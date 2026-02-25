<?php
$sub_menu = "910200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

if ($is_admin != 'super')
	alert('최고관리자만 접근 가능합니다.');

$g5['title'] = '1:1 배틀 설정';
include_once ('./admin.head.php');

$bc = sql_fetch("select * from {$g5['battle_config_table']}");

$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">기본설정</a></li>
	<li><a href="#anc_002">보상 설정</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
</div>';

// 기본 항목 설정 데이터
$ad = sql_fetch("select * from {$g5['article_default_table']}");

$reward_rowspan = 3;

if(!$ad['ad_use_money']) { $reward_rowspan--; }
if(!$ad['ad_use_exp']) { $reward_rowspan--; }

// 연동코드 목록 가져오기
$extra = array();
$extra_result = sql_query("select * from {$g5['status_extra_table']}");
for($i=0; $ex = sql_fetch_array($extra_result); $i++) {
	$extra[] = $ex['ex_name'];
}


?>

<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);">
<input type="hidden" name="token" value="" id="token">

<section id="anc_001">
	<h2 class="h2_frm">배틀 기본설정</h2>
	<?php echo $pg_anchor ?>

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 150px;">
			<col style="width: 80px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">배틀기능 사용</th>
			<td colspan="2">
				<input type="checkbox" name="cf_use_status_battle" value="1" id="cf_use_status_battle" <?php echo $config['cf_use_status_battle']?'checked':''; ?>>
				<label for="cf_use_status_battle">배틀 사용</label>
			</td>
		</tr>
		<tr>
			<th scope="row">진행 방식</th>
			<td colspan="2">
				<?php echo help('전투 신청을 받은 대상의 대응 여부를 선택합니다.') ?>
				<input type="radio" name="bc_proc" value="자동" id="bc_proc" <?php echo $bc['bc_proc'] == '자동' || $bc['bc_proc'] == ''?'checked':''; ?>>
				<label for="bc_proc">자동 대응</label>
				&nbsp;&nbsp;
				<input type="radio" name="bc_proc" value="수동" id="bc_proc_2"<?php echo $bc['bc_proc'] == '수동'?'checked':''; ?>>
				<label for="bc_proc_2">수동 대응</label>
				&nbsp;&nbsp;
			</td>
		</tr>
		<tr>
			<th scope="row" rowspan="2">승패 기준</th>
			<td class="bo-right">선</td>
			<td>
				<?php echo help('선 액션을 하는 대상의 수치를 결정할 연동코드를 선택합니다.') ?>
				<select name="bc_before" style="width:90px;">
					<option value="">-</option>
					<? for($k=0; $k < count($extra); $k++) { ?>
						<option value="<?=$extra[$k]?>" <?=($bc['bc_before'] == $extra[$k] ? "selected" : "")?>><?=$extra[$k]?></option>
					<? } ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="bo-right">후</td>
			<td>
				<?php echo help('후 액션을 하는 대상의 수치를 결정할 연동코드를 선택합니다.') ?>
				<select name="bc_after" style="width:90px;">
					<option value="">-</option>
					<? for($k=0; $k < count($extra); $k++) { ?>
						<option value="<?=$extra[$k]?>" <?=($bc['bc_after'] == $extra[$k] ? "selected" : "")?>><?=$extra[$k]?></option>
					<? } ?>
				</select>
			</td>
		</tr>

		<tr>
			<th scope="row" rowspan="3">대미지</th>
			<td class="bo-right">설정</td>
			<td>
				<?php echo help('대미지를 적용할 대상을 설정합니다.') ?>
				<input type="radio" name="bc_damage_proc" value="" id="bc_damage_proc" <?php echo $bc['bc_damage_proc'] == ''?'checked':''; ?>>
				<label for="bc_damage_proc">설정안함</label>
				&nbsp;&nbsp;
				<input type="radio" name="bc_damage_proc" value="패자" id="bc_damage_proc_2"<?php echo $bc['bc_damage_proc'] == '패자'?'checked':''; ?>>
				<label for="bc_damage_proc_2">패자</label>
				&nbsp;&nbsp;
				<input type="radio" name="bc_damage_proc" value="패자+비김" id="bc_damage_proc_3"<?php echo $bc['bc_damage_proc'] == '패자+비김'?'checked':''; ?>>
				<label for="bc_damage_proc_3">패자 + 비김</label>
				&nbsp;&nbsp;
				<input type="radio" name="bc_damage_proc" value="양쪽" id="bc_damage_proc_4"<?php echo $bc['bc_damage_proc'] == '양쪽'?'checked':''; ?>>
				<label for="bc_damage_proc_4">양쪽</label>
				&nbsp;&nbsp;
			</td>
		</tr>
		<tr>
			<td class="bo-right" rowspan="2">수치 (합계)</td>
			<td>
				최소 <input type="text" name="bc_damage_min_point" value="<?php echo $bc['bc_damage_min_point'] ?>" style="width: 50px;">
				~
				최대 <input type="text" name="bc_damage_max_point" value="<?php echo $bc['bc_damage_max_point'] ?>" style="width: 50px;">
			</td>
		</tr>
		<tr>
			<td>
				선
				<select name="bc_damage_before" style="width:90px;">
					<option value="">-</option>
					<? for($k=0; $k < count($extra); $k++) { ?>
						<option value="<?=$extra[$k]?>" <?=($bc['bc_damage_before'] == $extra[$k] ? "selected" : "")?>><?=$extra[$k]?></option>
					<? } ?>
				</select>
				-
				후
				<select name="bc_damage_after" style="width:90px;">
					<option value="">-</option>
					<? for($k=0; $k < count($extra); $k++) { ?>
						<option value="<?=$extra[$k]?>" <?=($bc['bc_damage_after'] == $extra[$k] ? "selected" : "")?>><?=$extra[$k]?></option>
					<? } ?>
				</select>
				: 차감수치
			</td>
		</tr>
		</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_002">
	<h2 class="h2_frm">배틀 보상 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 150px;">
			<col style="width: 80px;">
			<col>
		</colgroup>
		<tbody>

		<tr>
			<th scope="row">지급범위</th>
			<td colspan="2">
				<input type="radio" name="bc_reward_proc" value="선행자" id="bc_reward_proc" <?php echo $bc['bc_reward_proc'] == '' || $bc['bc_reward_proc'] == '선행자'?'checked':''; ?>>
				<label for="bc_reward_proc">선행자(공격자)</label>
				&nbsp;&nbsp;
				<input type="radio" name="bc_reward_proc" value="전부" id="bc_reward_proc_2"<?php echo $bc['bc_reward_proc'] == '전부'?'checked':''; ?>>
				<label for="bc_reward_proc_2">참여자 전원</label>
				&nbsp;&nbsp;
			</td>
		</tr>




		<tr>
			<th scope="row" rowspan="<?=$reward_rowspan?>">승자</th>
			<? if($ad['ad_use_money']) { ?>
					<td class="bo-right"><?=$config['cf_money']?></td>
					<td>
						<input type="text" name="bc_reward_win_point" value="<?php echo $bc['bc_reward_win_point'] ?>" style="width: 50px;"> <?=$config['cf_money_pice']?>
					</td>
				</tr>
				<tr>
			<? } ?>
			<? if($ad['ad_use_exp']) { ?>
					<td class="bo-right"><?=$config['cf_exp_name']?></td>
					<td>
						<input type="text" name="bc_reward_win_exp" value="<?php echo $bc['bc_reward_win_exp'] ?>" style="width: 50px;"> <?=$config['cf_exp_pice']?>
					</td>
				</tr>
				<tr>
			<? } ?>
			<td class="bo-right">아이템</td>
			<td>
				<input type="hidden" name="bc_reward_win_item" id="bc_reward_win_item" value="" />
				<input type="text" name="bc_reward_win_item_name" value="<?=get_item_name($bc['bc_reward_win_item'])?>" id="bc_reward_win_item_name" onkeyup="get_ajax_item(this, 'bc_reward_win_item_list', 'bc_reward_win_item');" />
				<div id="bc_reward_win_item_list" class="ajax-list-box"><div class="list"></div></div>
			</td>
		</tr>

		<tr>
			<th scope="row" rowspan="<?=$reward_rowspan?>">패자</th>
			<? if($ad['ad_use_money']) { ?>
					<td class="bo-right"><?=$config['cf_money']?></td>
					<td>
						<input type="text" name="bc_reward_lose_point" value="<?php echo $bc['bc_reward_lose_point'] ?>" style="width: 50px;"> <?=$config['cf_money_pice']?>
					</td>
				</tr>
				<tr>
			<? } ?>
			<? if($ad['ad_use_exp']) { ?>
					<td class="bo-right"><?=$config['cf_exp_name']?></td>
					<td>
						<input type="text" name="bc_reward_lose_exp" value="<?php echo $bc['bc_reward_lose_exp'] ?>" style="width: 50px;"> <?=$config['cf_exp_pice']?>
					</td>
				</tr>
				<tr>
			<? } ?>
			<td class="bo-right">아이템</td>
			<td>
				<input type="hidden" name="bc_reward_lose_item" id="bc_reward_lose_item" value="" />
				<input type="text" name="bc_reward_lose_item_name" value="<?=get_item_name($bc['bc_reward_lose_item'])?>" id="bc_reward_lose_item_name" onkeyup="get_ajax_item(this, 'bc_reward_lose_item_list', 'bc_reward_lose_item');" />
				<div id="bc_reward_lose_item_list" class="ajax-list-box"><div class="list"></div></div>
			</td>
		</tr>

		<tr>
			<th scope="row" rowspan="<?=$reward_rowspan?>">비김</th>
			<? if($ad['ad_use_money']) { ?>
					<td class="bo-right"><?=$config['cf_money']?></td>
					<td>
						<input type="text" name="bc_reward_both_point" value="<?php echo $bc['bc_reward_both_point'] ?>" style="width: 50px;"> <?=$config['cf_money_pice']?>
					</td>
				</tr>
				<tr>
			<? } ?>
			<? if($ad['ad_use_exp']) { ?>
					<td class="bo-right"><?=$config['cf_exp_name']?></td>
					<td>
						<input type="text" name="bc_reward_both_exp" value="<?php echo $bc['bc_reward_both_exp'] ?>" style="width: 50px;"> <?=$config['cf_exp_pice']?>
					</td>
				</tr>
				<tr>
			<? } ?>
			<td class="bo-right">아이템</td>
			<td>
				<input type="hidden" name="bc_reward_both_item" id="bc_reward_both_item" value="" />
				<input type="text" name="bc_reward_both_item_name" value="<?=get_item_name($bc['bc_reward_both_item'])?>" id="bc_reward_both_item_name" onkeyup="get_ajax_item(this, 'bc_reward_both_item_list', 'bc_reward_both_item');" />
				<div id="bc_reward_both_item_list" class="ajax-list-box"><div class="list"></div></div>
			</td>
		</tr>
		</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>
</form>

<script>
function fconfigform_submit(f)
{
	f.action = "./battle_config_update.php";
	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
