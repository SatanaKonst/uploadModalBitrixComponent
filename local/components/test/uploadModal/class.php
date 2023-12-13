<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\ErrorCollection;
use \Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserConsent\Agreement;
use F5\Helpers\User;
use Sprint\Migration\HelperManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class UploadModal extends \CBitrixComponent implements Controllerable
{

    private $MAX_UPLOAD_FILE_SIZE_MB = 2;

    private $iblockInfo = null;

    public function __construct($component = null)
    {
        parent::__construct($component);
        \Bitrix\Main\Loader::includeModule('iblock');
    }

    /**
     * Конфигурация ajax методов
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'auth' => [
                'prefilters' => [
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
        ];
    }

    public function saveAction($approval, $data): AjaxJson
    {
        try {
            global $USER;

            if ($approval !== 'Y') {
                throw new \Exception('Вы не согласились на обработку персональных данных');
            }
            $this->init();

            $props = $this->getIblockProps();
            $saveProperty = [];
            $sendEmailData = [];
            $sendEmailFileProps = [];
            foreach ($props as $prop) {
                if (!empty($data[$prop['CODE']])) {
                    switch ($prop['PROPERTY_TYPE']) {
                        case 'F':
                            if (!empty($_FILES[$prop['CODE']])) {
                                //Проверим размер загружаемого файла (ограничение 5 МБ)
                                if ($_FILES[$prop['CODE']]['size'] > ($this->MAX_UPLOAD_FILE_SIZE_MB * 1024 * 1024)) {
                                    throw new Exception('Файл превышает допустимый размер');
                                }

                                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp';
                                if (!file_exists($uploaddir)) {
                                    mkdir($uploaddir, 755, true);
                                }

                                $uploadfile = $uploaddir . basename($_FILES[$prop['CODE']]['name']);

                                if (move_uploaded_file($_FILES[$prop['CODE']]['tmp_name'], $uploadfile)) {
                                    $image = CFile::MakeFileArray($uploadfile);
                                    $image["MODULE_ID"] = "main";
                                    $saveProperty[$prop['CODE']] = $image;
                                    $sendEmailFileProps[] = $prop['CODE'];
                                }
                            }
                            break;
                        default:
                            $saveProperty[$prop['CODE']] = $data[$prop['CODE']];
                            $sendEmailData[] = $prop['NAME'] . ': ' . $data[$prop['CODE']];
                            break;
                    }
                }
            }

            $newElement = new CIBlockElement;
            $arLoadProductArray = array(
                "IBLOCK_ID" => $this->getIblockId(),
                "NAME" => (!empty($this->arParams['NEW_IBLOCK_ELEMENT_PREFIX'])) ? $this->arParams['NEW_IBLOCK_ELEMENT_PREFIX'] : "Новое сообщение из формы",
                "ACTIVE" => "Y",
            );

            if ($newElementId = $newElement->Add($arLoadProductArray)) {
                CIBlockElement::SetPropertyValuesEx(
                    $newElementId,
                    $this->getIblockId(),
                    $saveProperty
                );

                //Удаляем времменный файл если он был
                if (!empty($uploadfile)) {
                    unlink($uploadfile);
                }

                $newElementUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=' . $this->iblockInfo['ID'] . '&type=' . $this->iblockInfo['TYPE'] . '&lang=ru';

                // Если были загружены файлы, то получим их
                $attachFilesHtml = [];
                $attachFiles = [];

                if (!empty($sendEmailFileProps)) {
                    $elementInfo = CIBlockElement::GetByID($newElementId)->GetNextElement();
                    $fileProps = $elementInfo->GetProperties();
                    foreach ($sendEmailFileProps as $filePropCode) {
                        $values = $fileProps[$filePropCode]['VALUE'];
                        if (!empty($values)) {
                            if (!is_array($values)) {
                                $values = [$values];
                            }

                            foreach ($values as $value) {
                                $attachFilesHtml[] = 'http://' . $_SERVER['SERVER_NAME'] . '/' . CFile::GetPath($value);
                                $attachFiles[] = $_SERVER['DOCUMENT_ROOT'] . '/' . CFile::GetPath($value);
                            }
                        }
                    }

                }

                if (!empty($this->arParams['EMAIL_EVENT'])) {
                    CEvent::Send(
                        $this->arParams['EMAIL_EVENT'],
                        SITE_ID,
                        array(
                            'IBLOCK_PAGE_LINK' => $newElementUrl,
                            'CONTENT' => implode("\n", $sendEmailData),
                            'FILE_LINKS' => implode("\n", $attachFilesHtml)
                        ),
                        'Y',
                        '',
                        $attachFiles
                    );
                }

            } else {
                throw new \Exception($newElement->LAST_ERROR);
            }


            return AjaxJson::createSuccess(
                array(
                    'result' => true
                )
            );
        } catch (Throwable $exception) {
            return AjaxJson::createError(
                new ErrorCollection([new Error($exception->getMessage())])
            );
        }
    }


    protected function listKeysSignedParameters()
    {
        return [  //массива параметров которые надо брать из параметров компонента
            'IBLOCK_ID',
            'IBLOCK_CODE',
            'NEW_IBLOCK_ELEMENT_PREFIX',
            'EMAIL_EVENT'
        ];
    }

    /**
     * Основной код компонента
     * Если есть ID в параметрах компонента, значит это обновление данных
     * иначе добавление рассылки.
     *
     * @return mixed|void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function executeComponent()
    {
        Loc::loadMessages(__FILE__);

        $this->init();

        //Получим свойства ИБ
        $this->arResult['FIELDS'] = $this->getIblockProps();

        $this->includeComponentTemplate();
    }

    private function init()
    {
        $iblockInfoQueryParams = [];
        if (!empty($this->arParams['IBLOCK_ID'])) {
            $iblockInfoQueryParams['ID'] = $this->arParams['IBLOCK_ID'];
        } elseif (empty($this->arParams['IBLOCK_ID']) && !empty($this->arParams['IBLOCK_CODE'])) {
            $iblockInfoQueryParams['CODE'] = $this->arParams['IBLOCK_CODE'];
        }

        if (empty($iblockInfoQueryParams)) {
            throw new \Exception('Не указан ID инфоблока');
        }

        $this->iblockInfo = CIBlock::GetList([], $iblockInfoQueryParams)->GetNext();

        if (!empty($this->arParams['MAX_UPLOAD_FILE_SIZE'])) {
            $this->MAX_UPLOAD_FILE_SIZE_MB = $this->arParams['MAX_UPLOAD_FILE_SIZE'];
        }
    }

    /**
     * @return int
     */
    private function getIblockId(): int
    {
        return (int)$this->iblockInfo['ID'];
    }


    /**
     * @return array
     */
    private function getIblockProps(): array
    {
        $iblockPropsQuery = CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $this->getIblockId()
            ]
        );
        $props = [];
        while ($prop = $iblockPropsQuery->GetNext()) {
            $props[] = $prop;
        }
        return $props;
    }

}