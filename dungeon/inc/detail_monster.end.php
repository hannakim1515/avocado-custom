<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/*------------------------------------------
	Monster State 
------------------------------------------ */

?>
<div class="allTurn">
	<div class="mid">
		<div>
			<span>전체 행동횟수</span>
			<strong><?=$total_turn_count['cnt']?></strong>
		</div>
	</div>
</div>

<div class="img">
	<img src="<?=$ds['dg_mon_img']?>" alt="" />
</div>

<div class="pop none-trans">
	<div class="tit">
		<div class="in">
			<strong>전투가 완료되었습니다</strong>
		</div>
	</div>
</div>
