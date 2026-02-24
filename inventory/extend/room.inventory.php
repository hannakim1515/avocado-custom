<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * [추가] 페어 시스템 연동 로직
 * 내 캐릭터가 속한 페어의 고유 키를 생성합니다.
 */
$pair = sql_fetch(" select co_id from {$g5['couple_table']} where co_left = '{$character['ch_id']}' or co_right = '{$character['ch_id']}' ");
$room_key = $pair['co_id'] ? "pair_".$pair['co_id'] : "solo_".$character['ch_id'];


if($inven_function == "마이룸가구") {
    // 지정된 이미지를 가구로 추가합니다.
    // [수정] ch_id 대신 페어 키($room_key)를 기준으로 카운트하고 저장합니다.
    $count_row = sql_fetch("select MAX(ro_order) as cnt from {$g5['room_table']} where pair_id = '{$room_key}'");
    $next_order = (int)$count_row['cnt'] + 1;

    // [수정] ch_id 컬럼 자리에 $room_key를 넣습니다. (DB 컬럼명이 pair_id로 바뀌었다고 가정)
    sql_query("insert into {$g5['room_table']} set ro_img='{$in['it_1']}', ro_order='{$next_order}', pair_id = '{$room_key}'");
    
    delete_inventory($in['in_id'], $in['it_use_ever']);

    echo location_url($return_url);
}

if($inven_function == "마이룸커스텀가구") {
    // 자유로운 이미지를 추가합니다.
    include_once(G5_PATH.'/room/room.add.inc.php');
}

if($inven_function == "마이룸배경") {
    // [수정] 배경화면은 페어 양쪽 캐릭터 모두에게 적용되도록 업데이트합니다.
    if($pair['co_id']) {
        // 페어인 경우 두 사람 모두의 배경을 바꿉니다.
        sql_query("update {$g5['character_table']} set ch_room_bak = '{$in['it_1']}' where ch_id = '{$pair['co_left']}' or ch_id = '{$pair['co_right']}'");
    } else {
        // 솔로인 경우 본인만 바꿉니다.
        sql_query("update {$g5['character_table']} set ch_room_bak = '{$in['it_1']}' where ch_id = '{$character['ch_id']}'");
    }
    
    delete_inventory($in['in_id'], $in['it_use_ever']);

    echo location_url($return_url);
}
?>