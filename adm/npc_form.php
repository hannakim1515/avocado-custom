<?php
$sub_menu = "400900";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
$token = get_token();

// NPC Form Setting Data
$ns = sql_fetch("select * from {$g5['character_table']} ch LEFT JOIN {$g5['npc_table']} ns on ch.ch_id = ns.ns_id where ch.ch_id = '{$ns_id}'");

if(!$ns['ch_id']) {
	alert("NPC 정보가 확인되지 않습니다.");
}
$w = 'u';

if(!$ns['ns_id']) {
	$w = '';
	$ns['ns_id'] = $ns['ch_id'];
	$ns_id = $ns['ns_id'];

	$ns['ns_lv0_name'] = "불신";
	$ns['ns_lv1_name'] = "비호감";
	$ns['ns_lv2_name'] = "보통";
	$ns['ns_lv3_name'] = "호감";
	$ns['ns_lv4_name'] = "극호감";

	$ns['ns_lv0_color'] = "#aaaaaa";
	$ns['ns_lv1_color'] = "#999999";
	$ns['ns_lv2_color'] = "#5aa8d7";
	$ns['ns_lv3_color'] = "#e97979";
	$ns['ns_lv4_color'] = "#fd3c3c";
}

$is_shop_npc = false;
if($config['cf_shop_npc'] == $ns_id) {
	$is_shop_npc = true;
}


$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">기본 호감도 설정</a></li>
	<li><a href="#anc_002">호감도 구간별 설정</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
	<a href="./npc_list.php?{$qstr}">목록</a>
</div>';

$g5['title'] = "NPC {$ns['ch_name']} 관리";
include_once('./admin.head.php');

?>


<form name="fmember" id="fmember" action="./npc_form_update.php" method="post">
<input type="hidden" name="w" value="<?php echo $w ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">
<input type="hidden" name="ns_id" value="<?php echo $ns_id ?>">
<input type="hidden" name="ns_name" value="<?php echo $ns['ch_name'] ?>">

<style>
ul,li {margin:0; padding:0; list-style:none;}

.npcSettingWrap {display:flex; position:relative; flex-wrap:nowrap; border:1px solid #efeff5; border-right-width:0;}
.npcSettingWrap > * {flex-grow:1;}
.npcSettingWrap .npc-body {width:400px; background:no-repeat 50% 50%; background-size:cover; border:0px solid #efeff5; border-right-width:1px;}
.npcSettingWrap .npc-body a {position:absolute; top:10px; left:10px; background:#29c7c9; padding:8px 15px; border-radius:9em; color:#fff; font-weight:800;}
.npcSettingWrap .npc-setting {width:100%; padding:20px; padding-right:0;} 
.npcSettingWrap .npc-setting h3 {font-size:14px; color:#333; font-weight:800; margin:0 0 10px 0;}
.npcSettingWrap .npc-setting h3:before {content:"\e908"; font-family:'icon'; margin-right:5px;}
.npcSettingWrap .npc-setting * ~ h3 {margin-top:30px;}
.npcSettingWrap .npc-setting .simply-input input[type="text"] {height:25px !important; border-width:0; border-bottom-width:1px; text-align:center; outline:0;}
.npcSettingWrap .npc-setting em {font-style:normal; font-weight:800;}
.npcSettingWrap .npc-setting .down {color:#ff9c9c;}
.npcSettingWrap .npc-setting .up {color:#9494ff;}

.npcSettingWrap .npc-setting .progress {display:flex; flex-wrap:nowrap; justify-content:space-between; margin-top:10px; height:80px; position:relative; z-index:0;}
.npcSettingWrap .npc-setting .progress li {display:block; position:relative; flex-grow:1;}
.npcSettingWrap .npc-setting .progress li:before {content:""; display:block; position:absolute; top:50%; left:1px; right:1px; margin-top:-2px; height:4px; border-radius:0; background:#333; z-index:-1;}
.npcSettingWrap .npc-setting .progress li:after {content:""; display:block; position:absolute; width:2px; background:#000; top:10px; bottom:10px; left:0; margin-left:-1px; z-index:1;}
.npcSettingWrap .npc-setting .progress li:first-child:after {display:none;}
.npcSettingWrap .npc-setting .progress li.lv0 .point {visibility:hidden;}
.npcSettingWrap .npc-setting .progress li.lv0 .point,
.npcSettingWrap .npc-setting .progress li.lv0 .name {margin-left:-2px;}
.npcSettingWrap .npc-setting .progress li.lv0:before {background:<?=$ns['ns_lv0_color']?>;}
.npcSettingWrap .npc-setting .progress li.lv1:before {background:<?=$ns['ns_lv1_color']?>;}
.npcSettingWrap .npc-setting .progress li.lv2:before {background:<?=$ns['ns_lv2_color']?>;}
.npcSettingWrap .npc-setting .progress li.lv3:before {background:<?=$ns['ns_lv3_color']?>;}
.npcSettingWrap .npc-setting .progress li.lv4:before {background:<?=$ns['ns_lv4_color']?>;}
.npcSettingWrap .npc-setting .progress .point,
.npcSettingWrap .npc-setting .progress .name {position:absolute; width:90px; left:0; margin-left:-45px; text-align:center; white-space:nowrap; z-index:2;}
.npcSettingWrap .npc-setting .progress .point {top:10px; background:#000; color:#fff; border-radius:9em;}
.npcSettingWrap .npc-setting .progress .point input {width:60px; margin-right:5px; background:transparent; height:20px; border:none; outline:none; color:#fff; text-align:center; padding:0;}
.npcSettingWrap .npc-setting .progress .name {bottom:0;}
.npcSettingWrap .npc-setting .progress .name input {text-align:center; width:100%;}

.npcSettingWrap .tab-group {margin-top:10px;}
.npcSettingWrap .tabs ul {display:flex;}
.npcSettingWrap .tabs ul li {margin-right:2px;}
.npcSettingWrap .tabs button {border:1px solid #333; border-bottom:0; height:30px; padding:0 15px; border-bottom-width:1px; background:#fff; border-radius:0 10px 0 0; outline:0;}
.npcSettingWrap .tabs button.on {background:#333; color:#fff !important;}

.npcSettingWrap *[data-tab="초과구간"]				{border-color:#a70606 !important;}
.npcSettingWrap button[data-tab="초과구간"]		{color:#a70606 !important;}
.npcSettingWrap button.on[data-tab="초과구간"],
.npcSettingWrap *[data-tab="초과구간"] h4			{background:#a70606 !important;}

.npcSettingWrap *[data-tab="감소구간"]				{border-color:#ff9c9c !important;}
.npcSettingWrap button[data-tab="감소구간"]		{color:#ff9c9c !important;}
.npcSettingWrap button.on[data-tab="감소구간"],
.npcSettingWrap *[data-tab="감소구간"] h4			{background:#ff9c9c !important;}

.npcSettingWrap *[data-tab="tab_lv0"]				{border-color	:<?=$ns['ns_lv0_color']?> !important;}
.npcSettingWrap button[data-tab="tab_lv0"]			{color			:<?=$ns['ns_lv0_color']?> !important;}
.npcSettingWrap button.on[data-tab="tab_lv0"]		{background		:<?=$ns['ns_lv0_color']?> !important;}
.npcSettingWrap *[data-tab="tab_lv0"] h4			{background		:<?=$ns['ns_lv0_color']?> !important;}

.npcSettingWrap *[data-tab="tab_lv1"]				{border-color	:<?=$ns['ns_lv1_color']?> !important;}
.npcSettingWrap button[data-tab="tab_lv1"]			{color			:<?=$ns['ns_lv1_color']?> !important;}
.npcSettingWrap button.on[data-tab="tab_lv1"]		{background		:<?=$ns['ns_lv1_color']?> !important;}
.npcSettingWrap *[data-tab="tab_lv1"] h4			{background		:<?=$ns['ns_lv1_color']?> !important;}

.npcSettingWrap *[data-tab="tab_lv2"]				{border-color	:<?=$ns['ns_lv2_color']?> !important;}
.npcSettingWrap button[data-tab="tab_lv2"]			{color			:<?=$ns['ns_lv2_color']?> !important;}
.npcSettingWrap button.on[data-tab="tab_lv2"]		{background		:<?=$ns['ns_lv2_color']?> !important;}
.npcSettingWrap *[data-tab="tab_lv2"] h4			{background		:<?=$ns['ns_lv2_color']?> !important;}

.npcSettingWrap *[data-tab="tab_lv3"]				{border-color	:<?=$ns['ns_lv3_color']?> !important;}
.npcSettingWrap button[data-tab="tab_lv3"]			{color			:<?=$ns['ns_lv3_color']?> !important;}
.npcSettingWrap button.on[data-tab="tab_lv3"]		{background		:<?=$ns['ns_lv3_color']?> !important;}
.npcSettingWrap *[data-tab="tab_lv3"] h4			{background		:<?=$ns['ns_lv3_color']?> !important;}

.npcSettingWrap *[data-tab="tab_lv4"]				{border-color	:<?=$ns['ns_lv4_color']?> !important;}
.npcSettingWrap button[data-tab="tab_lv4"]			{color			:<?=$ns['ns_lv4_color']?> !important;}
.npcSettingWrap button.on[data-tab="tab_lv4"]		{background		:<?=$ns['ns_lv4_color']?> !important;}
.npcSettingWrap *[data-tab="tab_lv4"] h4			{background		:<?=$ns['ns_lv4_color']?> !important;}

.npcSettingWrap .tab-content {display:none; position:relative; border-top:2px solid #333;}
.npcSettingWrap .tab-content.on {display:block;}
.npcSettingWrap .tab-content h4 {display:block; color:#fff; padding:10px; margin:0; background:#333;}
.npcSettingWrap .tab-content th,
.npcSettingWrap .tab-content td {padding:0 10px !important; height:28px !important;}
.npcSettingWrap .tab-content td input {height:20px !important;}

.npcSettingWrap .talk-area {height:270px; line-height:30px; padding:0 5px; background:url('./img/bak_textarea.jpg') repeat 0 0; background-attachment:local; resize:none; outline:0;}
.npcSettingWrap .talk-area.fit {height:240px;}

</style>

<section id="anc_001">
	<div class="npcSettingWrap">
		<div class="npc-body" style="background-image:url('<?=$ns['ch_body']?>');">
			<a href="<?=G5_ADMIN_URL?>/npc_item_list.php?ns_id=<?=$ns['ns_id']?>">선물 설정 바로가기</a>

		</div>
		<div class="npc-setting">
			<h3>대기 중 대사</h3>
			<div class="tbl_frm01 tbl_wrap">
				<table>
					<tbody>
						<tr>
							<td>
								<?php echo help("대화 진입 시 가장 먼저 보여지게 될 한마디를 작성합니다. 해당 문구는 호감도에 영향을 받지 않습니다.") ?>
								<input type="text" name="ns_talk" value="<?=$ns['ns_talk']?>" class="full" />
							</td>
							
						</tr>
					</tbody>
				</table>
			</div>

			<h3>대화 시 포인트 증감 설정</h3>
			<div class="tbl_frm01 tbl_wrap simply-input">
				<table>
					<colgroup>
						<col style="width:100px;" />
						<col style="width:300px;" />
						<col style="width:100px;" />
						<col />
					</colgroup>
					<tbody>
						<tr>
							<th>증가</th>
							<td class="up">
								<input type="text" name="ns_talk_point" value="<?=$ns['ns_talk_point']?>" style="width:50px;"/> P ( 최대 대화 <input type="text" name="ns_talk_max" value="<?=$ns['ns_talk_max']?>" style="width:50px;"/>회까지 <em>증가</em>)
							</td>
							<th>감소</th>
							<td class="down">
								<input type="text" name="ns_talk_hate_point" value="<?=$ns['ns_talk_hate_point']?>" style="width:50px;"/> P ( 대화 <input type="text" name="ns_talk_hate" value="<?=$ns['ns_talk_hate']?>" style="width:50px;"/> 회 이후 <em>하락</em>)
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<h3>호감도 구간 설정</h3>
			<?php echo help("각 구간의 이름을 입력하지 않을 시 사용되지 않습니다. 단, 가장 첫번째 값은 필수입니다.") ?>
			<ul class="progress">
				<?
					for($i=0; $i <= 4; $i++) {
						$required  = "";
						if($i == 0) {
							$ns['ns_lv'.$i.'_point'] = -200000000;
							$required = "required";
						}
						echo "<li class='lv{$i}'>";
						echo "	<div class='point'><input type='text' name='ns_lv{$i}_point' {$required}  value='".$ns['ns_lv'.$i.'_point']."' /><span>p</span></div> ";
						echo "	<div class='name'><input type='text' name='ns_lv{$i}_name' {$required} value='".$ns['ns_lv'.$i.'_name']."' /></div> ";
						echo "</li>";
					}
				?>
			</ul>

			<h3>대사 및 기타 구간별 설정</h3>
			<?php echo help("각 대사는 엔터로 구분합니다. 아래의 대사들 중 랜덤으로 하나를 출력합니다. / [이름] : 캐릭터 이름을 출력합니다.") ?>
			<div class="tab-group">
				<div class="tabs">
					<ul>
						<li class="tab">
							<button type="button" onclick="fn_tabs(this);"  data-tab="선물기본" class="on">선물기본 (<?=$ns['ns_talk_item'] != '' ? count(explode("<br />", nl2br(trim($ns['ns_talk_item'])))) : 0?>)</button>
						</li>
						<li class="tab">
							<button type="button" onclick="fn_tabs(this);"  data-tab="초과구간">대화횟수초과 (<?=$ns['ns_talk_max_txt'] != '' ? count(explode("<br />", nl2br(trim($ns['ns_talk_max_txt'])))) : 0?>)</button>
						</li>
						<li class="tab">
							<button type="button" onclick="fn_tabs(this);"  data-tab="감소구간">호감도감소 (<?=$ns['ns_talk_hate_txt'] != '' ? count(explode("<br />", nl2br(trim($ns['ns_talk_hate_txt'])))) : 0?>)</button>
						</li>
						<? for($i=0; $i < 5; $i++) {
							$lv_tab = "tab_lv{$i}";
							$lv_name = $ns['ns_lv'.$i.'_name'];
							$lv_txt = $ns['ns_lv'.$i.'_txt'];
							if($ns['ns_lv'.$i.'_name']) {
						?>
							<li class="tab lv<?=$i?>">
								<button type="button" onclick="fn_tabs(this);"  data-tab="<?=$lv_tab?>"><?=$lv_name?> (<?=$lv_txt != '' ? count(explode("<br />", nl2br(trim($lv_txt)))) : 0?>)</button>
							</li>
						<? }} ?>
					</ul>
					<script>
						function fn_tabs(obj) {
							let $tab = $(obj);
							let $pannel = $tab.closest('.tab-group');
							let _target = $tab.attr('data-tab');
							$pannel.find('.on').removeClass('on');
							$pannel.find('[data-tab="'+_target+'"]').addClass('on');;
						}
					</script>
				</div>
				<div class="tab-content on" data-tab="선물기본">
					<h4>선물받았을 시 나오는 대사입니다.</h4>
					<textarea name="ns_talk_item" class="talk-area"><?=$ns['ns_talk_item']?></textarea>
				</div>
				<div class="tab-content" data-tab="초과구간">
					<h4>대화 최대횟수 초과 ~ 호감도 수치 감소 미만 구간에서 나오는 대사입니다.</h4>
					<textarea name="ns_talk_max_txt" class="talk-area"><?=$ns['ns_talk_max_txt']?></textarea>
				</div>
				<div class="tab-content" data-tab="감소구간">
					<h4>호감도 수치 감소 구간에서 나오는 대사입니다.</h4>
					<textarea name="ns_talk_hate_txt" class="talk-area"><?=$ns['ns_talk_hate_txt']?></textarea>
				</div>

				<? for($i=0; $i < 5; $i++) {
					$lv_tab = "tab_lv{$i}";
					$lv_name = $ns['ns_lv'.$i.'_name'];
					$lv_txt = $ns['ns_lv'.$i.'_txt'];
					$lv_cost = $ns['ns_lv'.$i.'_cost'];
					$lv_color = $ns['ns_lv'.$i.'_color'];
					$lv_item_name = get_item_name($ns['ns_lv'.$i.'_item']);

					if($lv_name) {
				?>
					<div class="tab-content" data-tab="<?=$lv_tab?>">
						<h4><?=$lv_name?> 구간에서 나오는 기본 대사입니다.</h4>
						<div class="tbl_frm01 tbl_wrap simply-input">
							<table>
								<colgroup>
									<? if($is_shop_npc) { ?>
									<col style="width:100px;" />
									<col style="width:180px;" />
									<? } ?>
									<col style="width:100px;" />
									<col style="width:180px;" />
									<col style="width:100px;" />
									<col />
								</colgroup>
								<tbody>
									<tr>
										<? if($is_shop_npc) { ?>
										<th>상점가 변동</th>
										<td>
											<span class="shop-cost">상점가격</span> x <input type="text" name="ns_lv<?=$i?>_cost" value="<?=$lv_cost?>" style="width:50px;"/>
										</td>
										<? } ?>
										<th>아이템 획득</th>
										<td>
											<input type="text" name="ns_lv<?=$i?>_item_name" value="<?=$lv_item_name?>" placeholder="아이템 이름을 정확히 입력하세요" style="width:200px;" />
										</td>
										<th>구간색상</th>
										<td>
											<input type="text" name="ns_lv<?=$i?>_color" value="<?=$lv_color?>" placeholder="#000000" style="width:70px;" />
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<textarea name="ns_lv<?=$i?>_txt" class="talk-area fit"><?=$lv_txt?></textarea>
					</div>
				<? }} ?>
			</div>
		</div>
	</div>
</section>
<? echo $frm_submit; ?>

</form>

<?php
include_once ('./admin.tail.php');
?>
