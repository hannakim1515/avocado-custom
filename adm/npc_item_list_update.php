<?php
$sub_menu = '400900';
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'd');
check_admin_token();

$count = count($_POST['chk']);
if(!$count)
	alert($_POST['act_button'].' 하실 항목을 하나 이상 체크하세요.');

for ($i=0; $i<count($_POST['chk']); $i++) {
	// 실제 번호를 넘김
	$k = $_POST['chk'][$i];
	$ni = sql_fetch("select * from {$g5['npc_item_table']} where ni_id = '{$_POST['ni_id'][$k]}'");
	
	if (!$ni['ni_id']) {
		$msg .= '자료가 존재하지 않습니다.\\n';
	} else {

		if ($_POST['act_button'] == "선택수정") {
			$it = sql_fetch("select it_id from {$g5['item_table']} where it_name = '{$_POST['it_name'][$k]}'");
			if($it['it_id'] == 0) {
				$it_id = '';
				$it_name = '';
			} else {
				$it_id = $it['it_id'];
				$it_name = $_POST['it_name'][$k];
			}

			$sql = " update {$g5['npc_item_table']}
						set it_id = '{$it_id}',
							it_name = '{$it_name}',
							ni_value = '{$_POST['ni_value'][$k]}',
							ni_max_count = '{$_POST['ni_max_count'][$k]}',
							ni_comment = '{$_POST['ni_comment'][$k]}',
							ni_max_comment = '{$_POST['ni_max_comment'][$k]}',
							ni_max_is_total = '{$_POST['ni_max_is_total'][$k]}'
					where ni_id = '{$ni['ni_id']}' ";
			sql_query($sql);
		} else if ($_POST['act_button'] == "선택삭제") {
			// 소지금 내역삭제
			$sql = " delete from {$g5['npc_item_table']} where ni_id = '{$_POST['ni_id'][$k]}' ";
			sql_query($sql);

		}
	}
}

goto_url('./npc_item_list.php?'.$qstr.'&amp;ns_id='.$ch_id);
?>