<?if(!check_bitrix_sessid()) return;?>

<h3>Установить модуль с тестовыми данными?</h3>
<form action="" name="form1">
<?=bitrix_sessid_post()?>
<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
<input type="hidden" name="id" value="test_archive">
<input type="hidden" name="install" value="Y">
<input type="hidden" name="step" value="2">
	<table cellpadding="3" cellspacing="0" border="0" width="0%">
		<tr>
			<td><input type="checkbox" name="install_demo_data" value="Y" id="id_install_demo_data" ></td>
			<td><p><label for="id_install_demo_data">Установить с демо данными</label></p></td>
		</tr>
	</table>
	<br>
	<input type="submit" name="inst" value="<?= GetMessage("MOD_INSTALL")?>">
</form> 