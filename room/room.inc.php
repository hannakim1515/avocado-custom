<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
include_once('./_config.php');

add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/room/css/style.css">', 0);

/**
 * [수정] 페어 시스템 연동 로직
 */
// 1. 현재 접속한 내 캐릭터의 페어 정보 확인
$my_pair = sql_fetch(" select co_id from {$g5['couple_table']} where co_left = '{$character['ch_id']}' or co_right = '{$character['ch_id']}' ");
$my_room_key = $my_pair['co_id'] ? "pair_".$my_pair['co_id'] : "solo_".$character['ch_id'];

// 2. 현재 방문한 방(ch_id) 캐릭터의 페어 정보 확인
$view_pair = sql_fetch(" select co_id from {$g5['couple_table']} where co_left = '{$ch_id}' or co_right = '{$ch_id}' ");
$view_room_key = $view_pair['co_id'] ? "pair_".$view_pair['co_id'] : "solo_".$ch_id;

// 3. 관리 권한: 내 페어 키와 방문한 방의 페어 키가 같으면 주인으로 인정
$is_mine = ($my_room_key == $view_room_key) ? true : false;

// 캐릭터 마이룸 배경 설정 가져오기 (배경은 방 주인 캐릭터의 설정을 따름)
if(!$ch['ch_room_bak']) {
    $ch = sql_fetch("select ch_id, ch_name, ch_room_bak from {$g5['character_table']} where ch_id = '{$ch_id}'");
}

// [수정] 이미지 리스트 가져오기 (ch_id 대신 페어/솔로 고유 키인 view_room_key 기준)
// 주의: DB의 컬럼명이 pair_id로 변경되어 있어야 합니다. (기존 ch_id 컬럼을 그대로 쓰신다면 pair_id를 ch_id로 바꾸세요)
$sql = sql_query("select * from {$g5['room_table']} where pair_id = '{$view_room_key}' order by ro_order");
$room_config = array();

$room = array();
for($i=0; $row = sql_fetch_array($sql); $i++) { $room[] = $row; }
?>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<div class="room-movie-link">
    <select onchange="location.href='?ch_id='+this.value;" style="border-radius:9em; border-color:#000; height:25px;">
        <option value="<?=$character['ch_id']?>">친구방 이동</option>
        <?
            $relation_list = "select ch.ch_id, ch.ch_name from {$g5['relation_table']} rel, {$g5['character_table']} ch where rel.ch_id='{$character['ch_id']}' and rel.re_ch_id = ch.ch_id group by rel.re_ch_id order by rel.rm_order asc, rel.rm_id desc";
            $relation = sql_query($relation_list);
            for($i=0; $row = sql_fetch_array($relation); $i++) { 
        ?>
            <option value="<?=$row['ch_id']?>" <? if($ch_id == $row['ch_id']) { ?>selected<? } ?>><?=$row['ch_name']?></option>
        <? } ?>
    </select>
</div>

<div class="room-pannel">
    <div class="roomWrap" style="background-image:url(<?=$ch['ch_room_bak']?>);">
        <? if($is_mine) { ?>
            <div class="objList trans open theme-box">
                <button type="button" class="close theme-box" onclick="$('.objList').toggleClass('open');"></button>
                <div class="inner">
                    <ul id="obj_list">
                        <? 
                            for($i=0; $i < count($room); $i++) {
                                $ro = $room[$i];
                        ?>
                            <li>
                                <div class="item" data-idx="<?=$ro['ro_id']?>">
                                    <em><img src="<?=$ro['ro_img']?>" /></em>
                                    <div class="control">
                                        <button type="button" onclick="fn_room_dir(this, 'prev');" data-idx="<?=$ro['ro_id']?>">
                                            ▲
                                        </button>
                                        <button type="button" onclick="fn_room_dir(this, 'next');" data-idx="<?=$ro['ro_id']?>">
                                            ▼
                                        </button>
                                        <button type="button" onclick="fn_room_del(this);" class="del" data-idx="<?=$ro['ro_id']?>">
                                            삭제
                                        </button>
                                    </div>
                                </div>
                            </li>
                        <? } ?>
                    </ul>
                </div>
            </div>
        <? } ?>

        <div class="objCanvas">
            <div id="room_area" class="none-trans" style="width:<?=$room_w?>px; height:<?=$room_h?>px;">
                <? 
                    for($i=0; $i < count($room); $i++) {
                        $ro = $room[$i];
                ?>
                    <div data-idx="<?=$ro['ro_id']?>" style="top:<?=$ro['ro_top']?>px; left:<?=$ro['ro_left']?>px; z-index:<?=$ro['ro_order']?>" class="obj draggable">
                        <img src="<?=$ro['ro_img']?>" />
                    </div>
                <? } ?>
            </div>
        </div>
    </div>
</div>

<? if($is_mine) { ?>

    <script>
    $('#obj_list .item').on('mouseenter', function() {
        var idx = $(this).attr('data-idx');
        $('#room_area').addClass('over');
        $('#room_area .obj').removeClass('over');
        $('#room_area .obj[data-idx="'+idx+'"]').addClass('over');
    }).on('mouseleave', function() {
        $('#room_area').removeClass('over');
        $('#room_area .obj').removeClass('over');
    });

    $(".draggable" ).each(function() {
        $obj = $(this);
        $obj.draggable({
            stop:function(event, ui) {
                var idx = $(event.target).attr('data-idx');
                var top = ui.position.top;
                var left = ui.position.left;

                if(top < 0) { top = 0; }
                if(left < 0) { left = 0; }
                if(top > (<?=$room_h?>-10)) { top = (<?=$room_h?>-10); }
                if(left > (<?=$room_w?>-10)) { left = (<?=$room_w?>-10); }

                $(event.target).css('top', top +  "px");
                $(event.target).css('left', left +  "px");

                var sendData = {ro_id:idx, ro_top:top, ro_left:left};
                var url = g5_url + "/room/room_obj_update.php";

                $.ajax({
                    type: 'post'
                    , url : url
                    , data: sendData
                    , success : function(data) {}
                });
            }
        });
    });


    function fn_room_del(obj) {
        var idx = $(obj).attr('data-idx');
        var url = g5_url + "/room/room_obj_delete.php";
        var sendData = {ro_id:idx};
        $.ajax({
            type: 'post'
            , url : url
            , data: sendData
            , success : function(data) {
                if(data == 'Y') {
                    $('#obj_list .item[data-idx="'+idx+'"]').remove();
                    $('#room_area .obj[data-idx="'+idx+'"]').remove();
                }
            }
        });
    }

    function fn_room_dir(obj, dir) {
        var idx = $(obj).attr('data-idx');
        var url = g5_url + "/room/room_obj_dir.php";
        // ch_id 대신 페어 식별자 view_room_key를 전달하도록 수정 가능하지만, 
        // 기존 PHP 파일 호환성을 위해 ch_id를 그대로 보냅니다.
        var sendData = {ro_id:idx, dir:dir, ch_id: '<?=$ch_id?>'}; 
        $.ajax({
            type: 'post'
            , url : url
            , data: sendData
            , dataType:"json"
            , success : function(data) {
                if(data.my) {
                    var li = $('#obj_list .item[data-idx="'+idx+'"]').closest('li');
                    if(dir == 'next') {
                        li.next().after(li);
                    } else {
                        li.prev().before(li);
                    }
                    $('#room_area .obj[data-idx="'+data.other+'"]').css('z-index', data.other_order);
                    $('#room_area .obj[data-idx="'+data.my+'"]').css('z-index', data.my_order);
                }
            }
        });
    }
    </script>
<? } ?>