<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

if (!function_exists('mmb_craft_item_thumb')) {
	function mmb_craft_item_thumb($it_id, $it_name = '') {
		$it_id = trim($it_id);
		$it_name = trim($it_name);

		if(!$it_id || $it_id == 'NON') {
			return '<span class="mmb-list-item-thumb is-empty">?</span>';
		}

		if(!$it_name) {
			$it_name = get_item_name($it_id);
		}

		$item_img = get_item_img($it_id);
		$item_name = get_text($it_name);

		if($item_img) {
			return '<span class="mmb-list-item-thumb" title="'.$item_name.'"><img src="'.$item_img.'" alt="'.$item_name.'"></span>';
		}

		return '<span class="mmb-list-item-thumb is-empty">'.$item_name.'</span>';
	}
}

$update_href = $delete_href = '';

if (($member['mb_id'] && ($member['mb_id'] == $list_item['mb_id'])) || $is_admin) {
	$update_href = './write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;page='.$page.$qstr;
	if(!$list_item['wr_log'] || $is_admin) {
		set_session('ss_delete_token', $token = uniqid(time()));
		$delete_href ='./delete.php?bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;token='.$token.'&amp;page='.$page.urldecode($qstr);
	}
} else if (!$list_item['mb_id']) {
	$update_href = './password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;page='.$page.$qstr;
	$delete_href = './password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;page='.$page.$qstr;
}

$is_favorite = sql_fetch("select count(*) as cnt from {$g5['scrap_table']} where mb_id = '{$member['mb_id']}' and wr_id = '{$list_item['wr_id']}' and bo_table = '{$bo_table}'");
$is_favorite = $is_favorite['cnt'] > 0 ? true : false;

$is_viewer = true;
if($list_item['wr_secret'] == '1' && !$is_member) {
	$is_viewer = false;
}

$craft_log = explode('||', $list_item['wr_log']);
$is_craft_log = isset($craft_log[0]) && $craft_log[0] == 'H';
$craft_success = $is_craft_log && isset($craft_log[1]) && $craft_log[1] == 'S';
$result_item_id = $is_craft_log && isset($craft_log[2]) ? $craft_log[2] : '';
$result_item_name = $is_craft_log && isset($craft_log[3]) ? $craft_log[3] : '';
$material_items = $is_craft_log ? array_slice($craft_log, -3) : array();
$craft_message = $craft_success ? '을 제작하였습니다!' : '조합에 실패했습니다.';
$content_source = html_entity_decode($list_item['wr_content'], ENT_QUOTES, 'UTF-8');
$content = conv_content($content_source, 1, 'wr_content');
$content = search_font($stx, $content);

sql_query("update {$g5['call_table']} set bc_check = 1 where re_mb_id = '{$member['mb_id']}' and bo_table ='{$bo_table}' and wr_id = '{$list_item['wr_id']}'");
?>

<div class="item mmb-craft-log-item" id="log_<?=$list_item['wr_id']?>">
	<div class="mmb-craft-log-card">
		<div class="mmb-craft-log-head">
			<div>
				<strong><?=get_text($list_item['wr_name'])?></strong>
				<span><?=substr($list_item['wr_datetime'], 2, 14)?></span>
				<span>No. <?=($list_item['wr_num'] * -1)?></span>
			</div>
			<div class="mmb-craft-log-tools">
				<? if($delete_href) { ?><a href="<?php echo $delete_href ?>" onclick="del(this.href); return false;">삭제</a><? } ?>
				<? if($update_href) { ?><a href="<?php echo $update_href ?>">수정</a><? } ?>
			</div>
		</div>

		<? if($is_viewer) { ?>
			<div class="mmb-craft-result">
				<div class="mmb-craft-materials">
					<? if(count($material_items)) { ?>
						<? for($m_i = 0; $m_i < count($material_items); $m_i++) { ?>
							<?=mmb_craft_item_thumb($material_items[$m_i])?>
						<? } ?>
					<? } else { ?>
						<span class="mmb-list-item-thumb is-empty">?</span>
						<span class="mmb-list-item-thumb is-empty">?</span>
						<span class="mmb-list-item-thumb is-empty">?</span>
					<? } ?>
				</div>

				<span class="mmb-craft-equal">=</span>

				<div class="mmb-craft-created">
					<?=mmb_craft_item_thumb($result_item_id, $result_item_name)?>
					<strong><?=get_text($result_item_name ? $result_item_name : '아이템')?> <?=$craft_message?></strong>
				</div>
			</div>

			<? if(trim(strip_tags($list_item['wr_content']))) { ?>
				<div class="mmb-craft-log-content editor-style-root">
					<?=$content?>
				</div>
			<? } ?>
		<? } else { ?>
			<div class="mmb-craft-log-content">멤버 공개용 로그 입니다.</div>
		<? } ?>
	</div>
</div>
