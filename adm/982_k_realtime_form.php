<?php
$sub_menu = '930090';
include_once './_common.php';

include_once G5_EDITOR_LIB;

$html_title = '실시간 레이드';

$required  = '';
$readonly  = '';

if ($w === '') {

    $html_title .= ' 생성';

} elseif ($w === 'u') {

    $html_title .= ' 수정';
    $ra_id= isset($_REQUEST['ra_id']) ? $_REQUEST['ra_id'] : 0;
    $ra=sql_fetch (" SELECT * from {$g5['k_realtime_table']} where ra_id='{$ra_id}' ");
    if (empty($ra['ra_id'])) {
        alert('존재하지 않는 실시간 레이드 입니다.');
    }

    $readonly = 'readonly';
}

$g5['title'] = $html_title;
include_once './admin.head.php';
include_once './990_unified_menu_bootstrap.php';

// 하단 버튼 영역 생성
$fra_submit  = '<div class="btn_confirm01 btn_confirm">'.PHP_EOL;
$fra_submit .= '    <input type="submit" value="확인" class="btn_submit" accesskey="s">'.PHP_EOL;
$fra_submit .= '    <a href="./982_k_realtime_list.php?'.$qstr.'">목록</a>'.PHP_EOL;

if ($w === 'u') {
    $ra_id_esc = isset($ra_id) ? $ra_id : (isset($ra['ra_id']) ? $ra['ra_id'] : '');
    $fra_submit .= '    <a href="/k_realtime/?ra_id='.$ra['ra_id'].'" class="btn_frmline">레이드 바로가기</a>'.PHP_EOL;
}
$fra_submit .= '</div>';


?>
<form action="./982_k_realtime_form_update.php" onsubmit="return f_submit(this)" method="post" enctype="multipart/form-data">
    <input type="hidden" name="w" value="<?php echo $w; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx" value="<?php echo $stx; ?>">
    <input type="hidden" name="sst" value="<?php echo $sst; ?>">
    <input type="hidden" name="sod" value="<?php echo $sod; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">

    <h2 class="h2_frm">실시간 레이드 기본 설정</h2>

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>실시간 레이드 기본 설정</caption>
            <colgroup>
                <col style="width:150px;">
                <col>
                <col style="width:180px;">
            </colgroup>
            <tbody>
            <tr>
                <th scope="row">
                    <label for="ra_id">
                        TABLE<?php echo isset($sound_only) ? $sound_only : ''; ?>
                    </label>
                </th>
                <td colspan="2">
                    <input type="text"
                           name="ra_id"
                           value="<?php echo isset($ra['ra_id']) ? $ra['ra_id'] : ''; ?>"
                           id="ra_id"
                           <?php echo $required; ?>
                           <?php echo $readonly; ?>
                           class="fra_input <?php echo $readonly ? 'readonly' : ''; ?> <?php echo $required; ?> <?php echo isset($required_valid) ? $required_valid : ''; ?>"
                           maxlength="20">
                    <?php if ($w === '') { ?>
                        영문자, 숫자, _ 만 가능 (공백없이 20자 이내)
                    <?php } else { ?>
                        <a href="<?php echo G5_URL?>/k_battle/raid.php?ra_id=<?php echo $ra['ra_id']; ?>&raid_type=realtime" class="btn_frmline">레이드 바로가기</a>
                        <a href="./982_k_realtime_list.php" class="btn_frmline">목록으로</a>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th scope="row">레이드 제목</th>
                <td colspan="2">
                    <input type="text"
                           name="ra_title"
                           value="<?php echo isset($ra['ra_title']) ? get_text($ra['ra_title']) : ''; ?>"
                           id="ra_title"
                           class="required fra_input"
                           size="80"
                           maxlength="120">
                </td>
            </tr>
            <tr>
                <th scope="row">레이드 설명</th>
                <td colspan="2">
                    <input type="text"
                           name="ra_content"
                           value="<?php echo isset($ra['ra_content']) ? get_text($ra['ra_content']) : ''; ?>"
                           id="ra_content"
                           class="fra_input"
                           size="80"
                           maxlength="225">
                </td>
            </tr>
            <!--
            <tr>
                <th scope="row">레이드 유형</th>
                <td colspan="2">
                    <?php $ra_type = isset($ra['ra_type']) ? $ra['ra_type'] : 'pve'; ?>
                    <select name="ra_type">
                        <option value="pve"<?php echo $ra_type === 'pve' ? ' selected' : ''; ?>>pve</option>
                        <option value="pvp"<?php echo $ra_type === 'pvp' ? ' selected' : ''; ?>>pvp</option>
                    </select>
                </td>
            </tr>
            -->
            <tr>
                <th scope="row">참가인원</th>
                <td colspan="2">
                    최대
                    <input type="text"
                           name="ra_limit"
                           value="<?php echo isset($ra['ra_limit']) ? get_text($ra['ra_limit']) : '5'; ?>"
                           id="ra_limit"
                           class="fra_input"
                           size="5">인
                </td>
            </tr>
            <tr>
                <th scope="row">턴 변경 타입</th>
                <td colspan="2">
                    <?php $ra_turn_type = isset($ra['ra_turn_type']) ? $ra['ra_turn_type'] : 'speed'; ?>
                    <select name="ra_turn_type" id="ra_turn_type">
                        <option value="speed"<?php echo $ra_turn_type === 'speed' ? ' selected' : ''; ?>>speed (속도순 한바퀴)</option>
                        <option value="free"<?php echo $ra_turn_type === 'free' ? ' selected' : ''; ?>>free (자유행동)</option>
                        <option value="turn"<?php echo $ra_turn_type === 'turn' ? ' selected' : ''; ?>>turn (턴 진행)</option>
                    </select>
                    <br>speed: 속도에 따라 한바퀴 돔 / free: 자유행동, 턴 진행될수록 몬스터 행동 / turn: 캐릭터 순서 상관 없이 1회씩 행동 후 몬스터 공격
                </td>
            </tr>
            <tr>
                <th scope="row">몬스터 공격 타입</th>
                <td colspan="2">
                    <?php $ra_mo_auto = isset($ra['ra_mo_auto']) ? $ra['ra_mo_auto'] : 'auto'; ?>
                    <select name="ra_mo_auto" id="ra_mo_auto">
                        <option value="auto"<?php echo $ra_mo_auto === 'auto' ? ' selected' : ''; ?>>auto (자동 - 패턴 따라 행동)</option>
                        <option value="free"<?php echo $ra_mo_auto === 'free' ? ' selected' : ''; ?>>free (수동 - 어드민에서 직접)</option>
                    </select>
                    <br>auto: 미리 등록된 스킬/패턴 따라 자동 행동 / free: 어드민 메뉴에서 직접 입력
                </td>
            </tr>
            <tr>
                <th scope="row">새로고침 타입</th>
                <td colspan="2">
                    <?php $ra_reload = isset($ra['ra_reload']) ? $ra['ra_reload'] : 'turn'; ?>
                    <select name="ra_reload" id="ra_reload">
                        <option value="turn"<?php echo $ra_reload === 'turn' ? ' selected' : ''; ?>>turn (턴 변경 시)</option>
                        <option value="count"<?php echo $ra_reload === 'count' ? ' selected' : ''; ?>>count (모든 행동시)</option>
                        <option value="myturn"<?php echo $ra_reload === 'myturn' ? ' selected' : ''; ?>>myturn (내 턴일 때)</option>
                        <option value="none"<?php echo $ra_reload === 'none' ? ' selected' : ''; ?>>none (새로고침 안함)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">새로고침 간격</th>
                <td colspan="2">
                    <input type="text"
                           name="ra_reload_time"
                           value="<?php echo isset($ra['ra_reload_time']) ? (int)$ra['ra_reload_time'] : '5'; ?>"
                           id="ra_reload_time"
                           class="fra_input"
                           size="5">초
                    <br>AJAX 폴링 간격 (초 단위)
                </td>
            </tr>
            <tr>
                <th scope="row">행동 제한시간</th>
                <td colspan="2">
                    <input type="text"
                           name="ra_time_limit"
                           value="<?php echo isset($ra['ra_time_limit']) ? (int)$ra['ra_time_limit'] : '0'; ?>"
                           id="ra_time_limit"
                           class="fra_input"
                           size="5">초
                    <br>0이면 무제한. speed: 각 유닛 행동마다 / turn: 각 턴마다 제한시간 적용
                </td>
            </tr>
            <!--
            <tr>
                <th scope="row">시스템 타입</th>
                <td colspan="2">
                    <?php $ra_system = isset($ra['ra_system']) ? $ra['ra_system'] : 'normal'; ?>
                    <select name="ra_system" id="ra_system">
                        <option value="normal"<?php echo $ra_system === 'normal' ? ' selected' : ''; ?>>normal (일반 폴링)</option>
                        <option value="pusher"<?php echo $ra_system === 'pusher' ? ' selected' : ''; ?>>pusher (실시간 푸시)</option>
                    </select>
                </td>
            </tr>
            -->
            <tr>
                <th scope="row" colspan="3"><strong>이미지 설정</strong></th>
            </tr>
            <tr>
                <th scope="row">목록 이미지</th>
                <td colspan="2">
                    <?php if (!empty($ra['ra_list_img'])): ?>
                    <p style="margin-bottom:5px;">
                        현재: <a href="<?php echo h($ra['ra_list_img']); ?>" target="_blank"><?php echo h($ra['ra_list_img']); ?></a>
                    </p>
                    <?php endif; ?>
                    <input type="file"
                           name="ra_list_img_file"
                           accept="image/*"
                           class="fra_input">
                    <br>
                    <input type="text"
                           name="ra_list_img"
                           value="<?php echo isset($ra['ra_list_img']) ? get_text($ra['ra_list_img']) : ''; ?>"
                           id="ra_list_img"
                           class="fra_input"
                           size="80"
                           placeholder="또는 URL 직접 입력">
                    <br>실시간 레이드 목록에 표시될 썸네일 이미지 (파일 업로드 또는 URL)
                </td>
            </tr>
            <tr>
                <th scope="row">배경 이미지</th>
                <td colspan="2">
                    <?php if (!empty($ra['ra_bg_img'])): ?>
                    <p style="margin-bottom:5px;">
                        현재: <a href="<?php echo h($ra['ra_bg_img']); ?>" target="_blank"><?php echo h($ra['ra_bg_img']); ?></a>
                    </p>
                    <?php endif; ?>
                    <input type="file"
                           name="ra_bg_img_file"
                           accept="image/*"
                           class="fra_input">
                    <br>
                    <input type="text"
                           name="ra_bg_img"
                           value="<?php echo isset($ra['ra_bg_img']) ? get_text($ra['ra_bg_img']) : ''; ?>"
                           id="ra_bg_img"
                           class="fra_input"
                           size="80"
                           placeholder="또는 URL 직접 입력">
                    <br>레이드 배경 이미지 (파일 업로드 또는 URL)
                </td>
            </tr>
            <tr>
                <th scope="row" colspan="3"><strong>배경음악 설정</strong></th>
            </tr>
            <tr>
                <th scope="row">BGM 타입</th>
                <td colspan="2">
                    <?php $ra_bgm_type = isset($ra['ra_bgm_type']) ? $ra['ra_bgm_type'] : 'single'; ?>
                    <select name="ra_bgm_type" id="ra_bgm_type">
                        <option value="single"<?php echo $ra_bgm_type === 'single' ? ' selected' : ''; ?>>영상 - 단일 영상 반복 재생</option>
                        <option value="list"<?php echo $ra_bgm_type === 'list' ? ' selected' : ''; ?>>재생목록 - 플레이리스트 순차 재생</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">BGM ID</th>
                <td colspan="2">
                    <input type="text"
                           name="ra_bgm"
                           value="<?php echo isset($ra['ra_bgm']) ? get_text($ra['ra_bgm']) : ''; ?>"
                           id="ra_bgm"
                           class="fra_input"
                           size="50"
                           placeholder="YouTube 영상 ID 또는 재생목록 ID">
                </td>
            </tr>
            <tr>
                <th scope="row">BGM 볼륨</th>
                <td colspan="2">
                    <input type="number"
                           name="ra_bgm_volume"
                           value="<?php echo isset($ra['ra_bgm_volume']) ? (int)$ra['ra_bgm_volume'] : '30'; ?>"
                           id="ra_bgm_volume"
                           class="fra_input"
                           size="5"
                           min="0"
                           max="100"
                           style="width:80px;"> %
                    <br>0~100 사이 값 (기본값: 30)
                </td>
            </tr>
            <tr>
                <th scope="row" colspan="3"><strong>보상 설정</strong> (레이드 종료 시 참가자에게 지급)</th>
            </tr>
            <tr>
                <th scope="row">화폐 보상</th>
                <td colspan="2">
                    <input type="text"
                           name="ra_reward_money"
                           value="<?php echo isset($ra['ra_reward_money']) ? (int)$ra['ra_reward_money'] : '0'; ?>"
                           id="ra_reward_money"
                           class="fra_input"
                           size="10"> 포인트
                </td>
            </tr>
            <tr>
                <th scope="row">경험치 보상</th>
                <td colspan="2">
                    <input type="text"
                           name="ra_reward_exp"
                           value="<?php echo isset($ra['ra_reward_exp']) ? (int)$ra['ra_reward_exp'] : '0'; ?>"
                           id="ra_reward_exp"
                           class="fra_input"
                           size="10"> EXP
                </td>
            </tr>
            <tr>
                <th scope="row">아이템 보상</th>
                <td colspan="2">
                    <input type="hidden" name="ra_reward_item" id="ra_reward_item" value="<?php echo isset($ra['ra_reward_item']) ? get_text($ra['ra_reward_item']) : ''; ?>">
                    <input type="text"
                           name="ra_reward_item_name"
                           id="ra_reward_item_name"
                           value="<?php 
                               $item_id = isset($ra['ra_reward_item']) ? trim($ra['ra_reward_item']) : '';
                               if ($item_id !== '' && function_exists('get_item_name')) {
                                   $item_ids = explode(',', $item_id);
                                   $item_names = array();
                                   foreach ($item_ids as $it_id) {
                                       $it_id = (int)trim($it_id);
                                       if ($it_id > 0) {
                                           $item_names[] = get_item_name($it_id);
                                       }
                                   }
                                   echo h(implode(', ', $item_names));
                               }
                           ?>"
                           class="fra_input"
                           size="30"
                           placeholder="아이템 이름 검색"
                           onkeyup="get_ajax_item(this, 'ra_item_list', 'ra_reward_item');"
                           autocomplete="off">
                    <span id="ra_reward_item_display" style="color:#888;"><?php echo $item_id !== '' ? '(ID: '.$item_id.')' : ''; ?></span>
                    <div id="ra_item_list" class="ajax-list-box"><div class="list"></div></div>
                    <br>아이템 이름을 검색하여 선택하거나, ID를 직접 입력 (여러 개일 경우 쉼표로 구분: 1,2,3)
                </td>
            </tr>
            <tr>
                <th scope="row">칭호 보상</th>
                <td colspan="2">
                    <?php 
                        $title_id = isset($ra['ra_reward_title']) ? (int)$ra['ra_reward_title'] : 0;
                        $title_name = '';
                        if ($title_id > 0) {
                            // title.lib.php의 get_title_name() 사용 (ti_title 필드 반환)
                            $title_name = function_exists('get_title_name') ? get_title_name($title_id) : '';
                        }
                    ?>
                    <input type="hidden" name="ra_reward_title" id="ra_reward_title" value="<?php echo $title_id; ?>">
                    <input type="text"
                           name="ra_reward_title_name"
                           id="ra_reward_title_name"
                           value="<?php echo h($title_name); ?>"
                           class="fra_input"
                           size="30"
                           placeholder="칭호 이름 검색"
                           onkeyup="get_ajax_title(this, 'ra_title_list', 'ra_reward_title');"
                           autocomplete="off">
                    <span id="ra_reward_title_display" style="color:#888;"><?php echo $title_id > 0 ? '(ID: '.$title_id.')' : ''; ?></span>
                    <div id="ra_title_list" class="ajax-list-box"><div class="list"></div></div>
                    <br>칭호 이름을 검색하여 선택
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <?php echo $fra_submit; ?>

</form>

<script>
    $(function () {
        $("#k_realtime_copy").click(function () {
            window.open(this.href, "win_k_realtime_copy", "left=10,top=10,width=500,height=400");
            return false;
        });
    });

    function k_realtime_copy(ra_id) {
        window.open("./982_k_realtime_copy.php?ra_id=" + ra_id, "k_realtimeCopy", "left=10,top=10,width=500,height=200");
    }

    function f_submit(f) {
        return true;
    }

    function f_list_submit(f) {
        if (!is_checked("chk[]")) {
            alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
            return false;
        }

        if (document.pressed === "선택삭제") {
            if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
                return false;
            }
        }

        return true;
    }
    
    // 아이템/칭호 선택 시 ID 표시 업데이트
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
        if (output_obj === 'ra_reward_item') {
            $('#ra_reward_item_display').text(it_id ? '(ID: ' + it_id + ')' : '');
        } else if (output_obj === 'ra_reward_title') {
            $('#ra_reward_title_display').text(it_id ? '(ID: ' + it_id + ')' : '');
        }
    };
    
    // 아이템 이름 지우면 ID도 초기화
    $('#ra_reward_item_name').on('input', function() {
        if ($(this).val() === '') {
            $('#ra_reward_item').val('');
            $('#ra_reward_item_display').text('');
        }
    });
    
    $('#ra_reward_title_name').on('input', function() {
        if ($(this).val() === '') {
            $('#ra_reward_title').val('');
            $('#ra_reward_title_display').text('');
        }
    });
</script>

<?php
include_once './admin.tail.php';
?>
