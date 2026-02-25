<?php
$sub_menu = "993100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// 1. 테이블 존재 여부 확인 (개선된 로직)
$table_check_sql = "SHOW TABLES LIKE '{$g5['k_quest_config_table']}'";
$table_result = sql_query($table_check_sql);
$table_exists = ($table_result && sql_num_rows($table_result) > 0);

// 2. 테이블이 없으면 자동 설치
if (!$table_exists) {
    // 직접 쿼리 실행 방식 (파일 파싱 대신)
    $queries = array();
    
    // k_quest 테이블
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$g5['k_quest_table']}` (
        `qu_id` int(11) NOT NULL AUTO_INCREMENT,
        `qu_type` enum('main','sub','member') NOT NULL DEFAULT 'sub',
        `qu_complete_type` enum('log','item') NOT NULL DEFAULT 'log',
        `qu_submit_type` enum('mmb','direct','admin') NOT NULL DEFAULT 'mmb',
        `qu_title` varchar(255) NOT NULL DEFAULT '',
        `qu_content` text,
        `qu_content2` text,
        `qu_end_msg` varchar(255) NOT NULL DEFAULT '',
        `qu_request_item` int(11) NOT NULL DEFAULT 0,
        `qu_request_item_count` int(11) NOT NULL DEFAULT 1,
        `qu_take_max` int(11) NOT NULL DEFAULT 0,
        `qu_take_now` int(11) NOT NULL DEFAULT 0,
        `qu_ch_id` int(11) NOT NULL DEFAULT 0,
        `qu_blind` tinyint(1) NOT NULL DEFAULT 0,
        `qu_money` int(11) NOT NULL DEFAULT 0,
        `qu_exp` int(11) NOT NULL DEFAULT 0,
        `it_id` int(11) NOT NULL DEFAULT 0,
        `ti_id` int(11) NOT NULL DEFAULT 0,
        `event_id` varchar(100) NOT NULL DEFAULT '',
        `event_week` varchar(100) NOT NULL DEFAULT '',
        `qu_state` enum('hidden','active','done') NOT NULL DEFAULT 'active',
        `qu_view` tinyint(1) NOT NULL DEFAULT 0,
        `qu_datetime` datetime DEFAULT NULL,
        PRIMARY KEY (`qu_id`),
        KEY `idx_qu_type` (`qu_type`),
        KEY `idx_qu_state` (`qu_state`),
        KEY `idx_event_id` (`event_id`),
        KEY `idx_qu_ch_id` (`qu_ch_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    // k_quest_has 테이블
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$g5['k_quest_has_table']}` (
        `qh_id` int(11) NOT NULL AUTO_INCREMENT,
        `qu_id` int(11) NOT NULL DEFAULT 0,
        `ch_id` int(11) NOT NULL DEFAULT 0,
        `qh_state` enum('수행중','완료','실패') NOT NULL DEFAULT '수행중',
        `qh_starttime` datetime DEFAULT NULL,
        `qh_endtime` datetime DEFAULT NULL,
        `qh_log` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`qh_id`),
        UNIQUE KEY `uk_qu_ch` (`qu_id`, `ch_id`),
        KEY `idx_ch_id` (`ch_id`),
        KEY `idx_qh_state` (`qh_state`),
        KEY `idx_qh_starttime` (`qh_starttime`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    // k_quest_config 테이블
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$g5['k_quest_config_table']}` (
        `qc_id` int(11) NOT NULL AUTO_INCREMENT,
        `qc_title` varchar(50) NOT NULL DEFAULT '퀘스트',
        `qc_register_reward_type` enum('exp','money','item') NOT NULL DEFAULT 'money',
        `qc_register_reward_value` int(11) NOT NULL DEFAULT 30,
        `qc_complete_reward_type` enum('exp','money','item') NOT NULL DEFAULT 'money',
        `qc_complete_reward_value` int(11) NOT NULL DEFAULT 5,
        `qc_quarter_receive_max` int(11) NOT NULL DEFAULT 3,
        `qc_quarter_give_max` int(11) NOT NULL DEFAULT 2,
        `qc_member_take_max` int(11) NOT NULL DEFAULT 3,
        `qc_last_reset_date` date DEFAULT NULL,
        `qc_reset_days` int(11) NOT NULL DEFAULT 7,
        `qc_main_display_count` int(11) NOT NULL DEFAULT 3,
        `qc_member_quest_enabled` tinyint(1) NOT NULL DEFAULT 1,
        `qc_member_quest_type` varchar(50) NOT NULL DEFAULT 'mmb,direct',
        `qc_main_color` varchar(20) NOT NULL DEFAULT '#d4a373',
        `qc_sub_color` varchar(20) NOT NULL DEFAULT '#8EACBB',
        `qc_member_color` varchar(20) NOT NULL DEFAULT '#509164',
        `qc_main_text_color` varchar(20) NOT NULL DEFAULT 'white',
        `qc_sub_text_color` varchar(20) NOT NULL DEFAULT 'white',
        `qc_member_text_color` varchar(20) NOT NULL DEFAULT 'white',
        PRIMARY KEY (`qc_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $queries[] = "ALTER TABLE {$g5['character_table']} ADD `ch_give_quest` VARCHAR(100) NOT NULL DEFAULT '0'";
    $queries[] = "ALTER TABLE {$g5['character_table']} ADD `ch_receive_quest` VARCHAR(100) NOT NULL DEFAULT '0'";
    $queries[] = "ALTER TABLE {$g5['inventory_table']} ADD `qu_id` INT(11) NOT NULL DEFAULT '0'";
    $queries[] = "CREATE TABLE {$g5['k_quest_inven_table']} LIKE {$g5['inventory_table']}";

    // 기본 설정 데이터 삽입
    $queries[] = "INSERT INTO `{$g5['k_quest_config_table']}` (
        `qc_title`, `qc_register_reward_type`, `qc_register_reward_value`,
        `qc_complete_reward_type`, `qc_complete_reward_value`,
        `qc_quarter_receive_max`, `qc_quarter_give_max`, `qc_member_take_max`,
        `qc_last_reset_date`, `qc_reset_days`, `qc_main_display_count`,
        `qc_member_quest_enabled`, `qc_member_quest_type`,
        `qc_main_color`, `qc_sub_color`, `qc_member_color`,
        `qc_main_text_color`, `qc_sub_text_color`, `qc_member_text_color`
    ) VALUES (
        '퀘스트', 'money', 30, 'money', 5, 3, 2, 3,
        CURDATE(), 7, 3, 1, 'mmb,direct',
        '#d4a373', '#8EACBB', '#509164', '#ffffff', '#ffffff', '#ffffff'
    )";
    
    // 쿼리 실행
    $success_count = 0;
    $error_count = 0;
    $error_details = array();
    
    foreach ($queries as $idx => $query) {
        $result = sql_query($query);
        if ($result) {
            $success_count++;
        } else {
            $error_count++;
            $mysql_error = mysqli_error($g5['connect_db']);
            $error_details[] = "[쿼리 " . ($idx + 1) . "] " . $mysql_error;
        }
    }
    
    // 설치 완료 후 테이블 재확인
    $verify_result = sql_query("SHOW TABLES LIKE '{$g5['k_quest_config_table']}'");
    $verify_exists = ($verify_result && sql_num_rows($verify_result) > 0);
    
    if ($verify_exists) {
        // 설치 성공 - 페이지 새로고침
        $msg = '퀘스트 시스템 데이터베이스가 설치되었습니다.\n\n';
        $msg .= '성공: ' . $success_count . '개';
        if ($error_count > 0) {
            $msg .= ' / 실패: ' . $error_count . '개\n\n';
            $msg .= '오류 상세:\n' . implode("\n", $error_details);
        }
        echo "<script>alert('" . addslashes(str_replace(array("\r\n", "\n", "\r"), "\\n", $msg)) . "'); location.href='./993_quest_config.php';</script>";
        exit;
    } else {
        // 설치 실패
        $error_msg = implode("\n", $error_details);
        if (empty($error_msg)) {
            $error_msg = '알 수 없는 오류';
        }
        $final_msg = '데이터베이스 설치 실패\n\nMySQL 오류:\n' . $error_msg . '\n\nphpMyAdmin에서 quest/database.sql 파일을 직접 실행해주세요.';
        alert(addslashes(str_replace(array("\r\n", "\n", "\r"), "\\n", $final_msg)));
    }
}

// 3. 정상 표시 - 퀘스트 설정 불러오기
$qu_cf = get_quest_config();

// 기본값 방어
$qu_cf['qc_title']                  = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
$qu_cf['qc_register_reward_type']   = ses($qu_cf, 'qc_register_reward_type', 'money', 'raw');
$qu_cf['qc_register_reward_value']  = ses($qu_cf, 'qc_register_reward_value', 30, 'int');
$qu_cf['qc_complete_reward_type']   = ses($qu_cf, 'qc_complete_reward_type', 'money', 'raw');
$qu_cf['qc_complete_reward_value']  = ses($qu_cf, 'qc_complete_reward_value', 5, 'int');
$qu_cf['qc_quarter_receive_max']    = ses($qu_cf, 'qc_quarter_receive_max', 3, 'int');
$qu_cf['qc_quarter_give_max']       = ses($qu_cf, 'qc_quarter_give_max', 2, 'int');
$qu_cf['qc_member_take_max']        = ses($qu_cf, 'qc_member_take_max', 3, 'int');
$qu_cf['qc_reset_days']             = ses($qu_cf, 'qc_reset_days', 7, 'int');
$qu_cf['qc_main_display_count']     = ses($qu_cf, 'qc_main_display_count', 3, 'int');
$qu_cf['qc_member_quest_enabled']   = ses($qu_cf, 'qc_member_quest_enabled', 1, 'int');
$qu_cf['qc_member_quest_type']      = ses($qu_cf, 'qc_member_quest_type', 'mmb,direct', 'raw');
$qu_cf['qc_last_reset_date']        = ses($qu_cf, 'qc_last_reset_date', '', 'raw');
$qu_cf['qc_main_color']             = ses($qu_cf, 'qc_main_color', '#d4a373', 'raw');
$qu_cf['qc_sub_color']              = ses($qu_cf, 'qc_sub_color', '#8EACBB', 'raw');
$qu_cf['qc_member_color']           = ses($qu_cf, 'qc_member_color', '#509164', 'raw');
$qu_cf['qc_main_text_color']        = ses($qu_cf, 'qc_main_text_color', 'white', 'raw');
$qu_cf['qc_sub_text_color']         = ses($qu_cf, 'qc_sub_text_color', 'white', 'raw');
$qu_cf['qc_member_text_color']      = ses($qu_cf, 'qc_member_text_color', 'white', 'raw');

// 멤버 퀘스트 허용 타입 배열로 변환
$qc_member_quest_type_arr = array_map('trim', explode(',', $qu_cf['qc_member_quest_type']));

// 화폐명
$money_name = ses($config, 'cf_money', '화폐', 'raw');

// 보상 아이템 이름 (등록 보상)
$register_item_name = '';
if ($qu_cf['qc_register_reward_type'] == 'item' && $qu_cf['qc_register_reward_value']) {
    $register_item_name = get_item_name($qu_cf['qc_register_reward_value']);
}

// 보상 아이템 이름 (완료 보상)
$complete_item_name = '';
if ($qu_cf['qc_complete_reward_type'] == 'item' && $qu_cf['qc_complete_reward_value']) {
    $complete_item_name = get_item_name($qu_cf['qc_complete_reward_value']);
}

$g5['title'] = '퀘스트 설정';
include_once('./admin.head.php');
?>

<style>
.quest_config_help { color: #888; font-size: 12px; margin-top: 3px; }
.reward_type_field { display: none; margin-top: 5px; }
.reward_type_field.active { display: block; }
</style>

<section id="anc_quest_config">
    <h2 class="h2_frm">퀘스트 설정</h2>
    <?php if (isset($pg_anchor)) echo $pg_anchor; ?>

    <form method="post" action="./993_quest_config_update.php" autocomplete="off">
        <input type="hidden" name="token" value="<?php echo $token; ?>">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col style="width:200px;">
                    <col>
                </colgroup>
                <tbody>
                
                <!-- 퀘스트 명칭 -->
                <tr>
                    <th scope="row">퀘스트 페이지</th>
                    <td>
                        <a href="<?php echo G5_URL?>/quest">바로가기</a>
                        <br>
                        메인 페이지에 퀘스트 리스트를 넣을 때에는 원하는 위치에 아래 코드를 넣어 주세요.
                        <pre style="background:#f5f5f5; padding:10px; border:1px solid #ddd; border-radius:4px; margin-top:5px;"><code>&lt;?php include(G5_PATH.'/quest/quest_main.inc.php')?&gt;</code></pre>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">퀘스트 명칭</th>
                    <td>
                        <input type="text" name="qc_title" value="<?php echo h($qu_cf['qc_title']); ?>"
                               class="frm_input" style="width:150px;" placeholder="퀘스트">
                        <p class="quest_config_help">"의뢰", "퀘스트", "임무" 등 원하는 명칭으로 설정</p>
                    </td>
                </tr>
                
                <!-- 멤버 퀘스트 사용 여부 -->
                <tr>
                    <th scope="row">멤버 <?php echo h($qu_cf['qc_title']); ?> 사용</th>
                    <td>
                        <label>
                            <input type="radio" name="qc_member_quest_enabled" value="1"
                                <?php echo $qu_cf['qc_member_quest_enabled'] ? 'checked' : ''; ?>>
                            사용
                        </label>
                        <label>
                            <input type="radio" name="qc_member_quest_enabled" value="0"
                                <?php echo !$qu_cf['qc_member_quest_enabled'] ? 'checked' : ''; ?>>
                            사용 안함
                        </label>
                        <p class="quest_config_help">멤버 <?php echo h($qu_cf['qc_title']); ?>(유저가 등록하는 퀘스트) 기능 사용 여부</p>
                    </td>
                </tr>
                
                <!-- 멤버 퀘스트 완료 방식 설정 -->
                <tr>
                    <th scope="row">멤버 <?php echo h($qu_cf['qc_title']); ?> 완료 방식</th>
                    <td>
                        <label style="margin-right:15px;">
                            <input type="checkbox" name="qc_member_quest_type[]" value="mmb"
                                <?php echo in_array('mmb', $qc_member_quest_type_arr) ? 'checked' : ''; ?>>
                            자비란 경유 (글 작성)
                        </label>
                        <label>
                            <input type="checkbox" name="qc_member_quest_type[]" value="direct"
                                <?php echo in_array('direct', $qc_member_quest_type_arr) ? 'checked' : ''; ?>>
                            직접 완료
                        </label>
                        <p class="quest_config_help">멤버가 <?php echo h($qu_cf['qc_title']); ?> 등록 시 선택할 수 있는 완료 방식 (최소 1개 선택 필수)</p>
                    </td>
                </tr>

                <!-- 멤버 의뢰 등록 보상 -->
                <tr>
                    <th scope="row">멤버 <?php echo h($qu_cf['qc_title']); ?> 등록 보상</th>
                    <td>
                        <select name="qc_register_reward_type" id="qc_register_reward_type">
                            <option value="money" <?php echo $qu_cf['qc_register_reward_type'] == 'money' ? 'selected' : ''; ?>><?php echo h($money_name); ?></option>
                            <option value="exp" <?php echo $qu_cf['qc_register_reward_type'] == 'exp' ? 'selected' : ''; ?>>경험치</option>
                            <option value="item" <?php echo $qu_cf['qc_register_reward_type'] == 'item' ? 'selected' : ''; ?>>아이템</option>
                        </select>
                        
                        <!-- 경험치/화폐 입력 -->
                        <div id="register_value_field" class="reward_type_field <?php echo in_array($qu_cf['qc_register_reward_type'], array('exp', 'money')) ? 'active' : ''; ?>">
                            <input type="number" name="qc_register_reward_value" id="qc_register_reward_value"
                                   value="<?php echo $qu_cf['qc_register_reward_type'] != 'item' ? $qu_cf['qc_register_reward_value'] : 30; ?>"
                                   class="frm_input" style="width:100px;" min="0">
                        </div>
                        
                        <!-- 아이템 검색 -->
                        <div id="register_item_field" class="reward_type_field <?php echo $qu_cf['qc_register_reward_type'] == 'item' ? 'active' : ''; ?>">
                            <input type="hidden" name="qc_register_reward_item_id" id="qc_register_reward_item_id"
                                   value="<?php echo $qu_cf['qc_register_reward_type'] == 'item' ? $qu_cf['qc_register_reward_value'] : ''; ?>">
                            <input type="text" id="register_item_search" class="frm_input" style="width:200px;"
                                   placeholder="아이템명 검색" autocomplete="off"
                                   value="<?php echo h($register_item_name); ?>"
                                   onkeyup="get_ajax_item(this, 'register_item_list', 'qc_register_reward_item_id');">
                            <div id="register_item_list" class="ajax-list-box"><div class="list"></div></div>
                            <span id="register_item_id_display" style="margin-left:5px; color:#666;">
                                <?php if ($qu_cf['qc_register_reward_type'] == 'item' && $qu_cf['qc_register_reward_value']) { ?>
                                (ID: <?php echo $qu_cf['qc_register_reward_value']; ?>)
                                <?php } ?>
                            </span>
                        </div>
                        
                        <p class="quest_config_help">멤버가 <?php echo h($qu_cf['qc_title']); ?>를 등록했을 때 지급할 보상</p>
                    </td>
                </tr>

                <!-- 멤버 의뢰 완료 보상 -->
                <tr>
                    <th scope="row">멤버 <?php echo h($qu_cf['qc_title']); ?> 완료 보상</th>
                    <td>
                        <select name="qc_complete_reward_type" id="qc_complete_reward_type">
                            <option value="money" <?php echo $qu_cf['qc_complete_reward_type'] == 'money' ? 'selected' : ''; ?>><?php echo h($money_name); ?></option>
                            <option value="exp" <?php echo $qu_cf['qc_complete_reward_type'] == 'exp' ? 'selected' : ''; ?>>경험치</option>
                            <option value="item" <?php echo $qu_cf['qc_complete_reward_type'] == 'item' ? 'selected' : ''; ?>>아이템</option>
                        </select>
                        
                        <!-- 경험치/화폐 입력 -->
                        <div id="complete_value_field" class="reward_type_field <?php echo in_array($qu_cf['qc_complete_reward_type'], array('exp', 'money')) ? 'active' : ''; ?>">
                            <input type="number" name="qc_complete_reward_value" id="qc_complete_reward_value"
                                   value="<?php echo $qu_cf['qc_complete_reward_type'] != 'item' ? $qu_cf['qc_complete_reward_value'] : 5; ?>"
                                   class="frm_input" style="width:100px;" min="0">
                        </div>
                        
                        <!-- 아이템 검색 -->
                        <div id="complete_item_field" class="reward_type_field <?php echo $qu_cf['qc_complete_reward_type'] == 'item' ? 'active' : ''; ?>">
                            <input type="hidden" name="qc_complete_reward_item_id" id="qc_complete_reward_item_id"
                                   value="<?php echo $qu_cf['qc_complete_reward_type'] == 'item' ? $qu_cf['qc_complete_reward_value'] : ''; ?>">
                            <input type="text" id="complete_item_search" class="frm_input" style="width:200px;"
                                   placeholder="아이템명 검색" autocomplete="off"
                                   value="<?php echo h($complete_item_name); ?>"
                                   onkeyup="get_ajax_item(this, 'complete_item_list', 'qc_complete_reward_item_id');">
                            <div id="complete_item_list" class="ajax-list-box"><div class="list"></div></div>
                            <span id="complete_item_id_display" style="margin-left:5px; color:#666;">
                                <?php if ($qu_cf['qc_complete_reward_type'] == 'item' && $qu_cf['qc_complete_reward_value']) { ?>
                                (ID: <?php echo $qu_cf['qc_complete_reward_value']; ?>)
                                <?php } ?>
                            </span>
                        </div>
                        
                        <p class="quest_config_help">멤버 <?php echo h($qu_cf['qc_title']); ?> 완료 시 지급할 기본 보상</p>
                    </td>
                </tr>

                <!-- 분기별 수행 제한 -->
                <tr>
                    <th scope="row">분기별 멤버 <?php echo h($qu_cf['qc_title']); ?> 수행 제한</th>
                    <td>
                        <input type="number" name="qc_quarter_receive_max"
                               value="<?php echo $qu_cf['qc_quarter_receive_max']; ?>"
                               class="frm_input" style="width:80px;" min="0"> 회
                        <p class="quest_config_help">한 분기에 캐릭터당 수행할 수 있는 멤버 <?php echo h($qu_cf['qc_title']); ?> 최대 수</p>
                    </td>
                </tr>

                <!-- 분기별 등록 제한 -->
                <tr>
                    <th scope="row">분기별 멤버 <?php echo h($qu_cf['qc_title']); ?> 등록 제한</th>
                    <td>
                        <input type="number" name="qc_quarter_give_max"
                               value="<?php echo $qu_cf['qc_quarter_give_max']; ?>"
                               class="frm_input" style="width:80px;" min="0"> 회
                        <p class="quest_config_help">한 분기에 캐릭터당 등록할 수 있는 멤버 <?php echo h($qu_cf['qc_title']); ?> 최대 수</p>
                    </td>
                </tr>

                <!-- 멤버 퀘스트 최대 수행 인원 -->
                <tr>
                    <th scope="row">멤버 <?php echo h($qu_cf['qc_title']); ?> 최대 수행 인원</th>
                    <td>
                        <input type="number" name="qc_member_take_max"
                               value="<?php echo $qu_cf['qc_member_take_max']; ?>"
                               class="frm_input" style="width:80px;" min="1"> 명
                        <p class="quest_config_help">멤버 <?php echo h($qu_cf['qc_title']); ?> 1건당 최대 수행 가능 인원 (기본값)</p>
                    </td>
                </tr>

                <!-- 리셋 주기 -->
                <tr>
                    <th scope="row">멤버 <?php echo h($qu_cf['qc_title']); ?> 리셋 주기</th>
                    <td>
                        <input type="number" name="qc_reset_days"
                               value="<?php echo $qu_cf['qc_reset_days']; ?>"
                               class="frm_input" style="width:80px;" min="1"> 일
                        <p class="quest_config_help">멤버 <?php echo h($qu_cf['qc_title']); ?> 받은 후 미완료시 자동 실패 처리되는 기간</p>
                    </td>
                </tr>

                <!-- 메인 페이지 출력 개수 -->
                <tr>
                    <th scope="row">메인 페이지 <?php echo h($qu_cf['qc_title']); ?> 표시</th>
                    <td>
                        <input type="number" name="qc_main_display_count"
                               value="<?php echo $qu_cf['qc_main_display_count']; ?>"
                               class="frm_input" style="width:80px;" min="0"> 개
                        <p class="quest_config_help">메인 페이지에 표시할 <?php echo h($qu_cf['qc_title']); ?> 개수</p>
                    </td>
                </tr>

                <!-- 마지막 리셋 날짜 (읽기 전용) -->
                <tr>
                    <th scope="row">마지막 리셋 날짜</th>
                    <td>
                        <?php echo $qu_cf['qc_last_reset_date'] ? $qu_cf['qc_last_reset_date'] : '(기록 없음)'; ?>
                        <p class="quest_config_help">시스템에서 자동으로 갱신됩니다</p>
                    </td>
                </tr>

                <!-- 색상 설정 -->
                <tr>
                    <th scope="row">메인 <?php echo h($qu_cf['qc_title']); ?> 색상</th>
                    <td>
                        배경: <input type="color" name="qc_main_color" value="<?php echo h($qu_cf['qc_main_color']); ?>" style="width:80px;height:30px;border:1px solid #ddd;">
                        텍스트: <input type="color" name="qc_main_text_color" value="<?php echo h($qu_cf['qc_main_text_color']); ?>" style="width:80px;height:30px;border:1px solid #ddd;margin-left:10px;">
                        <p class="quest_config_help">메인 <?php echo h($qu_cf['qc_title']); ?>의 배지 및 제목 배경색과 텍스트 색상</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">서브 <?php echo h($qu_cf['qc_title']); ?> 색상</th>
                    <td>
                        배경: <input type="color" name="qc_sub_color" value="<?php echo h($qu_cf['qc_sub_color']); ?>" style="width:80px;height:30px;border:1px solid #ddd;">
                        텍스트: <input type="color" name="qc_sub_text_color" value="<?php echo h($qu_cf['qc_sub_text_color']); ?>" style="width:80px;height:30px;border:1px solid #ddd;margin-left:10px;">
                        <p class="quest_config_help">서브 <?php echo h($qu_cf['qc_title']); ?>의 배지 및 제목 배경색과 텍스트 색상</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">멤버 <?php echo h($qu_cf['qc_title']); ?> 색상</th>
                    <td>
                        배경: <input type="color" name="qc_member_color" value="<?php echo h($qu_cf['qc_member_color']); ?>" style="width:80px;height:30px;border:1px solid #ddd;">
                        텍스트: <input type="color" name="qc_member_text_color" value="<?php echo h($qu_cf['qc_member_text_color']); ?>" style="width:80px;height:30px;border:1px solid #ddd;margin-left:10px;">
                        <p class="quest_config_help">멤버 <?php echo h($qu_cf['qc_title']); ?>의 배지 및 제목 배경색과 텍스트 색상</p>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>

        <div class="btn_confirm01 btn_confirm">
            <input type="submit" name="act_button" class="btn_frmline"  value="설정 저장">
        </div>
    </form>
</section>

<section id="anc_quest_quarter_reset">
    <h2 class="h2_frm">분기 종료</h2>

    <form method="post" action="./993_quest_config_update.php" onsubmit="return confirm('정말 분기를 종료하시겠습니까?\n\n모든 캐릭터의 멤버 <?php echo h($qu_cf['qc_title']); ?> 수행/등록 카운트가 0으로 초기화됩니다.');">
        <input type="hidden" name="token" value="<?php echo $token; ?>">
        <input type="hidden" name="act_button" value="분기종료">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col style="width:200px;">
                    <col>
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row">분기 종료</th>
                    <td>
                        <p>모든 캐릭터의 멤버 <?php echo h($qu_cf['qc_title']); ?> 수행/등록 카운트를 0으로 초기화합니다.</p>
                        <p class="quest_config_help" style="color:#c33;">이 작업은 되돌릴 수 없습니다. 분기가 변경될 때만 사용하세요.</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="btn_confirm01 btn_confirm">
            <input type="submit" value="분기 종료 실행" class="btn_frmline" style="background:#c33;">
        </div>
    </form>
</section>

<script>
(function() {
    // 등록 보상 타입
    var registerType = document.getElementById('qc_register_reward_type');
    var registerValueField = document.getElementById('register_value_field');
    var registerItemField = document.getElementById('register_item_field');
    
    registerType.addEventListener('change', function() {
        registerValueField.classList.remove('active');
        registerItemField.classList.remove('active');
        
        if (this.value === 'exp' || this.value === 'money') {
            registerValueField.classList.add('active');
        } else if (this.value === 'item') {
            registerItemField.classList.add('active');
        }
    });
    
    // 완료 보상 타입
    var completeType = document.getElementById('qc_complete_reward_type');
    var completeValueField = document.getElementById('complete_value_field');
    var completeItemField = document.getElementById('complete_item_field');
    
    completeType.addEventListener('change', function() {
        completeValueField.classList.remove('active');
        completeItemField.classList.remove('active');
        
        if (this.value === 'exp' || this.value === 'money') {
            completeValueField.classList.add('active');
        } else if (this.value === 'item') {
            completeItemField.classList.add('active');
        }
    });
})();
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
