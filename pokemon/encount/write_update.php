<?
$temp_check = sql_fetch("select * from {$write_table}");

if(!isset($temp_check['wr_pokemon_log'])) { 
	sql_query("
		ALTER TABLE `{$write_table}`
			ADD `wr_pokemon_log` TEXT NOT NULL AFTER `wr_10`,
			ADD `po_egg` TEXT NOT NULL AFTER `wr_10`,
			ADD `po_name` TEXT NOT NULL AFTER `wr_10`,
			ADD `po_dot` TEXT NOT NULL AFTER `wr_10`
	", true);
}
unset($temp_check);

$pokemon_log='';

if($action_pokemon){
	if($action_pokemon=='encount'&&$encount){

		$lo_id=pokemon_encount($encount, $character['ch_id']);
		$log_inner="{$lo_id}||{$fail_msg}";

	}elseif($action_pokemon=='partner'&&$partner){
		$func=insert_partner_pokemon($partner,$character['ch_id']);
		$log_inner="{$func}";
	}elseif($action_pokemon=='evolution'&&$evolution){
		if($color_select||$sugar_select){
			if(!$color_select){$color_select=rand(1,9);}
			if(!$sugar_select){$sugar_select=rand(1,7);}
			$evo_select="e{$color_select}s{$sugar_select}";
		}
		if(!$evo_select){$evo_select=0;}
		$func=evo_pokemon($evolution, $character['ch_id'], $evo_select);
		$log_inner="{$func['po_name']}||{$func['po_species']}||{$func['po_id']}";
	}elseif($action_pokemon=='release'&&$release){
		$func=release_pokemon($release, $character['ch_id']);
		$log_inner="{$func['po_name']}||{$func['po_dot']}";
	}elseif($action_pokemon=='battle'&&$battle_enemy&&$battle_ph_id){
		$ba_ch=get_character($battle_enemy);
		$ba_mb=get_member($ba_ch['mb_id']);
		if($ba_ch['ch_id']){
			$log_link = G5_BBS_URL."/board.php?bo_table=".$bo_table."&log=".($wr_num * -1);
			
			$call_memo = "[ ".$character['ch_name']."]님이 배틀 신청을 하였습니다.";
			if($ba_mb['mb_id']){
				$bc_sql_common = "
					wr_id = '{$temp_wr_id}',
					wr_num = '{$wr_num}',
					bo_table = '{$bo_table}',
					mb_id = '{$member['mb_id']}',
					mb_name = '{$member['mb_nick']}',
					re_mb_id = '{$ba_mb['mb_id']}',
					re_mb_name = '{$ba_mb['mb_name']}',
					ch_side = '{$character['ch_side']}',
					memo = '{$call_memo}',
					bc_datetime = '".G5_TIME_YMDHIS."'
				";

				$sql = " insert into {$g5['call_table']} set {$bc_sql_common} ";
				sql_query($sql);

				// 회원 테이블에서 알람 업데이트를 해준다.
				// 실시간 호출 알림 기능
				$sql = " update {$g5['member_table']} 
							set mb_board_call = '".$member['mb_nick']."',
								mb_board_link = '{$log_link}'
						where mb_id = '".$ba_mb['mb_id']."' ";
				sql_query($sql);
			}
		}
		sql_query (" UPDATE {$g5['pokemon_battle_table']} set is_battle=1 where ph_id='{$battle_ph_id}' ");
		$log_id=insert_event_log('', '', '', '배틀', 999, '', $character['ch_id'],$battle_ph_id,$battle_enemy);
		$log_inner="{$log_id}||{$battle_enemy}||{$battle_msg}";
	}
	if($log_inner){
		$pokemon_log="pokemon||{$action_pokemon}||{$log_inner}";
		$customer_sql .= " , wr_pokemon_log = '{$log}'";
	}
}

if($character['ph_id']){
	$po_dot="";

	if($egg_id&&$character['egg_id']==$egg_id){
		
		$egg_log="";
		
		if($w=='c'){
			$egg_value=1;
		}else{
			$egg_value=3;
		}
	
		$egg=hatch_pokemon($egg_id, $character['ch_id'], $egg_value);

		if($egg['ph_egg']){
			$po_dot.="<img src=\"{$egg['po_dot']}\">";
		}else{
			$egg['po_content']="알이 부화하여 {$egg['po_name']}이(가) 태어났다!";
		}
		$egg_log="{$egg['ph_egg']}|{$egg['po_name']}|{$egg['po_dot']}|{$egg['po_content']}";

		$customer_sql .= " , po_egg = '{$egg_log}'";
	}

	$po=get_pokemon_list($character['ch_id'],false);

	for ($i=0; $i < count($po); $i++) { 
		if($po[$i]['po_dot']){$po_dot.="<img src=\"{$po[$i]['po_dot']}\">";}
	}
	$customer_sql .= " , po_name = '{$po[0]['po_name']}', po_dot='{$po_dot}' ";
}

$customer_sql .= " , wr_pokemon_log = '{$pokemon_log}'";
?>