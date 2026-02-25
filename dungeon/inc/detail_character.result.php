<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>

<ul class="character-list">
	<? for($i=0; $i < count($dm_list_All); $i++) { 
		$d = $dm_list_All[$i];

		$state_class = "";
		if($d['dm_result'] == "" && $d['dm_state'] == 'E') {
			$state_class = "state-E";
		}
	?>
		<li class="<?=$state_class?>">
			<div class="item">
				<div class="thumb" style="cursor:pointer;" onclick="window.open('./character.php?dm_id=<?=$d['dm_id']?>', 'quick_character', 'width=400,height=550');">
					<em><img src="<?=$d['ch_thumb']?>" alt="" /></em>
				</div>
				<div class="comment">
					<div class="in">
						<div class="descript">
							<div class="name">
								<i><img src="<?=$d['si_img']?>" alt="" onerror="this.remove();" /></i>
								<strong><?=$d['ch_name']?></strong>
							</div>
							
							<div class="present">
								<? if($state_class != "") { ?>
									<div class="no-get">
										<p>보상을 획득할 수 없습니다.</p>
									</div>
								<? } else {
										if($d['dm_result'] == "") { 
								?>
										<div class="con">
											<span class="loading">보상획득 대기중...</span>
										</div>
								<?		} else {	?>
										<div class="con">
											<?=$d['dm_result']?>
										</div>
								<?
										}
									}
								?>
							</div>
						</div>

						<? if($state_class == "" && $d['dm_result'] == "" && $d['ch_id'] == $character['ch_id']) { ?>
							<div class="result-control">
								<button type="button" onclick="location.href='<?=G5_URL?>/dungeon/proc/get_present.php?dm_id=<?=$d['dm_id']?>';" class="ui-btn">보상 획득</button>
							</div>
						<? } ?>

					</div>
				</div>


			</div>
		</li>
	<? } ?>
</ul>