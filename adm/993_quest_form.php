<?php
$sub_menu = "993200";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], 'w');

// 파라미터 처리
$w = ses($_GET, 'w', '', 'raw');
$qu_id = ses($_GET, 'qu_id', 0, 'int');

$html_title = '퀘스트';
$required = "";
$readonly = "";
$quest = array();

if ($w == '') {

    $html_title .= ' 생성';
    $required = 'required';
    $required_valid = 'alnum_';
    $sound_only = '<strong class="sound_only">필수</strong>';


} else if ($w == 'u') {

    $html_title .= ' 수정';
    $quest = sql_fetch("SELECT * FROM {$g5['k_quest_table']} WHERE qu_id = '{$qu_id}'");
    if (!$quest['qu_id'])
        alert('존재하지 않는 퀘스트 입니다.');
    $readonly = 'readonly';
}

$g5['title'] = $html_title;
include_once ('./admin.head.php');


$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
	<a href="./993_quest_list.php?'.$qstr.'">목록</a>'.PHP_EOL;
$frm_submit .= '</div>';

?>

<?
	include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');
	if (empty($fr_date)) $fr_date = G5_TIME_YMD;
?>


<form name="fitemform" id="fitemform" action="./993_quest_form_update.php" onsubmit="return fitemform_submit(this)" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo $w ?>">
<input type="hidden" name="qu_id" value="<?php echo $qu_id ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">

<section id="anc_001">
	<h2 class="h2_frm">퀘스트 기본 설정</h2>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>퀘스트 기본 설정</caption>
			<colgroup>
				<col style="width: 130px;">
				<col style="width: 100px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th scope="row">퀘스트 분류</th>
					<td colspan="2">
						<select name="qu_type">
							<option value="main" <?php echo $quest['qu_type'] == "main" ? "selected" : ""?>>메인 퀘스트</option>
							<option value="sub" <?php echo $quest['qu_type'] == "sub" ? "selected" : ""?>>서브 퀘스트</option>
							<option value="member" <?php echo $quest['qu_type'] == "member" ? "selected" : ""?>>멤버 퀘스트</option>
						</select>
						
						<select name="qu_state" style="margin-left:10px;">
							<option value="hidden" <?php echo ses($quest, 'qu_state', 'active') == "hidden" ? "selected" : ""?>>미표시</option>
							<option value="active" <?php echo ses($quest, 'qu_state', 'active') == "active" ? "selected" : ""?>>진행중</option>
							<option value="done" <?php echo ses($quest, 'qu_state', 'active') == "done" ? "selected" : ""?>>완료</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">완료 요구사항</th>
					<td colspan="2">
						<select name="qu_complete_type" id="qu_complete_type" onchange="toggleRequestItem()">
							<option value="log" <?php echo ses($quest, 'qu_complete_type', 'log') == "log" ? "selected" : ""?>>로그 등록</option>
							<option value="item" <?php echo ses($quest, 'qu_complete_type', 'log') == "item" ? "selected" : ""?>>아이템 제출</option>
						</select>
						<span id="request_item_wrap" style="<?php echo ses($quest, 'qu_complete_type', 'log') == "item" ? "" : "display:none;"?>">
							<input type="hidden" name="qu_request_item" id="qu_request_item" value="<?php echo (int)ses($quest, 'qu_request_item', 0); ?>">
							<input type="text" name="request_item_name" id="request_item_name" value="<?php echo ses($quest, 'qu_request_item', 0) ? get_item_name($quest['qu_request_item']) : ''; ?>" placeholder="요구 아이템 검색" onkeyup="get_ajax_item(this, 'request_item_list', 'qu_request_item');" autocomplete="off" size="20">
							<span id="qu_request_item_display" style="color:#888;"><?php echo ses($quest, 'qu_request_item', 0) ? '(ID: '.$quest['qu_request_item'].')' : ''; ?></span>
							<div id="request_item_list" class="ajax-list-box"><div class="list"></div></div>
							x <input type="text" name="qu_request_item_count" value="<?php echo (int)ses($quest, 'qu_request_item_count', 1); ?>" size="3">개
						</span>
					</td>
				</tr>

				<tr>
					<th scope="row">제출 방식</th>
					<td colspan="2">
						<select name="qu_submit_type">
							<option value="mmb" <?php echo ses($quest, 'qu_submit_type', 'mmb') == "mmb" ? "selected" : ""?>>자비란 경유 (글 작성)</option>
							<option value="direct" <?php echo ses($quest, 'qu_submit_type', 'mmb') == "direct" ? "selected" : ""?>>직접 완료</option>
							<option value="admin" <?php echo ses($quest, 'qu_submit_type', 'mmb') == "admin" ? "selected" : ""?>>관리자 완료 (자동)</option>
						</select>
						<span style="color:#888;">※ 자비란 경유: 퀘스트 수행 기능이 있는 자비란에서 수행 | 직접 완료: 퀘스트 페이지에서 버튼을 눌러 완료 | 관리자 완료: 멤버가 완료할 수 없고 관리자가 일괄 완료 처리만 가능</span>
					</td>
				</tr>

				
				<tr>
					<th scope="row">퀘스트 이름</th>
					<td colspan="2">
						<input type="text" name="qu_title" value="<?php echo get_text($quest['qu_title']) ?>" id="qu_title" required class="required" size="50" maxlength="120">
					</td>
				</tr>

				<tr>
					<th scope="row">퀘스트 내용</th>
					<td colspan="2">
						<input type="text" style="width:100%;" name="qu_content" value="<?php echo get_text($quest['qu_content']) ?>" id="qu_content_<?php echo $i ?>" size="20" placeholder="콘텐츠">
						<input type="text" style="width:100%;" name="qu_content2" value="<?php echo get_text($quest['qu_content2']) ?>" id="qu_content2_<?php echo $i ?>" size="20" placeholder="요구사항">
						<input type="text" style="width:100%;" name="qu_end_msg" value="<?php echo get_text($quest['qu_end_msg']) ?>" id="qu_end_msg_<?php echo $i ?>" size="20" placeholder="완수메시지">
					</td>
				</tr>
				<tr>
					<th scope="row">퀘스트 보상</th>
					<td colspan="2">
						<div style="margin-bottom: 5px;">
							<label>화폐: </label>
							<input type="text" name="qu_money" value="<?php echo (int)$quest['qu_money']; ?>" placeholder="화폐" size="10">
						</div>
						<div style="margin-bottom: 5px;">
							<label>경험치: </label>
							<input type="text" name="qu_exp" value="<?php echo (int)$quest['qu_exp']; ?>" placeholder="경험치" size="10">
						</div>
						<div style="margin-bottom: 5px;">
							<label>아이템: </label>
							<input type="hidden" name="it_id" id="it_id" value="<?php echo (int)$quest['it_id']; ?>">
							<input type="text" name="it_name" id="it_name" value="<?php echo $quest['it_id'] ? get_item_name($quest['it_id']) : ''; ?>" placeholder="아이템 이름 검색" onkeyup="get_ajax_item(this, 'item_list', 'it_id');" autocomplete="off">
							<span id="it_id_display" style="color:#888;"><?php echo $quest['it_id'] ? '(ID: '.$quest['it_id'].')' : ''; ?></span>
							<div id="item_list" class="ajax-list-box"><div class="list"></div></div>
						</div>
						<div style="margin-bottom: 5px;">
							<label>타이틀: </label>
							<input type="hidden" name="ti_id" id="ti_id" value="<?php echo (int)$quest['ti_id']; ?>">
							<input type="text" name="ti_name" id="ti_name" value="<?php echo $quest['ti_id'] ? get_title_name($quest['ti_id']) : ''; ?>" placeholder="타이틀 이름 검색" onkeyup="get_ajax_title(this, 'title_list', 'ti_id');" autocomplete="off">
							<span id="ti_id_display" style="color:#888;"><?php echo $quest['ti_id'] ? '(ID: '.$quest['ti_id'].')' : ''; ?></span>
							<div id="title_list" class="ajax-list-box"><div class="list"></div></div>
						</div>
					</td>
				</tr>

                <tr>
                    <th scope="row">인원제한</th>
                    <td colspan="2">
                        <input type="text" name="qu_take_max" value="<?php echo (int)$quest['qu_take_max']; ?>" size="4">명
                        <span style="color:#888;">0:제한없음</span>
                        <?php if($quest['qu_id']){
                            $qh_list = array();
                            $qh_sql = sql_query("SELECT ch.ch_name 
                                                 FROM {$g5['k_quest_has_table']} qh 
                                                 INNER JOIN {$g5['character_table']} ch ON qh.ch_id = ch.ch_id 
                                                 WHERE qh.qu_id = '{$quest['qu_id']}'");
                            while($row = sql_fetch_array($qh_sql)) {
                                $qh_list[] = $row;
                            }
                            if(count($qh_list) > 0){ ?>
                                <p>
                                    수행중 캐릭터 : 
                                    <?php foreach($qh_list as $qh_row) { 
                                        echo "<span style='margin: 0 2px;'>".h($qh_row['ch_name'])."</span>";
                                    } ?>
                                </p>
                            <?php }
                        } ?>
                    </td>
                </tr>
			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

</form>


<script>
$(function(){
	$(".date").datepicker({ changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99" });
});

function fitemform_submit(f)
{
	return true;
}

// 완료 타입 변경 시 요구 아이템 필드 표시/숨김
function toggleRequestItem() {
	var completeType = document.getElementById('qu_complete_type').value;
	var wrap = document.getElementById('request_item_wrap');
	if (completeType == 'item') {
		wrap.style.display = '';
	} else {
		wrap.style.display = 'none';
	}
}

// 아이템/타이틀 선택 시 ID 표시 업데이트를 위한 원본 함수 확장
var original_select_item = window.select_item;
window.select_item = function(list_obj, input_obj, it_name, output_obj, it_id) {
    if (typeof original_select_item === 'function') {
        original_select_item(list_obj, input_obj, it_name, output_obj, it_id);
    } else {
        $('#' + list_obj + ' .list').empty();
        $('#' + input_obj).val(it_name);
        $('#' + output_obj).val(it_id);
    }
    // ID 표시 업데이트
    if (output_obj === 'it_id') {
        $('#it_id_display').text(it_id ? '(ID: ' + it_id + ')' : '');
    } else if (output_obj === 'ti_id') {
        $('#ti_id_display').text(it_id ? '(ID: ' + it_id + ')' : '');
    } else if (output_obj === 'qu_request_item') {
        $('#qu_request_item_display').text(it_id ? '(ID: ' + it_id + ')' : '');
    }
};

// 아이템 이름 지우면 ID도 초기화
$('#it_name').on('input', function() {
    if ($(this).val() === '') {
        $('#it_id').val('');
        $('#it_id_display').text('');
    }
});

$('#ti_name').on('input', function() {
    if ($(this).val() === '') {
        $('#ti_id').val('');
        $('#ti_id_display').text('');
    }
});

$('#request_item_name').on('input', function() {
    if ($(this).val() === '') {
        $('#qu_request_item').val('');
        $('#qu_request_item_display').text('');
    }
});
</script>

<?php
include_once ('./admin.tail.php');
?>
