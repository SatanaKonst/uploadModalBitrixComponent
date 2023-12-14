# uploadModalBitrixComponent
Тестовое задание

# Установка
1. Сопировать в папу local проекта
2. Установить модуль миграции sprint.migration если не установлен
3. Уставновить миграцию Version20231213191906.php. Миграция добавляет новый ИБ и почтовое событие.
4. Добавить компонент на страницу через код или через редактирование страницы
```php
<?$APPLICATION->IncludeComponent(
	"test:uploadModal",
	"",
	Array(
		"APPROVAL_TEXT" => "Текст согласия на <a href=\"#\">обработку персональных данных</a>",
		"EMAIL_EVENT" => "UPLOAD_MODAL",
		"IBLOCK_CODE" => "FOS",
		"IBLOCK_ID" => "",
		"MAX_UPLOAD_FILE_SIZE" => "2", // Указвается в МБ
		"NEW_IBLOCK_ELEMENT_PREFIX" => "Новое сообщение из формы"
	)
);?>
```