<?php
include_once('./_common.php');
include_once('./_head.sub.php');

/* 입력값/기본값 방어 */
$type = ses($_REQUEST, 'type', '', 'raw');

// $character는 common.php에서 설정됨
if (!isset($character) || !is_array($character)) {
    $character = array();
}

$ch_id = ses($character, 'ch_id', 0, 'int');

/* 안내 메시지 */
$msg = ($type === 'upgrade')
    ? '강화할 아이템을 선택해 주세요.'
    : '모습을 바꿀 아이템을 선택해 주세요.';
?>
<style>
    @import url(./css/upgrade.css);
    <?php include G5_PATH . '/k_battle/css/equip_custom.php'; ?>
</style>

<?php
/* 뷰 출력 */
include_once('./skin_default/upgrade.php');
include_once('./_tail.sub.php');
