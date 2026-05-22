<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

$is_error = false;
$option = '';
$option_hidden = '';
if ($is_notice || $is_html || $is_secret || $is_mail) {
	$option = '';
	if ($is_notice) {
		$option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">공지</label>';
	}

	if ($is_html) {
		if ($is_dhtml_editor) {
			$option_hidden .= '<input type="hidden" value="html1" name="html">';
		} else {
			$option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
		}
	}

	if ($is_secret) {
		if ($is_admin || $is_secret==1) {
			$option .= "\n".'<input type="checkbox" id="secret" name="secret" value="secret" '.$secret_checked.'>'."\n".'<label for="secret">비밀글</label>';
		} else {
			$option_hidden .= '<input type="hidden" name="secret" value="secret">';
		}
	}

	if ($is_mail) {
		$option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
	}
}

/*if(!$character['ch_id']) {
?>
	<div class='error'>
	<? if(!$is_member) { ?>
		작성권한이 없습니다.<br />
		계정로그인을 해주시길 바랍니다.

		<div class="btn-group txt-center">
			<a href="<?=G5_BBS_URL?>/login.php" class="ui-btn">로그인</a>
			<a href="./board.php?bo_table=<?=$bo_table?>" class="ui-btn">목록으로</a>
		</div>
	<? } else { ?>
		대표캐릭터가 설정되지 않았습니다.<br />
		마이페이지에서 대표 캐릭터를 선택해 주시길 바랍니다.

		<div class="btn-group txt-center">
			<a href="<?=G5_URL?>/mypage" class="ui-btn">마이페이지</a>
			<a href="./board.php?bo_table=<?=$bo_table?>" class="ui-btn">목록으로</a>
		</div>
	<? } ?>
	</div>
<?
	$is_error = true;
}*/

if(!$is_error) { 

	if($character['ch_id']) { 
		// 사용가능 아이템 검색
		$temp_sql = "select it.it_id, it.it_name, inven.in_id from {$g5['inventory_table']} inven, {$g5['item_table']} it where it.it_id = inven.it_id and it.it_use_mmb_able = '1' and inven.ch_id = '{$character['ch_id']}'";
		$mmb_item_result = sql_query($temp_sql);
		$mmb_item = array();
		for($i = 0; $row = sql_fetch_array($mmb_item_result); $i++) {
			$mmb_item[$i] = $row;
		}
	}

	// 카테고리 재정의
	$is_category = false;
	$category_option = '';
	if ($board['bo_use_category']) {
		$ca_name = "";
		if (isset($write['ca_name']))
			$ca_name = $write['ca_name'];

		$categories = explode("|", $board['bo_category_list']); // 구분자가 , 로 되어 있음
		$category_option = "";
		for ($i=0; $i<count($categories); $i++) {
			$checked = '';
			$class = '';
			$category = trim($categories[$i]);
			if (!$category) continue;

			if($i==0 && $ca_name == '') { 
				$ca_name = $category;
			}
			if ($category == $ca_name) {
				$class = ' class="on"';
				$checked = 'checked';
			}
			
			$category_option .= "<li $class>";
			
			$category_option .= "
				<input type='radio' name='ca_name' value='{$category}' id='ca_name_{$i}' {$checked} />
				<label for='ca_name_{$i}' data-index='view_{$i}'>$category</label>
			</li>\n";
		}

		$is_category = true;
	}

	if($w == 'u') { 
		if($write['wr_subject'] == "--|UPLOADING|--")	{
			$write['wr_subject'] = $character['ch_name'];
			if(!$write['wr_subject']) $write['wr_subject'] = 'GUEST';
		}

	} else { 
		$write['wr_subject'] = $character['ch_name'];
		if(!$write['wr_subject']) $write['wr_subject'] = 'GUEST';
	}

	$temp_sql = "select ch_thumb, mb_id, ch_id, ch_name from {$g5['character_table']} where ch_state = '승인' and ch_type != 'test' and ch_id != '{$character['ch_id']}' order by ch_type asc, ch_name asc";
	$re_ch_result = sql_query($temp_sql);
	$re_ch = array();
	for($i = 0; $row = sql_fetch_array($re_ch_result); $i++) {
		$re_ch[$i] = $row;
	}

	$make_item = array();
	if($character['ch_id']) {
		$make_result = sql_query("select inven.in_id, it.it_id, it.it_name, it.it_img from {$g5['inventory_table']} inven, {$g5['item_table']} it where inven.ch_id = '{$character['ch_id']}' and it.it_use_recepi = 1 and inven.it_id = it.it_id order by it.it_name asc, inven.in_id asc");
		for($i = 0; $row = sql_fetch_array($make_result); $i++) {
			$make_item[] = $row;
		}
	}

	?>

	<div id="load_log_board">
		<section id="bo_w" class="mmb-board">
			<!-- 게시물 작성/수정 시작 { -->
			<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
			<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
			<input type="hidden" name="w" value="<?php echo $w ?>">
			<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
			<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
			<input type="hidden" name="sca" value="<?php echo $sca ?>">
			<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
			<input type="hidden" name="stx" value="<?php echo $stx ?>">
			<input type="hidden" name="spt" value="<?php echo $spt ?>">
			<input type="hidden" name="sst" value="<?php echo $sst ?>">
			<input type="hidden" name="sod" value="<?php echo $sod ?>">
			<input type="hidden" name="page" value="<?php echo $page ?>">
			<input type="hidden" name="wr_subject" value="<?=$write['wr_subject']?>" />
			<input type="hidden" name="wr_width" id="wr_width" value="<?php echo $write['wr_width']; ?>">
			<input type="hidden" name="wr_height" id="wr_height" value="<?php echo $write['wr_height']; ?>">
			<input type="hidden" name="action" value="H">
			<?php echo $option_hidden; ?>

			<div class="theme-box">
			<?php if ($is_category) { ?>
				<ul id="board_category">
					<?php echo $category_option ?>
				</ul>
			<?php } ?>

				<div id="board_action" class="inner">
					<div class="mmb-craft-box">
						<h3>조합대</h3>
						<p>재료 슬롯을 클릭해 아이템을 선택하세요.</p>
						<div class="mmb-craft-slots">
						<? for($slot_i = 1; $slot_i <= 3; $slot_i++) { ?>
							<div class="mmb-craft-slot-wrap">
								<button type="button" class="mmb-craft-slot" data-slot="<?=$slot_i?>" aria-label="재료 <?=$slot_i?> 선택">
									<span class="mmb-craft-empty">ITEM <?=$slot_i?></span>
									<img src="" alt="" />
								</button>
								<strong>재료 <?=$slot_i?></strong>
								<select name="make_<?=$slot_i?>" id="make_<?=$slot_i?>" class="make-item mmb-craft-select" tabindex="-1" aria-hidden="true">
									<option value="" data-name="" data-img="">재료 선택</option>
								<? for($make_i = 0; $make_i < count($make_item); $make_i++) {
									$re_row = $make_item[$make_i];
									$item_img = $re_row['it_img'] ? $re_row['it_img'] : get_item_img($re_row['it_id']);
								?>
									<option value="<?=$re_row['in_id']?>" data-name="<?=get_text($re_row['it_name'])?>" data-img="<?=$item_img?>">
										<?=$re_row['it_name']?>
									</option>
								<? } ?>
								</select>
							</div>
						<? } ?>
						</div>
					</div>

					<div class="mmb-craft-picker" aria-hidden="true">
						<div class="mmb-craft-picker-panel">
							<div class="mmb-craft-picker-head">
								<strong>재료 선택</strong>
								<button type="button" class="mmb-craft-close" aria-label="닫기">×</button>
							</div>
							<input type="text" class="mmb-craft-search" placeholder="아이템 이름 검색">
							<ul class="mmb-craft-list">
							<? for($make_i = 0; $make_i < count($make_item); $make_i++) {
								$re_row = $make_item[$make_i];
								$item_img = $re_row['it_img'] ? $re_row['it_img'] : get_item_img($re_row['it_id']);
							?>
								<li>
									<button type="button" class="mmb-craft-option" data-value="<?=$re_row['in_id']?>" data-name="<?=get_text($re_row['it_name'])?>" data-img="<?=$item_img?>">
										<span class="thumb"><? if($item_img) { ?><img src="<?=$item_img?>" alt=""><? } ?></span>
										<span class="name"><?=get_text($re_row['it_name'])?></span>
									</button>
								</li>
							<? } ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			
			<hr class="padding small" />

			<div class="comments">
				<?php if($write_min || $write_max) { ?>
				<!-- 최소/최대 글자 수 사용 시 -->
				<p id="char_count_desc">이 게시판은 최소 <strong><?php echo $write_min; ?></strong>글자 이상, 최대 <strong><?php echo $write_max; ?></strong>글자 이하까지 글을 쓰실 수 있습니다.</p>
				<?php } ?>
				<textarea id="wr_content" name="wr_content" class="mmb-craft-note" placeholder="조합에 남길 짧은 글을 작성해 주세요."><?php echo $content; ?></textarea>
				<?php if($write_min || $write_max) { ?>
				<!-- 최소/최대 글자 수 사용 시 -->
				<div id="char_count_wrap"><span id="char_count"></span>글자</div>
				<?php } ?>
			</div>
			
			<hr class="padding" />

			<div class="txt-center">
				<button type="submit" id="btn_submit" accesskey="s" class="ui-btn">조합하기</button>
				<button type="button" onclick="location.href='./board.php?bo_table=<?=$bo_table?>';" class="ui-btn">LIST</button>
			</div>
			</form>

			<hr class="padding" />
			<hr class="padding" />
			<hr class="padding" />
		</section>
	<!-- } 게시물 작성/수정 끝 -->
	</div>

<script>
	<?php if($write_min || $write_max) { ?>
	// 글자수 제한
	var char_min = parseInt(<?php echo $write_min; ?>); // 최소
	var char_max = parseInt(<?php echo $write_max; ?>); // 최대
	check_byte("wr_content", "char_count");

	$(function() {
		$("#wr_content").on("keyup", function() {
			check_byte("wr_content", "char_count");
		});
	});
	<?php } ?>
	function html_auto_br(obj)
	{
		if (obj.checked) {
			result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
			if (result)
				obj.value = "html2";
			else
				obj.value = "html1";
		}
		else
			obj.value = "";
	}

	function fwrite_submit(f)
	{
		if(!f.wr_content.value) {
			alert("내용을 입력해 주십시오.");
			f.wr_content.focus();
			return false;
		}

		var subject = "";
		var content = "";
		$.ajax({
			url: g5_bbs_url+"/ajax.filter.php",
			type: "POST",
			data: {
				"subject": f.wr_subject.value,
				"content": f.wr_content.value
			},
			dataType: "json",
			async: false,
			cache: false,
			success: function(data, textStatus) {
				subject = data.subject;
				content = data.content;
			}
		});

		if (subject) {
			alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
			f.wr_subject.focus();
			return false;
		}

		if (content) {
			alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
			if (typeof(ed_wr_content) != "undefined")
				ed_wr_content.returnFalse();
			else
				f.wr_content.focus();
			return false;
		}

		if (document.getElementById("char_count")) {
			if (char_min > 0 || char_max > 0) {
				var cnt = parseInt(check_byte("wr_content", "char_count"));
				if (char_min > 0 && char_min > cnt) {
					alert("내용은 "+char_min+"글자 이상 쓰셔야 합니다.");
					return false;
				}
				else if (char_max > 0 && char_max < cnt) {
					alert("내용은 "+char_max+"글자 이하로 쓰셔야 합니다.");
					return false;
				}
			}
		}

		if(f.action && f.action.value == 'H') {
			if(!f.make_1.value || !f.make_2.value || !f.make_3.value) {
				alert("조합 재료 3개를 모두 선택해 주세요.");
				return false;
			}
		}

		document.getElementById("btn_submit").disabled = "disabled";
		return true;
	}
</script>

<script>
$('.change-thumb').on('change', function() {
	var select_item = $(this).find('option:selected');

	var thumb = select_item.data('thumb');

	if(typeof(thumb) != "undefined") {
		// 썸네일이 있는 경우
		$(this).closest('.has-thumb').find('.ui-thumb').empty().append("<img src='"+thumb+"' alt='' />");
	} else { 
		$(this).closest('.has-thumb').find('.ui-thumb').empty();
	}
});

var craftSlot = null;

function refreshCraftSlots() {
	$('.mmb-craft-select').each(function() {
		var slot = $(this).closest('.mmb-craft-slot-wrap').find('.mmb-craft-slot');
		var selected = $(this).find('option:selected');
		var itemName = selected.data('name');
		var itemImg = selected.data('img');

		if($(this).val()) {
			slot.addClass('is-selected');
			slot.find('.mmb-craft-empty').text(itemName);
			if(itemImg) {
				slot.find('img').attr('src', itemImg).attr('alt', itemName).show();
			} else {
				slot.find('img').attr('src', '').attr('alt', '').hide();
			}
		} else {
			slot.removeClass('is-selected');
			slot.find('.mmb-craft-empty').text('ITEM ' + slot.data('slot'));
			slot.find('img').attr('src', '').attr('alt', '').hide();
		}
	});
}

function refreshCraftDisabledOptions() {
	var selectedValues = [];

	$('.mmb-craft-select').each(function() {
		if($(this).val()) {
			selectedValues.push($(this).val());
		}
	});

	$('.mmb-craft-select option, .mmb-craft-option').prop('disabled', false).removeClass('is-disabled');

	$('.mmb-craft-select').each(function() {
		var current = $(this).val();
		for(var i = 0; i < selectedValues.length; i++) {
			if(selectedValues[i] && selectedValues[i] !== current) {
				$(this).find('option[value="' + selectedValues[i] + '"]').prop('disabled', true);
			}
		}
	});

	$('.mmb-craft-option').each(function() {
		var value = String($(this).data('value'));
		if(selectedValues.indexOf(value) > -1 && (!craftSlot || craftSlot.find('select').val() !== value)) {
			$(this).prop('disabled', true).addClass('is-disabled');
		}
	});
}

function openCraftPicker(slotWrap) {
	craftSlot = slotWrap;
	$('.mmb-craft-search').val('');
	$('.mmb-craft-list li').show();
	refreshCraftDisabledOptions();
	$('.mmb-craft-picker').addClass('is-open').attr('aria-hidden', 'false');
	setTimeout(function() {
		$('.mmb-craft-search').focus();
	}, 50);
}

function closeCraftPicker() {
	$('.mmb-craft-picker').removeClass('is-open').attr('aria-hidden', 'true');
	craftSlot = null;
}

$('.mmb-craft-slot').on('click', function() {
	openCraftPicker($(this).closest('.mmb-craft-slot-wrap'));
});

$('.mmb-craft-close, .mmb-craft-picker').on('click', function(e) {
	if(e.target === this) {
		closeCraftPicker();
	}
});

$('.mmb-craft-search').on('input', function() {
	var keyword = $(this).val().toLowerCase();
	$('.mmb-craft-list li').each(function() {
		var name = String($(this).find('.mmb-craft-option').data('name')).toLowerCase();
		$(this).toggle(name.indexOf(keyword) > -1);
	});
});

$('.mmb-craft-option').on('click', function() {
	if(!craftSlot || $(this).prop('disabled')) {
		return;
	}

	var select = craftSlot.find('.mmb-craft-select');
	select.val($(this).data('value')).trigger('change');
	closeCraftPicker();
});

$('.mmb-craft-select').on('change', function() {
	refreshCraftSlots();
	refreshCraftDisabledOptions();
});

$('.make-item').change(function() {
	$('.make-item').find("option").attr('disabled', false);
	$('.make-item').each(function() {
		if($(this).val()) { 
			$('.make-item').not(this).find("option[value="+ $(this).val() + "]").attr('disabled', true);
		}
	});
});

$(function() {
	refreshCraftSlots();
	refreshCraftDisabledOptions();
});

</script>

<? } ?>
