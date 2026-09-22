<?php

// $data가 이미 정의되어 있지 않은 경우에만 초기화 (include 호출 시 유지)
if (!isset($data) || !is_array($data)) {
    $data = array('warning' => '', 'info' => '', 'disable' => '', 'target' => '');
}

// 유닛 조회
$select = "origin.{$unit_type}_name AS unit_name, unit.*";
$rm     = get_k_unit($rm_id, $unit_type, $select);
if (!is_array($rm)) $rm = array();

if (empty($rm['rm_id'])) {
    $data['warning'] = '대상 정보를 찾을 수 없습니다.';
    return;
}

// 턴/상태 검사
if ((int)$rm['hp_now'] <= 0) {
    $data['warning'] = '현재 행동불능 상태입니다.';
} elseif ($turn_type === '') {
    if ($battle_table !== '') {
        $rid = sql_escape_string($ra_id);
        $ra  = sql_fetch("SELECT now_turn FROM {$battle_table} WHERE {$ar_title} = '{$rid}'");
        if (empty($ra['now_turn']) || (int)$ra['now_turn'] !== $rm_id) {
            $data['warning'] = '내 턴이 아닙니다.';
        }
    } else {
        $data['warning'] = '전투 정보를 확인할 수 없습니다.';
    }
} elseif ( ($turn_type === 'all' || ($turn_type === 'skill' && $type === 'skill')) && (int)$rm['tt_done'] === 1 ) {
    // 연산자 우선순위 명시
    $data['warning'] = '이번 턴 행동이 종료되었습니다.';
}

if ($data['warning'] !== '') {
    return;
}

// 메시지 구성
$msg = '';
if (!empty($board['bo_8_subj'])) {
    $text = nl2br((string)$board['bo_8_subj']);
    $text = get_k_rand_text($text);
    if (!empty($text)) {
        $msg .= "<p class=\"mmbsystem\">{$text}</p>";
    }
}

$msg .= "<p class=\"log-title\">{$rm['unit_name']}의 행동</p>";

$text_base = '';
if     ($type === 'atk')   { $text_base = ses($board, 'bo_3_subj', ''); }
elseif ($type === 'heal')  { $text_base = ses($board, 'bo_4_subj', ''); }
elseif ($type === 'item')  { $text_base = ses($board, 'bo_6_subj', ''); }
elseif ($type === 'skill') { $text_base = ses($board, 'bo_5_subj', ''); }

if ($text_base !== '') {
    $text = nl2br((string)$text_base);
    $text = get_k_rand_text($text);
    if (!empty($text)) {
        $msg .= "<p class=\"mmbsystem\">{$text}</p>";
    }
}

// 액션 처리
if ($type === 'atk' || $type === 'heal') {

    use_k_action($type, $rm, $target_id, $target_type, $ra_id, $msg, $option);

} elseif ($type === 'guard') {

    use_k_guard($rm, $ra_id, $msg, $option);

} elseif ($type === 'item') {

    $in = sql_fetch("
        SELECT it.it_value, it.it_type, inven.in_id, it.it_img, it.it_content, it.it_name
        FROM {$g5['inventory_table']} AS inven
        JOIN {$g5['item_table']} AS it ON it.it_id = inven.it_id
        WHERE inven.in_id = '{$target_id}'
        LIMIT 1
    ");

    if (empty($in['in_id'])) {
        $data['warning'] = '아이템 정보를 찾을 수 없습니다.';
        return;
    }

    $recover = '';
    $hp_name = '';
    if ($in['it_type'] === 'HP회복(K)') {
        $recover = 'hp';
        $hp_name = ses($kb_cf, 'hp_name', 'HP');
    } elseif ($in['it_type'] === 'MP회복(K)') {
        $recover = 'mp';
        $hp_name = ses($kb_cf, 'mp_name', 'MP');
    } else {
        $data['warning'] = '아이템 정보를 찾을 수 없습니다.';
        return;
    }

    if ($recover !== '') {
        set_k_dmg($rm, $recover, (int)$in['it_value']);
        delete_inventory($target_id);

        $msg .= "<p class=\"act-title item\">아이템 사용</p>";
        $msg .= "<div class=\"sk-info\"><p class=\"sc-name\"><img src=\"{$in['it_img']}\">{$in['it_name']}</p><p class=\"sk-content\">{$in['it_content']}</p></div>";
        $msg .= "<p><span class=\"name\">{$rm['unit_name']}</span>의 {$hp_name} <span class=\"dmg heal\">{$in['it_value']}</span></p>";

        insert_k_log($msg, $ra_id, 'ch', $option);
    }

} elseif ($type === 'skill') {

    $sk = get_k_battle_skill($bs_id, '*', true);
    if (!is_array($sk)) $sk = array();
    use_k_skill($sk, $rm, $target_id, $target_type, $ra_id, $msg, $option);
}

// 턴 종료 처리
if ($battle_table !== '' && ($turn_type === 'all' || $turn_type === '' || ($turn_type === 'skill' && $type === 'skill'))) {
    sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = {$rm_id}");
}
