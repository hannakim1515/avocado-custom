<?php
include_once('./_common.php');
define('_MAIN_', true);
include_once(G5_PATH.'/head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/main.css">', 0);
include_once(G5_PATH.'/intro.php');
?>

<div id="main_body">
    <?php include(G5_PATH.'/gpt_main.php'); ?>
</div>

<script>
$(function () {
    window.onload = function () {
        $('#body').css('opacity', 1);
    };
});
</script>

<?php include_once(G5_PATH.'/tail.php'); ?>
