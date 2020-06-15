<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

\CJSCore::Init(['jquery']);

$london = new \DateTimeZone("Europe/London");
$moscow = new \DateTimeZone("Europe/Moscow");
$Tokyo =   new \DateTimeZone("Asia/Tokyo");
$chicago =   new \DateTimeZone("America/Chicago");

$dateTimelondon = new DateTime("now", $london);
$dateTimemoscow = new DateTime("now", $moscow);
$dateTimeTokyo = new DateTime("now", $Tokyo);
$dateTimechicago = new DateTime("now", $chicago);

//die( $moscow ->getOffset($dateTimeTokyo) . " " );

$arResult ['times'] = [$dateTimelondon, $dateTimemoscow, $dateTimeTokyo, $dateTimechicago];

$arResult ['zone'] = $moscow;

$arResult ['formats'] = [ 'Y-m-d H:i:s', 'd.m.Y H:m:s' ];

$this->IncludeComponentTemplate();
?>