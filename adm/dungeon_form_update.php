<?php
$sub_menu = '730100';
include_once './_common.php';

$w = $_POST['w'] ?? '';
$dg_id = (int)($_POST['dg_id'] ?? 0);
if ($w !== '' && $w !== 'u') alert('저장 방식이 올바르지 않습니다.');
if ($w === 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

require_once G5_PATH.'/lib/monster_shared.lib.php';
require_once __DIR__.'/dungeon_admin.lib.php';
require_once __DIR__.'/dungeon_maze.admin.lib.php';

function da_dungeon_int($name, $min, $max) {
    $value = filter_var($_POST[$name] ?? null, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) throw new RuntimeException($name.' 값을 확인해 주세요.');
    return $value;
}
function da_dungeon_query($sql) {
    if (sql_query($sql, false) === false) throw new RuntimeException('던전 설정을 저장할 수 없습니다.');
}

try {
    if ($w === 'u' && ($dg_id < 1 || !sql_fetch('SELECT dg_id FROM `'.$g5['dungeon_table'].'` WHERE dg_id='.$dg_id)))
        throw new RuntimeException('던전 정보가 존재하지 않습니다.');
    $title = trim(stripslashes((string)($_POST['dg_title'] ?? '')));
    $rank = trim(stripslashes((string)($_POST['dg_rank'] ?? '')));
    if ($title === '' || mb_strlen($title) > 255) throw new RuntimeException('던전 이름을 확인해 주세요.');
    if (mb_strlen($rank) > 255) throw new RuntimeException('던전 랭크를 확인해 주세요.');
    $description = stripslashes((string)($_POST['description'] ?? ''));
    $count = da_dungeon_int('dg_count', 1, 999);
    $percent_start = da_dungeon_int('dg_per_s', 0, 100);
    $percent_end = da_dungeon_int('dg_per_e', $percent_start, 100);
    $point = da_dungeon_int('dg_point', 0, 2147483647);
    $status = (int)($_POST['dg_status'] ?? 0);
    $status_type = (string)($_POST['dg_status_type'] ?? '');
    $status_value = trim((string)($_POST['dg_status_value'] ?? ''));
    if (!in_array($status_type, array('', '+', 'x'), true) || strlen($status_value) > 11)
        throw new RuntimeException('지역 버프를 확인해 주세요.');
    $use = !empty($_POST['dg_use']) ? 1 : 0;
    $regions = $_POST['ma_ids'] ?? array();
    if (!is_array($regions)) throw new RuntimeException('지역을 선택해 주세요.');
    $regions = array_values(array_unique(array_filter(array_map('intval', $regions), function($id){ return $id > 0; })));
    $region_id = $regions[0] ?? 0;
    $region_list = '||'.implode('||', $regions).'||';

    maze_require_ready();
    if(!isset($_POST['pool_present']))throw new RuntimeException('몬스터 출현 설정을 다시 입력하세요.');
    $pool_selection = da_pool_validate($_POST);

    $reward_names = $_POST['it_name'] ?? array();
    $reward_counts = $_POST['di_count'] ?? array();
    $reward_percents = $_POST['di_percent'] ?? array();
    if (!is_array($reward_names) || !is_array($reward_counts) || !is_array($reward_percents))
        throw new RuntimeException('아이템 보상 설정을 다시 입력해 주세요.');
    $rewards = array();
    foreach ($reward_names as $index=>$name) {
        $name = trim(stripslashes((string)$name));
        if ($name === '') continue;
        $item = sql_fetch('SELECT it_id FROM `'.$g5['item_table'].'` WHERE it_name=\''.sql_escape_string($name).'\' LIMIT 1');
        if (empty($item['it_id'])) throw new RuntimeException('아이템 보상에서 등록된 아이템을 선택하세요: '.$name);
        $amount = filter_var($reward_counts[$index] ?? null, FILTER_VALIDATE_INT);
        $percent = filter_var($reward_percents[$index] ?? null, FILTER_VALIDATE_FLOAT);
        if ($amount === false || $amount < 1 || $amount > 100 || $percent === false || $percent < 0 || $percent > 100 || abs($percent-round($percent,2))>0.000001)
            throw new RuntimeException('보상 수량은 1~100개, 확률은 0~100% (소수점 둘째 자리까지)로 입력하세요.');
        $rewards[] = array('it_id'=>(int)$item['it_id'], 'count'=>$amount, 'percent'=>$percent);
    }
    $maze_base = array('dg_id'=>$dg_id,'dg_title'=>$title,'dg_count'=>$count,'dg_description'=>$description,'dg_point'=>$point);
    $maze_settings = maze_admin_settings($maze_base,$_POST,$pool_selection,$rewards);

    da_dungeon_query('START TRANSACTION');
    try {
        $common = 'dg_use='.$use.',dg_title=\''.sql_escape_string($title).'\',dg_count='.$count
            .',ma_id='.$region_id.',ma_ids=\''.sql_escape_string($region_list).'\''
            .',dg_rank=\''.sql_escape_string($rank).'\',dg_per_s='.$percent_start.',dg_per_e='.$percent_end
            .',dg_point='.$point.',dg_status='.$status.',dg_status_type=\''.sql_escape_string($status_type).'\''
            .',dg_status_value=\''.sql_escape_string($status_value).'\''
            .',dg_description=\''.sql_escape_string($description).'\''
            .',dg_settings=\''.sql_escape_string(maze_json($maze_settings)).'\'';
        if ($w === '') {
            da_dungeon_query('INSERT INTO `'.$g5['dungeon_table'].'` SET '.$common);
            $dg_id = (int)sql_insert_id();
        } else da_dungeon_query('UPDATE `'.$g5['dungeon_table'].'` SET '.$common.' WHERE dg_id='.$dg_id);

        da_pool_save($dg_id, $pool_selection, false);
        da_dungeon_query('DELETE FROM `'.$g5['dungeon_item_table'].'` WHERE dg_id='.$dg_id);
        foreach ($rewards as $reward)
            da_dungeon_query('INSERT INTO `'.$g5['dungeon_item_table'].'` (dg_id,it_id,di_count,di_percent) VALUES ('.$dg_id.','.$reward['it_id'].','.$reward['count'].','.$reward['percent'].')');
        da_dungeon_query('COMMIT');
    } catch (Throwable $error) {
        sql_query('ROLLBACK', false);
        throw $error;
    }
} catch (Throwable $error) {
    alert($error->getMessage());
}

goto_url('./dungeon_form.php?w=u&dg_id='.$dg_id.'&saved=1&'.$qstr, false);
