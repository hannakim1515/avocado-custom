<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

// 도발 스킬 : 사용 대상은 자신으로 고정됩니다.
$skill_name = $sh['sh_name'] ? $sh['sh_name'] : $sh['sk_name'];
$log = "<p class=\'txt-skill-info\'><strong>{$skill_name}</strong> 스킬을 사용했습니다.</p>";
$log .= "<p class=\'txt-skill-info ty2\'>{$sh['sk_descript']}</p>";

insert_dungeon_log("스킬", $ds, $dm, $sh, 0, 0, $log);

if($sh['sk_keep_limit'] > 0) {
	insert_dungeon_log("효과", $ds, $dm, $sh, $sh['sl_set_value'], 0, "");
}

?>