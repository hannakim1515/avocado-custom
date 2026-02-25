<?php
include_once '../../common.php';

/* 입력 방어 */
$in_id = ses($_REQUEST, 'in_id', 0, 'int');
$url   = ses($_REQUEST, 'url', '', 'raw');
if ($url === '') {
    $url = ses($_SERVER, 'REQUEST_URI', '', 'raw');
}

/* 인벤토리 조회: 명시적 JOIN */
$in = sql_fetch("
    SELECT inven.*, item.*
    FROM {$g5['inventory_table']} AS inven
    INNER JOIN {$g5['item_table']} AS item ON inven.it_id = item.it_id
    WHERE inven.in_id = '{$in_id}'
");

if (empty($in['in_id'])) {
    echo '<p>아이템 보유 정보를 확인할 수 없습니다.</p>';
    return;
}

/* 캐릭터 결정 */
if (isset($character['ch_id']) && (int)$in['ch_id'] === (int)$character['ch_id']) {
    $ch = $character;
} else {
    $ch = get_character((int)$in['ch_id']);
}

?>
<form action="<?php echo G5_URL ?>/inventory/inventory_update.php" method="post">
    <input type="hidden" name="in_id" value="<?php echo (int)$in['in_id'] ?>" />
    <input type="hidden" name="ch_id" value="<?php echo ses($ch, 'ch_id', 0, 'int') ?>" />
    <input type="hidden" name="url" value="<?php echo h($url) ?>" />

    <div class="send-item-form">
        <div class="item-input">
            <input type="hidden" name="re_ch_id" id="send_re_ch_id" value="" />
            <input type="text"
                   name="re_ch_name"
                   value=""
                   id="send_re_ch_name"
                   onkeyup="get_ajax_character(this, 'send_character_list', 'send_re_ch_id', 'user');"
                   placeholder="받는 사람 이름 검색" />
            <div id="send_character_list" class="ajax-list-box"><div class="list"></div></div>
        </div>

        <div class="item-input">
            <input type="text" name="in_memo" placeholder="전달 메세지" />
        </div>
    </div>

    <div class="control-box">
        <button type="submit" class="ui-btn simple">보내기</button>
    </div>
</form>

<div class="ui-btn" onclick="viewImg(999)">닫기</div>
