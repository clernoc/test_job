<?if(!check_bitrix_sessid()) return;?>
<?

if (is_array($errors) && count($errors) > 0) :
    foreach ($errors as $val)
        $alErrors .= $val . "<br>";
    echo CAdminMessage::ShowMessage(Array(
        "TYPE" => "ERROR",
        "MESSAGE" => GetMessage("MOD_INST_ERR"),
        "DETAILS" => $alErrors,
        "HTML" => true
    ));
else :
    echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
endif;

?>