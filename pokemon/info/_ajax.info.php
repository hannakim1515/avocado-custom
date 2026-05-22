<?
include_once("./_common.php");

$po=get_pokemon($ph_id);

if($po['ph_id']) {
	include(G5_PATH."/pokemon/info/pokemon_info.php");
}


?>
