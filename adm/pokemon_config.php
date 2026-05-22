<?php
$sub_menu = "091001";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');


$g5['title'] = '포켓몬 환경설정';
include_once ('./admin.head.php');

if(!$pkm_cf['cf_id']){
	$sql_raw = @file(G5_PATH.'/pokemon/database.sql');
	if ($sql_raw === false) {
		die('database.sql 을 읽을 수 없습니다.');
	}
	$sql_raw = implode('', $sql_raw);


	$sql_raw = preg_replace('/^--.*$/m', '', $sql_raw);
	$sql_raw = preg_replace('!/\*.*?\*/!s', '', $sql_raw);

	
	if (!defined('G5_TABLE_PREFIX')) {
		die('G5_TABLE_PREFIX 가 정의되어 있지 않습니다.');
	}
	$sql_raw = preg_replace(
		'/`avo_([^`]+`)/',
		'`'.G5_TABLE_PREFIX.'$1',
		$sql_raw
	);

	$queries = explode(';', $sql_raw);

	$errors = array();
	for ($i=0; $i<count($queries); $i++) {
		$q = trim($queries[$i]);
		if ($q === '') continue;

		if (stripos($q, 'delimiter ') === 0) continue;

		$res = sql_query($q, true); 

		if (!$res) {
			$errors[] = $q;
		}
	}

	if (!empty($errors)) {
		echo "<pre>다음 쿼리에서 오류가 발생했습니다:\n";
		foreach ($errors as $bad) {
			echo htmlspecialchars($bad, ENT_QUOTES, 'UTF-8').";\n\n";
		}
		echo "</pre>";
		exit;
	}

	sql_query("
		ALTER TABLE `{$g5['inventory_table']}`
			ADD `is_del` INT(2) NOT NULL DEFAULT '0'
	", true);

	sql_query("
		ALTER TABLE `{$g5['character_table']}`
			ADD `ph_id` INT(11) NOT NULL DEFAULT '0',
			ADD `egg_id` INT(11) NOT NULL DEFAULT '0',
			ADD `egg_item` INT(11) NOT NULL DEFAULT '0'
	", true);

	alert('설치가 완료되었습니다.');
}else{

	$pg_anchor = '<ul class="anchor">
						<li><a href="#anc_001">기본설정</a></li>
						<li><a href="#anc_002">스탯설정</a></li>
						<li><a href="#anc_003">아이템설정</a></li>
					</ul>';
	
	$frm_submit = '<div class="btn_confirm01 btn_confirm">
		<input type="submit" value="확인" class="btn_submit" accesskey="s">
	</div>';
?>
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);" enctype="multipart/form-data">
		<input type="hidden" name="token" value="" id="token">
		<section id="anc_001">
			<h2 class="h2_frm">기본 설정</h2>
			<?php echo $pg_anchor ?>
			<div class="tbl_frm01 tbl_wrap">
				<table>
					<caption>포켓몬 환경 설정</caption>
					<colgroup>
						<col style="width: 120px;" >
						<col>
					</colgroup>
					<tbody>
					
					<tr>
						<th>엔트리 수</th>
						<td>
							<select name="po_max">
								<?for ($i=1; $i <= 6 ; $i++) {?>
									<option value="<?=$i?>" <?if($pkm_cf['po_max']==$i){echo 'selected';}?>><?=$i?>마리</option>
								<?}?>
							</select>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</section>
		<?php echo $frm_submit; ?>

		<section id="anc_002">
			<h2 class="h2_frm">스탯 설정</h2>
			<?php echo $pg_anchor ?>
			<div class="tbl_head01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width: 120px;" />
						<col style="width: 60px;" />
						<col style="width: 80px;" />
						<col/>
					</colgroup>
					<thead>
						<tr>
							<th scope="col">구분</th>
							<th scope="col">이름</th>
							<th scope="col">아이콘</th>
							<th scope="col">아이템</th>
						</tr>
					</thead>
					<tbody>
						<?for ($i=1; $i <= 5; $i++) {
							$st_name="st_{$i}";
							$st_icon="{$st_name}_icon";
							$st_item="{$st_name}_it_id";
						?>
							<tr>
								<td>
									<?=$i?>
								</td>
								<td>
									<input type="text" name="<?=$st_name?>" value="<?=$pkm_cf[$st_name]?>">
								</td>
								<td>
									<span style="font-family:'Material icons'"><?=$pkm_cf[$st_icon]?></span>
									<input type="text" name="<?=$st_icon?>"  value="<?=$pkm_cf[$st_icon]?>">
								</td>
								<td>
									<input type="hidden" name="<?=$st_item?>" id="<?=$st_item?>" value="<?=$pkm_cf[$st_item]?>" />
									<input type="text" name="it_name_<?=$i?>" value="<?=get_item_name($pkm_cf[$st_item])?>" id="it_name_<?=$i?>" onkeyup="get_ajax_item(this, 'item_list_<?=$i?>', '<?=$st_item?>');" />
									<div id="item_list_<?=$i?>" class="ajax-list-box"><div class="list"></div></div>
								</td>
							</tr>
						<?}?>
					</tbody>
				</table>
			</div>
		</section>
		<?php echo $frm_submit; ?>

		<section id="anc_003">
			<h2 class="h2_frm">아이템 설정</h2>
			<?php echo $pg_anchor ?>
			<div class="tbl_head01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width: 120px;" />
						<col/>
					</colgroup>
					<thead>
						<tr>
							<th scope="col">구분</th>
							<th scope="col">아이템</th>
						</tr>
					</thead>
					<tbody>
						<?
						$it_list=array();
						$it_list[]=array('name'=>'조우','ar'=>'encount');
						$it_list[]=array('name'=>'포획','ar'=>'partner');
						$it_list[]=array('name'=>'이별','ar'=>'release');
						$it_list[]=array('name'=>'진화','ar'=>'evo');

						foreach ($it_list as $it) {
							$ar_name="{$it['ar']}_it_id";
						?>
							<tr>
								<td>
									<?=$it['name']?>
								</td>
								<td>
									<input type="hidden" name="<?=$ar_name?>" id="<?=$ar_name?>" value="<?=$pkm_cf[$ar_name]?>" />
									<input type="text" name="it_name_<?=$it['ar']?>" value="<?=get_item_name($pkm_cf[$ar_name])?>" id="it_name_<?=$it['ar']?>" onkeyup="get_ajax_item(this, 'item_list_<?=$it['ar']?>', '<?=$ar_name?>');" />
									<div id="item_list_<?=$it['ar']?>" class="ajax-list-box"><div class="list"></div></div>
								</td>
							</tr>
						<?}?>
					</tbody>
				</table>
			</div>
		</section>
		<?php echo $frm_submit; ?>
	</form>

	<script>

	function fconfigform_submit(f)
	{
		f.action = "./pokemon_config_update.php";
		return true;
	}
	</script>

<?}

include_once ('./admin.tail.php');
?>
