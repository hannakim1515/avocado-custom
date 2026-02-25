<?php 
if($raid_type=='mmbraid'&&isset($bo_table)&&$bo_table){
    $ra = $board;
    $ra['ra_id'] = $bo_table;
    $ra_id = $bo_table;
}

$raid=get_k_raid_type($raid_type, $ra_id);

$battle_table = $raid['battle_table'];
$ar_value = $raid['ar_value'];
$ar_title = $raid['ar_title'];
$raid_get = $raid['raid_get'];

$select_default=", unit.unit_type, unit.hp_now, unit.hp_max, unit.mp_now, unit.mp_max, unit.tt_done, unit.is_aggr, unit.is_stun, unit.rm_id";
$enemy_select="origin.mo_thumb as unit_thumb, origin.mo_name as unit_name{$select_default}";
$ally_select="origin.ch_thumb as unit_thumb, origin.ch_name as unit_name{$select_default}";
?>