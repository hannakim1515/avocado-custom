<?php
require_once './_common.php';
require_once G5_PATH.'/head.sub.php';
$is_all=true;
?>
<style>
    @import url(<?php echo G5_URL?>/k_battle/css/raid.default.css);
    @import url(<?php echo G5_URL?>/k_battle/css/admin.css);
    body {
        overflow: hidden;
    }
    .log-area {
        padding-bottom: 100px;
    }
</style>


<div class='btns'>
    <a class='ui-btn'
    href="./raid.unit.php?<?php echo $raid_get ?>&type=ch">
    캐릭터
    </a>
    <a class='ui-btn point'
    href="./raid.log.php?<?php echo $raid_get ?>">
    로그
    </a>
    <?php if ($is_admin){?>
        <a class='ui-btn'
        href="./raid.admin.php?<?php echo $raid_get ?>">
        관리자
    </a>
    <?php }?>
</div>

<?php

?>

<?php
require_once G5_PATH.'/k_battle/skin_default/raid_log.php';
require_once G5_PATH.'/tail.sub.php'; ?>
