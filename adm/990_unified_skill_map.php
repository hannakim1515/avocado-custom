<?php
$sub_menu = '990000';
include_once('./_common.php');
auth_check($auth[$sub_menu], 'r');

$status_types = array_values(array_filter(explode('||', isset($config['cf_status_select_type']) ? $config['cf_status_select_type'] : '')));
$status_rows = array();
$status_result = sql_query("SELECT st_id, st_name, st_use_hp FROM {$g5['status_config_table']} ORDER BY st_order ASC, st_id ASC", false);
if ($status_result) while ($row = sql_fetch_array($status_result)) $status_rows[] = $row;

$hp_st_id = function_exists('unified_stat_hp_id') ? unified_stat_hp_id() : 0;
$raid_config = sql_fetch("SELECT hp, mp, speed FROM {$g5['k_battle_config']} LIMIT 1", false);
if ($hp_st_id <= 0) $hp_st_id = (int)ses($raid_config, 'hp', 0, 'int');
$mp_st_id = (int)ses($raid_config, 'mp', 0, 'int');
$slot_min = max(0, (int)ses($config, 'cf_skill_count', 0, 'int'));
$slot_max = max($slot_min, (int)ses($config, 'cf_skill_count_max', 0, 'int'));
$unified_action_config = function_exists('unified_combat_config') ? unified_combat_config() : array();
$speed_status_type = ses($unified_action_config, 'speed_status_type', '');
$extra_codes = array();
$extra_result = sql_query("SELECT ex_name FROM {$g5['status_extra_table']} WHERE ex_name <> '' ORDER BY ex_name ASC", false);
if ($extra_result) while ($row = sql_fetch_array($extra_result)) $extra_codes[] = $row['ex_name'];

$stat_map_table = isset($g5['unified_combat_stat_map_table']) ? $g5['unified_combat_stat_map_table'] : G5_TABLE_PREFIX.'unified_combat_stat_map';
$k_stat_table = isset($g5['k_stat_table']) ? $g5['k_stat_table'] : G5_TABLE_PREFIX.'k_battle_stat_func';
$stat_maps = false;
$k_stats = false;
if (function_exists('unified_table_exists') && unified_table_exists($stat_map_table) && unified_table_exists($k_stat_table)) {
    $stat_maps = sql_query("SELECT um.*, ks.sc_name FROM {$stat_map_table} um LEFT JOIN {$k_stat_table} ks ON ks.sc_id = um.k_sc_id ORDER BY ks.sc_name ASC, um.map_id ASC", false);
    $k_stats = sql_query("SELECT sc_id, sc_name FROM {$k_stat_table} WHERE sc_category = 'stat' OR sc_category = '' ORDER BY sc_name ASC, sc_id ASC", false);
}
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
?>

<div id="unified-combat">
    <header class="uc-head">
        <p class="uc-kicker">A/K UNIFIED COMBAT</p>
        <h2>통합 전투 설정</h2>
        <p>스탯·스킬·슬롯은 이 화면을 기준으로 한 번만 설정합니다. 던전, 1:1, 실시간 레이드는 같은 A 원본을 사용하며 레이드는 입장 시점 값만 보관합니다.</p>
    </header>

    <section class="uc-card">
        <div class="uc-card-title">
            <div><span>01</span><h3>공통 전투 자원과 스킬 슬롯</h3></div>
            <p>체력은 A 스탯의 체력 지정값을 단일 원본으로 사용합니다. K 스킬 슬롯 화면은 더 이상 사용하지 않습니다.</p>
        </div>
        <form class="uc-form-grid" action="./990_unified_skill_map_update.php" method="post">
            <input type="hidden" name="action" value="save_unified_options">
            <input type="hidden" name="token" value="<?php echo get_token(); ?>">
            <label>체력(HP) 원본
                <select name="hp_st_id" required>
                    <option value="">선택</option>
                    <?php foreach ($status_rows as $row) { ?>
                        <option value="<?php echo (int)$row['st_id']; ?>"<?php echo (int)$row['st_id'] === $hp_st_id ? ' selected' : ''; ?>><?php echo get_text($row['st_name']); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>레이드 MP 원본
                <select name="mp_st_id">
                    <option value="0">MP 사용 안 함</option>
                    <?php foreach ($status_rows as $row) { ?>
                        <option value="<?php echo (int)$row['st_id']; ?>"<?php echo (int)$row['st_id'] === $mp_st_id ? ' selected' : ''; ?>><?php echo get_text($row['st_name']); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>턴 순서 원본
                <select name="speed_status_type">
                    <option value="">설정하지 않음</option>
                    <?php foreach ($status_types as $type) { ?>
                        <option value="<?php echo get_text($type); ?>"<?php echo $speed_status_type === $type ? ' selected' : ''; ?>><?php echo get_text($type); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>일반 공격 연동 코드
                <select name="basic_atk_code">
                    <option value="">자동 탐색</option>
                    <?php foreach ($extra_codes as $code) { ?>
                        <option value="<?php echo get_text($code); ?>"<?php echo ses($unified_action_config, 'basic_atk_code', '') === $code ? ' selected' : ''; ?>><?php echo get_text($code); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>일반 치유 연동 코드
                <select name="basic_heal_code">
                    <option value="">자동 탐색</option>
                    <?php foreach ($extra_codes as $code) { ?>
                        <option value="<?php echo get_text($code); ?>"<?php echo ses($unified_action_config, 'basic_heal_code', '') === $code ? ' selected' : ''; ?>><?php echo get_text($code); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>일반 방어 연동 코드
                <select name="basic_guard_code">
                    <option value="">자동 탐색</option>
                    <?php foreach ($extra_codes as $code) { ?>
                        <option value="<?php echo get_text($code); ?>"<?php echo ses($unified_action_config, 'basic_guard_code', '') === $code ? ' selected' : ''; ?>><?php echo get_text($code); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>기본 장착 슬롯
                <input type="number" name="skill_slot_min" min="0" value="<?php echo $slot_min; ?>">
            </label>
            <label>최대 장착 슬롯
                <input type="number" name="skill_slot_max" min="0" value="<?php echo $slot_max; ?>">
            </label>
            <div class="uc-submit"><input type="submit" value="공통 설정 저장" class="btn_submit"></div>
        </form>
        <p class="uc-help">일반 공격·치유·방어는 스킬 장착 여부와 무관하게 항상 사용할 수 있으며, 위 전투 연동 코드를 던전·1:1·레이드에서 함께 씁니다. 일반 방어는 자신의 받는 피해를 1턴 동안 줄입니다.</p>
    </section>

    <section class="uc-card">
        <div class="uc-card-title">
            <div><span>02</span><h3>공통 스탯 설정</h3></div>
            <p>기본 스탯과 전투 연동 코드는 A 설정이 원본입니다. 레이드 표시 슬롯에는 필요한 A 스탯 타입만 연결합니다.</p>
        </div>
        <div class="uc-link-grid">
            <a class="uc-link" href="./status_list.php"><strong>기본 스탯</strong><span>스탯 이름·범위·배분 설정</span></a>
            <a class="uc-link" href="./status_extra_list.php"><strong>전투 연동 코드</strong><span>공격·방어력·회피 등 수식 설정</span></a>
        </div>
        <form class="uc-role-form" action="./990_unified_skill_map_update.php" method="post">
            <input type="hidden" name="action" value="save_stat_map">
            <input type="hidden" name="token" value="<?php echo get_token(); ?>">
            <label>레이드 표시 슬롯
                <select name="k_sc_id" required>
                    <option value="">선택</option>
                    <?php if ($k_stats) while ($row = sql_fetch_array($k_stats)) { ?>
                        <option value="<?php echo (int)$row['sc_id']; ?>"><?php echo get_text($row['sc_name']); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>가져올 A 스탯 타입
                <select name="status_type" required>
                    <option value="">선택</option>
                    <?php foreach ($status_types as $type) { ?>
                        <option value="<?php echo get_text($type); ?>"><?php echo get_text($type); ?></option>
                    <?php } ?>
                </select>
            </label>
            <input type="submit" value="레이드 슬롯 연결" class="btn_submit">
        </form>
        <div class="uc-table-wrap">
            <table class="tbl_head01">
                <thead><tr><th>레이드 표시 슬롯</th><th>공통 원본(A 스탯 타입)</th><th>관리</th></tr></thead>
                <tbody>
                <?php $has_map = false; if ($stat_maps) while ($row = sql_fetch_array($stat_maps)) { $has_map = true; ?>
                    <tr>
                        <td><?php echo get_text($row['sc_name']); ?></td>
                        <td><?php echo get_text($row['status_type']); ?></td>
                        <td>
                            <form action="./990_unified_skill_map_update.php" method="post" onsubmit="return confirm('이 레이드 슬롯 지정을 해제하시겠습니까?');">
                                <input type="hidden" name="action" value="delete_stat_map">
                                <input type="hidden" name="map_id" value="<?php echo (int)$row['map_id']; ?>">
                                <input type="hidden" name="token" value="<?php echo get_token(); ?>">
                                <input type="submit" value="해제" class="btn btn_03">
                            </form>
                        </td>
                    </tr>
                <?php } if (!$has_map) { ?>
                    <tr><td colspan="3" class="empty_table">연결된 레이드 슬롯이 없습니다.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="uc-card">
        <div class="uc-card-title">
            <div><span>03</span><h3>공통 스킬 설정</h3></div>
            <p>한 A 스킬 정의가 던전·1:1·레이드에 함께 적용됩니다. 방어 스킬은 지속 시간 동안 받는 피해를 최대 90%까지 감소시킵니다.</p>
        </div>
        <div class="uc-link-grid">
            <a class="uc-link" href="./skill_list.php"><strong>스킬 정의</strong><span>액티브·패시브·방어·도트 수식 설정</span></a>
            <a class="uc-link" href="./skill_has_list.php"><strong>보유·장착 관리</strong><span>캐릭터별 A 스킬 장착 관리</span></a>
        </div>
        <form class="uc-compile" action="./990_unified_skill_map_update.php" method="post">
            <input type="hidden" name="action" value="compile">
            <input type="hidden" name="token" value="<?php echo get_token(); ?>">
            <p>기존 A 스킬을 처음 통합했거나 대량 수정한 경우에만 실행하세요. 개별 스킬 저장과 레이드 입장은 자동으로 최신 정의를 사용합니다.</p>
            <input type="submit" value="레이드 실행 정의 갱신" class="btn_submit">
        </form>
    </section>

    <section class="uc-card">
        <div class="uc-card-title">
            <div><span>04</span><h3>전투 콘텐츠와 장비</h3></div>
            <p>콘텐츠별 설정으로 바로 이동합니다. 별도 A/K 설정 허브나 포함 화면을 거치지 않습니다.</p>
        </div>
        <div class="uc-link-grid">
            <a class="uc-link" href="./map_list.php"><strong>맵·이동</strong><span>맵과 이동 연결</span></a>
            <a class="uc-link" href="./dungeon_list.php"><strong>던전</strong><span>던전·몬스터·보상</span></a>
            <a class="uc-link" href="./battle_config.php"><strong>1:1 전투</strong><span>대전 설정과 로그</span></a>
            <a class="uc-link" href="./982_k_realtime_list.php"><strong>실시간 레이드</strong><span>레이드 방과 패턴</span></a>
            <a class="uc-link" href="./980_k_equip_list.php"><strong>장비·강화</strong><span>장비와 강화 규칙</span></a>
        </div>
    </section>
</div>

<style>
#unified-combat { max-width: 1180px; margin: 20px auto 60px; color: #333; }
#unified-combat, #unified-combat * { box-sizing: border-box; }
#unified-combat .uc-head { padding: 26px 30px; border: 1px solid #d8e1ea; border-radius: 4px; background: #f7fafc; }
#unified-combat .uc-kicker { margin: 0 0 7px; color: #52728f; font-size: 11px; font-weight: 700; letter-spacing: .12em; }
#unified-combat .uc-head h2 { margin: 0; padding: 0; border: 0; color: #263b50; font-size: 25px; }
#unified-combat .uc-head > p:last-child { margin: 10px 0 0; color: #607284; font-size: 13px; line-height: 1.7; }
#unified-combat .uc-card { margin-top: 14px; padding: 24px 26px; border: 1px solid #d8e1ea; border-radius: 4px; background: #fff; }
#unified-combat .uc-card-title { display: flex; justify-content: space-between; gap: 20px; align-items: start; padding-bottom: 16px; border-bottom: 1px solid #e7edf2; }
#unified-combat .uc-card-title > div { display: flex; align-items: center; gap: 9px; }
#unified-combat .uc-card-title span { display: inline-block; padding: 4px 7px; border-radius: 3px; background: #edf3f8; color: #52728f; font-size: 11px; font-weight: 700; }
#unified-combat h3 { margin: 0; color: #344b62; font-size: 17px; }
#unified-combat .uc-card-title p { max-width: 520px; margin: 1px 0 0; color: #718191; font-size: 12px; line-height: 1.6; text-align: right; }
#unified-combat .uc-form-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 17px; padding: 15px; border: 1px solid #e1e8ef; background: #fafcfd; }
#unified-combat .uc-form-grid label, #unified-combat .uc-role-form label { display: grid; gap: 6px; color: #536779; font-size: 12px; font-weight: 700; }
#unified-combat select, #unified-combat input[type="number"] { width: 100%; min-height: 35px; padding: 6px 8px; border: 1px solid #cbd8e3; border-radius: 3px; color: #405364; background: #fff; }
#unified-combat .uc-submit { display: flex; align-items: end; }
#unified-combat .btn_submit { min-height: 35px; padding: 7px 12px; border: 1px solid #3c6e96; border-radius: 3px; background: #4f7fa5; color: #fff; font-size: 12px; font-weight: 700; cursor: pointer; }
#unified-combat .btn_submit:hover { background: #3d6d92; }
#unified-combat .uc-help { margin: 9px 0 0; color: #738394; font-size: 12px; line-height: 1.6; }
#unified-combat .uc-link-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 9px; margin-top: 16px; }
#unified-combat .uc-link { display: grid; gap: 5px; min-height: 80px; padding: 14px; border: 1px solid #dce5ed; border-radius: 3px; color: #405a70; background: #fafcfd; text-decoration: none; }
#unified-combat .uc-link:hover { border-color: #5d86aa; background: #edf5fb; }
#unified-combat .uc-link strong { color: #344b62; font-size: 14px; }
#unified-combat .uc-link span { color: #718191; font-size: 12px; line-height: 1.5; }
#unified-combat .uc-role-form { display: grid; grid-template-columns: minmax(190px, 1fr) minmax(190px, 1fr) auto; gap: 10px; align-items: end; margin: 17px 0 0; padding: 13px; border: 1px solid #e1e8ef; background: #fafcfd; }
#unified-combat .uc-table-wrap { margin-top: 13px; overflow-x: auto; border: 1px solid #dce5ed; }
#unified-combat .uc-table-wrap table { width: 100%; min-width: 520px; margin: 0; border: 0; }
#unified-combat .uc-table-wrap th { padding: 10px; border: 0; border-bottom: 1px solid #dce5ed; background: #f4f7f9; color: #536779; font-size: 12px; }
#unified-combat .uc-table-wrap td { padding: 10px; border: 0; border-bottom: 1px solid #edf1f4; color: #415467; font-size: 13px; text-align: center; }
#unified-combat .uc-table-wrap tr:last-child td { border-bottom: 0; }
#unified-combat .empty_table { color: #8190a0; }
#unified-combat .btn_03 { padding: 5px 9px; border: 1px solid #dfc4c4; border-radius: 3px; background: #fff; color: #a35454; font-size: 12px; cursor: pointer; }
#unified-combat .uc-compile { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 16px; padding: 13px; border: 1px solid #e1e8ef; background: #fafcfd; }
#unified-combat .uc-compile p { margin: 0; color: #65788a; font-size: 12px; line-height: 1.6; }
@media (max-width: 760px) {
  #unified-combat { margin: 12px auto 40px; }
  #unified-combat .uc-head, #unified-combat .uc-card { padding: 19px 16px; }
  #unified-combat .uc-card-title { display: block; }
  #unified-combat .uc-card-title p { margin-top: 8px; text-align: left; }
  #unified-combat .uc-form-grid, #unified-combat .uc-role-form { grid-template-columns: 1fr; }
  #unified-combat .uc-submit, #unified-combat .btn_submit { width: 100%; }
}
</style>
<?php include_once('./admin.tail.php'); ?>
