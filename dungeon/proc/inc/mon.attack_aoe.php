<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

// 광역 공격 처리 부분
$m_log_comment = "{$ds['dg_w_attack_comment']}&&&&";

// 이 include는 일반 공격과 별도 요청 경로로 실행되므로, 일반 공격에서만
// 초기화되던 적 공격 버프 값을 여기서도 읽어 로그/피해 계산이 끊기지 않게 한다.
$buff_attack = get_status_buffer_enermy($ds_id, "공격");

for($i=0; $i < count($dm_list); $i++) {
	$re_dm = $dm_list[$i];
	$damage = rand($ds['dg_w_attack_min'], $ds['dg_w_attack_max']);
	$damage = $damage + $buff_attack;
	$m_log_comment .= set_dungeon_character_damage($ds_id, $re_dm['ch_id'], $re_dm, $damage);
}
insert_dungeon_log("몬스터", $ds, null, null, null, null, $m_log_comment);

// 턴수 처리
// -- 도트 대미지 처리가 필요하다.
$dot_damage = sql_fetch("select SUM(dl_value) as total from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and dl_cate = '효과' and dl_function='공격' and dl_keep_limit > 0");
$dot_damage = $dot_damage['total'];
if($dot_damage > 0) {
	insert_dungeon_log("시스템", $ds, null, null, 0, 0, "추가 대미지 <strong>{$dot_damage}</strong><i>!</i>");
	$result_state = set_dungeon_mon_damage($ds_id, $ds, $dot_damage);
}
sql_query("update {$g5['dungeon_log_table']} set dl_keep_limit = dl_keep_limit-1 where ds_id = '{$ds_id}' and dl_cate = '효과' and ch_id = -1 and dl_keep_limit > 0");


?>
