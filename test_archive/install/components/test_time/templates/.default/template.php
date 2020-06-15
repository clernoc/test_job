<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?

//$this->addExternalJs(substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT'])).'/js/moment-timezone.js');

/* 
=== 2) Задача на создание компонента ===
 
Создать компонент, который выводит текущее время в произвольном формате выбранного часового пояса.
 
В качестве параметра из визуальной части компонент должен принимать формат вывода времени и часовой пояс (Выпадающий список).
Компонент должен быть виден в дереве компонентов визуального редактора по пути "Компоненты проекта -> Вывод времени".
Разместить компонент для демонстрации работы на странице /clocks.php
 
Плюсом будет создание компонента внутри модуля и его установка вместе с модулем.
 */
 
 
?>
<div class="">
	<label for="zone-select">Выбор времени:</label>
	<select class="zone-select" id="zone-select">
		<?
		foreach( $arResult ['times'] as $time ){
		?>
			<option value="<?=$time->getOffset()?>" ><?=$time->getTimezone()->getName()?> </option>
		<?
		}
		?>
	</select>
	<br />
	<label for="zone-select">Выбор формата:</label>
	<select class="format-select" id="format_select">
		<?
		foreach( $arResult ['formats'] as $format ){
		?>
			<option value="<?=$format?>" ><?=$format?> </option>
		<?
		}
		?>
	</select>
</div>

<div class="timer">

</div>

<?
/* <script>
BX.message({
	TEMPLATE_PATH: "<?=$componentPath?>/ajax.php"
});
</script> */
?>