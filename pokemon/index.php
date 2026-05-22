<?
include_once('./_common.php');
include_once('./_head.sub.php');

if(!$ph_id&&$character['ph_id']){
    $ph_id=$character['ph_id'];
}
$po=get_pokemon($ph_id);
$po_list=get_pokemon_list($po['ch_id']);
$ch=get_character($po['ch_id']);
?>

<div class="pokemon_tab">
    <ul>
        <?for ($i=0; $i < count($po_list); $i++) {?>
            <li><a href="./index.php?ph_id=<?=$po_list[$i]['ph_id']?>"><img src="<?=$po_list[$i]['po_dot']?>"><?=$po_list[$i]['po_name']?></a></li>
        <?}?>
 
    </ul>
</div>

<?include(G5_PATH.'/pokemon/info/pokemon_inc.php');

include_once('./_tail.sub.php');

?>