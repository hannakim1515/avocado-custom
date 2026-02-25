<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>
<? if($is_able_search) { ?>
<button type="button" onclick="map_search(<?=$ma['ma_id']?>);" class="ui-btn"><span>탐색하기</span></button>
<? } ?>