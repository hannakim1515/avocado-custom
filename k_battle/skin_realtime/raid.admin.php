<?php
require_once './_common.php';
require_once G5_PATH.'/head.sub.php';

if(!$is_admin){
    goto_url(G5_URL."/k_battle/skill_realtime/raid.unit.php");
}

// 레이드 기본 정보
$ra = array();
$ra = sql_fetch("SELECT * FROM {$battle_table} WHERE {$ar_title} = '{$ar_value}'");

//기본값 처리
$ra_turn  = ses($ra, 'ra_turn', 0, 'int');
$ra_count = ses($ra, 'ra_count', 0, 'int');
$turn_type = ses($ra, 'ra_turn_type', 'speed');
$act      = ses($_REQUEST, 'act', '');

// 현재 턴 유닛 정보
$now_turn = ses($ra, 'now_turn', 0, 'int');
$rm = array();

// speed 타입이 아닌 경우 select_rm_id 파라미터로 유닛 선택 가능
$select_rm_id = ses($_REQUEST, 'select_rm_id', 0, 'int');
if ($turn_type !== 'speed' && $select_rm_id > 0) {
    // 선택된 유닛이 유효한지 확인 (tt_done=0, hp_now>0)
    $selected_rm = sql_fetch("
        SELECT * FROM {$battle_table}_unit 
        WHERE rm_id = '{$select_rm_id}' 
          AND ra_id = '{$ar_value}'
          AND hp_now > 0 
          AND tt_done = 0
    ");
    if (!empty($selected_rm['rm_id'])) {
        $rm = $selected_rm;
    }
}

// 선택된 유닛이 없으면 now_turn 사용
if (empty($rm['rm_id']) && $now_turn > 0) {
    $rm = sql_fetch("SELECT * FROM {$battle_table}_unit WHERE rm_id = '{$now_turn}'");
}

// 유닛 이름 조회
$unit_name = '';
if (!empty($rm['unit_type']) && !empty($rm['unit_id'])) {
    $unit_id_sql = (int)$rm['unit_id'];
    if ($rm['unit_type'] === 'mo') {
        $unit = sql_fetch("SELECT mo_name AS unit_name FROM {$g5['k_monster_table']} WHERE mo_id = '{$unit_id_sql}'");
    } else {
        $unit = sql_fetch("SELECT ch_name AS unit_name FROM {$g5['character_table']} WHERE ch_id = '{$unit_id_sql}'");
    }
    if (is_array($unit) && isset($unit['unit_name'])) {
        $unit_name = h($unit['unit_name']);
    }
}

$rm_id = ses($rm, 'rm_id', '');
?>

<style>
    @import url(<?php echo G5_URL?>/k_battle/css/raid.default.css);
    @import url(<?php echo G5_URL?>/k_battle/css/admin.css);
</style>


<div class='btns'>
    <a class='ui-btn'
    href="./raid.unit.php?<?php echo $raid_get ?>&type=ch">
    캐릭터
    </a>
    <a class='ui-btn'
    href="./raid.log.php?<?php echo $raid_get ?>">
    로그
    </a>
    <?php if ($is_admin){?>
        <a class='ui-btn point'
        href="./raid.admin.php?<?php echo $raid_get ?>">
        관리자
    </a>
    <?php }?>
</div>


<div class="admin-wrapper">
    <?php if (isset($ra['ra_state']) && (int)$ra['ra_state'] === 1): ?>
        <div class="admin-area">
            <p class="title">현재 턴 행동 : 
            <?php if ($turn_type === 'speed'): ?>
                <?php echo $unit_name; ?>
            <?php else: ?>
                <select id="unit-select" onchange="changeUnit(this.value);">
                    <option value="">-- 유닛 선택 --</option>
                    <?php
                    // tt_done이 아닌 캐릭터 목록
                    $ch_list = sql_query("
                        SELECT u.rm_id, u.unit_id, c.ch_name AS unit_name 
                        FROM {$battle_table}_unit u
                        JOIN {$g5['character_table']} c ON u.unit_id = c.ch_id
                        WHERE u.ra_id = '{$ar_value}' 
                          AND u.unit_type = 'ch'
                          AND u.hp_now > 0
                          AND u.tt_done = 0
                        ORDER BY c.ch_name
                    ");
                    while ($ch_row = sql_fetch_array($ch_list)) {
                        $selected = ((int)$ch_row['rm_id'] === (int)$rm_id) ? 'selected' : '';
                        echo '<option value="' . (int)$ch_row['rm_id'] . '" ' . $selected . '>[캐] ' . h($ch_row['unit_name']) . '</option>';
                    }
                    
                    // ra_mo_auto가 free일 때만 몬스터 목록 표시
                    $ra_mo_auto = ses($ra, 'ra_mo_auto', '');
                    if ($ra_mo_auto === 'free'):
                        $mo_list = sql_query("
                            SELECT u.rm_id, u.unit_id, m.mo_name AS unit_name 
                            FROM {$battle_table}_unit u
                            JOIN {$g5['k_monster_table']} m ON u.unit_id = m.mo_id
                            WHERE u.ra_id = '{$ar_value}' 
                              AND u.unit_type = 'mo'
                              AND u.hp_now > 0
                              AND u.tt_done = 0
                            ORDER BY m.mo_name
                        ");
                        while ($mo_row = sql_fetch_array($mo_list)) {
                            $selected = ((int)$mo_row['rm_id'] === (int)$rm_id) ? 'selected' : '';
                            echo '<option value="' . (int)$mo_row['rm_id'] . '" ' . $selected . '>[몬] ' . h($mo_row['unit_name']) . '</option>';
                        }
                    endif;
                    ?>
                </select>
            <?php endif; ?>
            </p>
            <?php include(G5_PATH."/k_battle/skin_default/action_select.php"); ?>
            <button class="ui-btn full" type="button" onclick="skip();">이번 턴 스킵</button>
        </div>
        <div class="admin-area">
            <p class="title">메시지 입력</p>
            <p>
                <input type="text" style="width:100%;" placeholder="시스템 메시지 입력" id="admin-msg">
                <button class="ui-btn full" type="button" onclick="messageInsert();">전송</button>
            </p>
        </div>
        <div class="admin-area">
            <p class="title">레이드 종료</p>
            <p class="btn-list">
                <button class="ui-btn" type="button" onclick="raidState('ch_win');">멤버승리</button>
                <button class="ui-btn" type="button" onclick="raidState('mo_win');">몬스터승리</button>
                <button class="ui-btn" type="button" onclick="raidState('end');">단순종료</button>
            </p>
        </div>
    <?php elseif (empty($ra['ra_state'])): ?>
        <div class="admin-area">
            <p class="title">레이드 시작</p>
            <button class="ui-btn full" type="button" onclick="raidState('start');">시작</button>
        </div>
    <?php endif; ?>

    <div class="admin-area">
        <p class="title">레이드 초기화</p>
        <p class="btn-list">
            <button class="ui-btn" type="button" onclick="raidReset('buff');">버프제거</button>
            <button class="ui-btn" type="button" onclick="raidReset('log');">로그제거</button>
            <button class="ui-btn" type="button" onclick="raidReset('all');">전체 초기화</button>
        </p>
    </div>
</div>

<script>
    var $get     = "?<?php echo $raid_get ?>&turn_type=<?php echo h($turn_type)?>";
    var $rm_id   = "<?php echo h($rm_id)?>";
    var $act     = "<?php echo h($act)?>";
    var $log     = true;
    var $lo_id   = "";
    var $ra_turn = <?php echo $ra_turn ?>;
    var $ra_count= <?php echo $ra_count ?>;
</script>

<script src="<?php echo G5_URL?>/k_battle/skin_realtime/admin.js"></script>
<script src="<?php echo G5_URL?>/k_battle/js/action.js"></script>

<?php require_once G5_PATH.'/tail.sub.php'; ?>
