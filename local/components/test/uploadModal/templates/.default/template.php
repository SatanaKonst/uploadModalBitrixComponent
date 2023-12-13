<?php

use Bitrix\Main\Localization\Loc;

CJSCore::Init();
//echo '<pre>';
//print_r($arResult['FIELDS']);
//echo '</pre>';
?>

<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
    Открыть модалку
</button>

<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $APPLICATION->GetCurPage() ?>" method="post" id="uploadModalForm">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="signedParameters"
                       value="<?= $this->getComponent()->getSignedParameters() ?>">
                <input type="hidden" name="data[PAGE_URL]" value="<?= $APPLICATION->GetCurPageParam() ?>">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Тестовая форма</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($arResult['FIELDS'] as $FIELD) {
                        $fieldLabel = $FIELD['NAME'];
                        $fieldCode = $FIELD['CODE'];
                        $fieldName = 'data[' . $FIELD['CODE'] . ']';
                        $required = $FIELD['IS_REQUIRED'] === 'Y';
                        $defaultValue = $FIELD['DEFAULT_VALUE'];
                        $fileType = null;
                        if (!empty($FIELD['FILE_TYPE'])) {
                            $fileType = mb_split(',', $FIELD['FILE_TYPE']);
                            foreach ($fileType as $index => $item) {
                                $fileType[$index] = '.' . trim($item);
                            }
                            $fileType = 'accept="' . implode(',', $fileType) . '"';
                        }

                        switch ($FIELD['PROPERTY_TYPE']) {
                            case 'F':
                                include __DIR__ . '/fields/file.php';
                                break;
                            default:
                                include __DIR__ . '/fields/string.php';
                                break;
                        }
                    } ?>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="approval" value="Y" required>
                        <label class="form-check-label" for="flexCheckDefault">
                            <?= $arParams['~APPROVAL_TEXT'] ?>
                        </label>
                    </div>

                    <div class="mb-3">
                        <div id="infoMsg" class="alert" style="display: none"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div>
    </div>
</div>



