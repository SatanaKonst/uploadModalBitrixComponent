<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arCurrentValues */

if (!CModule::IncludeModule("iblock"))
    return;

$arTypesEx = CIBlockParameters::GetIBlockTypes(array("-" => " "));
$arIBlocks = array();
$arIBlocksCode = array();
$db_iblock = CIBlock::GetList(
    array("SORT" => "ASC"),
    array(
        "SITE_ID" => $_REQUEST["site"],
        "TYPE" => ($arCurrentValues["IBLOCK_TYPE"] != "-" ? $arCurrentValues["IBLOCK_TYPE"] : "")
    )
);
while ($arRes = $db_iblock->Fetch()) {
    $arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];
    $arIBlocksCode[$arRes["CODE"]] = "[" . $arRes["CODE"] . "] " . $arRes["NAME"];
}


$arProperty_LNS = array();
$rsProp = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("ACTIVE" => "Y", "IBLOCK_ID" => (isset($arCurrentValues["IBLOCK_ID"]) ? $arCurrentValues["IBLOCK_ID"] : $arCurrentValues["ID"])));
while ($arr = $rsProp->Fetch()) {
    $arProperty[$arr["CODE"]] = "[" . $arr["CODE"] . "] " . $arr["NAME"];
    if (in_array($arr["PROPERTY_TYPE"], array("L", "N", "S"))) {
        $arProperty_LNS[$arr["CODE"]] = "[" . $arr["CODE"] . "] " . $arr["NAME"];
    }
}

$arComponentParameters = array(
    "GROUPS" => array(),
    "PARAMETERS" => array(
        'IBLOCK_ID' => array(
            'PARENT' => 'BASE',
            'NAME' => 'ID Инфоблока',
            'TYPE' => 'LIST',
            'ADDITIONAL_VALUES' => 'Y',
            'VALUES' => $arIBlocks,
            'REFRESH' => 'N',
        ),
        "IBLOCK_CODE" => array(
            "PARENT" => "BASE",
            "NAME" => 'Код Инфоблока',
            "TYPE" => "LIST",
            "VALUES" => $arIBlocksCode,
            "DEFAULT" => '={$_REQUEST["ID"]}',
            "ADDITIONAL_VALUES" => "Y",
            "REFRESH" => "Y",
        ),
        "MAX_UPLOAD_FILE_SIZE" => array(
            "PARENT" => "BASE",
            "NAME" => 'Максимальный размер файла для загрузки',
            "TYPE" => "TEXT",
            "VALUES" => 2,
            "DEFAULT" => 2,
        ),
        "APPROVAL_TEXT" => array(
            "PARENT" => "BASE",
            "NAME" => 'Текст согласия на обработку персональных данных',
            "TYPE" => "TEXT",
            "VALUES" => '',
            "DEFAULT" => 'Текст согласия на <a href="#">обработку персональных данных</a>',
        ),
        "EMAIL_EVENT" => array(
            "PARENT" => "BASE",
            "NAME" => 'Почтовое событие',
            "TYPE" => "TEXT",
            "VALUES" => '',
            "DEFAULT" => 'UPLOAD_MODAL',
        ),
        'NEW_IBLOCK_ELEMENT_PREFIX' => array(
            "PARENT" => "BASE",
            "NAME" => 'Префикс нового элемента в ИБ',
            "TYPE" => "TEXT",
            "VALUES" => '',
            "DEFAULT" => 'Новое сообщение из формы',
        ),

    ),
);
