<?php
include_once('./_common.php');

$ch = get_character($ch_id);

if($url) { 
	$return_url = urldecode($url);
} else { 
	$return_url = "./viewer.php?ch_id=".$ch_id;
}

if(!$ch['ch_id']) { 
	alert('캐릭터 정보가 존재하지 않습니다.');
}

$character_image_path = G5_DATA_PATH."/character/".$ch['mb_id'];
$character_image_url = G5_DATA_URL."/character/".$ch['mb_id'];

@mkdir($character_image_path, G5_DIR_PERMISSION);
@chmod($character_image_path, G5_DIR_PERMISSION);

$in = sql_fetch("select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.in_id = '{$in_id}' and inven.it_id = item.it_id and inven.ch_id = '{$ch_id}'");
$it_id = $in['it_id'];

if($in['it_type'] != '마이룸커스텀가구') { 
	alert('올바른 아이템 정보가 아닙니다.');
}

/** 하우징 등록 **/
$sql_common = "";
if ($img = $_FILES['ro_img']['name']) {
	if (!preg_match("/\.(gif|jpg|png)$/i", $img)) {
		alert("하우징 이미지가 gif, jpg, png 파일이 아닙니다.");
	} else {
		// 확장자 따기
		$exp = explode(".", $_FILES['ro_img']['name']);
		$exp = $exp[count($exp)-1];

		$image_name = "room_".time().".".$exp;
		upload_file($_FILES['ro_img']['tmp_name'], $image_name, $character_image_path);

		$img_path = $character_image_path."/".$image_name;

		if (file_exists($img_path)) {
			$size = getimagesize($img_path);
			$able_side = explode("x", $in['it_value']);
			if(count($able_side) < 2) {
				$able_side = explode("X", $in['it_value']);
			}

			if ($size[0] > $able_side[0] || $size[1] > $able_side[1]) {
				@unlink($img_path);
				alert("등록 가능한 사이즈를 초과하였습니다.");
			} else {
				$room_img = $character_image_url."/".$image_name;
				$count = sql_fetch("select MAX(ro_id) as cnt from {$g5['room_table']} where ch_id = '{$ch_id}'");
				$count = $count['cnt']++;
				sql_query("insert into {$g5['room_table']} set ro_img='{$room_img}', ro_order='{$count}', ch_id = '{$ch_id}'");

				// 아이템 삭제
				delete_inventory($in['in_id'], $in['it_use_ever']);
			}
		}
	}
} else {
	alert("이미지가 제대로 넘어오지 않았습니다.");
}

goto_url($return_url, false);
?>
