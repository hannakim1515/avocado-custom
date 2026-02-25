<?php
$sub_menu = '980001';
include_once('./_common.php');

$g5['title'] = '플러그인 설치/버전 관리';
include_once('./admin.head.php');

// 버전 상수 정의
if (!defined('K_BATTLE_VERSION')) {
    define('K_BATTLE_VERSION', '2.0.2');
}
if (!defined('K_REALTIME_VERSION')) {
    define('K_REALTIME_VERSION', '1.0.0');
}

if (!isset($g5['k_battle_config'])) {
    $g5['k_battle_config'] = G5_TABLE_PREFIX.'k_battle_plugin_config';
}

$GLOBALS['_k_migration_errors'] = array();

function column_exists($table, $column) {
    $result = sql_query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'", false);
    return $result && sql_num_rows($result) > 0;
}
function table_exists($table) {
    $result = sql_query("SHOW TABLES LIKE '{$table}'", false);
    return $result && sql_num_rows($result) > 0;
}
function index_exists($table, $index_name) {
    $result = sql_query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index_name}'", false);
    return $result && sql_num_rows($result) > 0;
}
function run_migration($description, $sql) {
    global $g5;
    $result = sql_query($sql, false);
    if (!$result) {
        $error_msg = '';
        if (isset($g5['connect_db']) && $g5['connect_db']) {
            $error_msg = mysqli_error($g5['connect_db']);
        }
        $GLOBALS['_k_migration_errors'][] = "{$description}: {$error_msg}";
        return false;
    }
    return true;
}
function k_battle_migration($current_ver, $type='plugin') {
    global $g5;
    
    $install_path = G5_PATH . '/k_battle/extend/install';
    $ver_column = ($type === 'realtime') ? 'ver_realtime' : 'ver_plugin';
    
    $files = glob($install_path . '/' . $type . '_*.php');
    if (empty($files)) {
        return array('success' => true, 'message' => '마이그레이션 파일이 없습니다.');
    }
    
    $migrations = array();
    foreach ($files as $file) {
        $filename = basename($file);
        if (preg_match('/^' . preg_quote($type, '/') . '_([0-9]+\.[0-9]+(?:\.[0-9]+)?)\.php$/', $filename, $m)) {
            $ver = $m[1];
            $migrations[$ver] = $file;
        }
    }
    
    if (empty($migrations)) {
        return array('success' => true, 'message' => '실행할 마이그레이션이 없습니다.');
    }
    
    uksort($migrations, 'version_compare');
    
    $last_ver = K_BATTLE_VERSION;
    $executed = array();
    
    foreach ($migrations as $ver => $file) {
        if (version_compare($ver, $current_ver, '>')) {
            include_once $file;
            
            if (!empty($GLOBALS['_k_migration_errors'])) {
                break;
            }
            
            $executed[] = $ver;
            $last_ver = $ver;
        }
    }
    
    if (empty($GLOBALS['_k_migration_errors']) && version_compare($last_ver, $current_ver, '>')) {
        sql_query("UPDATE `{$g5['k_battle_config']}` SET `{$ver_column}` = '".sql_escape_string($last_ver)."' WHERE cf_id = 1", false);
        return array('success' => true, 'message' => '버전 ' . implode(' → ', $executed) . ' 마이그레이션 완료', 'version' => $last_ver);
    }
    
    if (!empty($GLOBALS['_k_migration_errors'])) {
        return array('success' => false, 'message' => implode("\n", $GLOBALS['_k_migration_errors']));
    }
    
    return array('success' => true, 'message' => '이미 최신 버전입니다.');
}

// SQL 파일 실행 함수
function k_run_sql_file($sql_file) {
    global $g5;
    
    $sql_raw = @file($sql_file);
    if ($sql_raw === false) {
        return array('success' => false, 'message' => basename($sql_file) . ' 을 읽을 수 없습니다.');
    }
    
    $sql_raw = implode('', $sql_raw);
    $sql_raw = preg_replace('/^--.*$/m', '', $sql_raw);
    $sql_raw = preg_replace('!/\*.*?\*/!s', '', $sql_raw);
    $sql_raw = preg_replace(
        '/`avo_([^`]+`)/',
        '`'.G5_TABLE_PREFIX.'$1',
        $sql_raw
    );

    $queries = explode(';', $sql_raw);
    $errors = array();
    
    for ($i=0; $i<count($queries); $i++) {
        $q = trim($queries[$i]);
        if ($q === '') continue;
        if (stripos($q, 'delimiter ') === 0) continue;

        $res = sql_query($q, false);
        if (!$res) {
            $errors[] = mysqli_error($g5['connect_db']);
        }
    }

    if (!empty($errors)) {
        return array('success' => false, 'message' => implode("\n", $errors));
    }
    
    return array('success' => true, 'message' => '설치가 완료되었습니다.');
}

/*설정 및 버전 확인*/
$chk = sql_fetch("SHOW TABLES LIKE '{$g5['k_battle_config']}'", false);
$is_installed = !empty($chk);
$current_ver = '1.0.0';
$need_update = false;
$install_result = null;

// 실시간 레이드 관련 변수
$realtime_sql_file = G5_PATH.'/k_battle/extend/install/database_realtime.sql';
$realtime_exists = file_exists($realtime_sql_file);
$realtime_installed = false;
$realtime_ver = '0.0.0';
$realtime_need_update = false;
$realtime_result = null;

// 배틀 플러그인 설치 / 재설치
if (isset($_POST['action']) && ($_POST['action'] === 'install' || $_POST['action'] === 'reinstall')) {
    $sql_file = G5_PATH.'/k_battle/extend/install/database_plugin.sql';
    $install_result = k_run_sql_file($sql_file);
    if ($install_result['success']) {
        $install_result['message'] = ($_POST['action'] === 'reinstall') 
            ? '배틀 플러그인 초기화 및 재설치가 완료되었습니다.' 
            : '배틀 플러그인 설치가 완료되었습니다.';
        $is_installed = true;
    }
}

if ($is_installed) {
    $kb_cf = sql_fetch("SELECT * FROM `{$g5['k_battle_config']}` WHERE cf_id = 1", false);
    if (!is_array($kb_cf)) {
        $kb_cf = array();
    }
    $current_ver = !empty($kb_cf['ver_plugin']) ? $kb_cf['ver_plugin'] : '1.0.0';
    $need_update = version_compare($current_ver, K_BATTLE_VERSION, '<');
    
    // 배틀 플러그인 업데이트 실행
    if ($need_update && isset($_POST['action']) && $_POST['action'] === 'update') {
        $install_result = k_battle_migration($current_ver, 'plugin');
        if ($install_result['success'] && !empty($install_result['version'])) {
            $current_ver = $install_result['version'];
            $need_update = version_compare($current_ver, K_BATTLE_VERSION, '<');
            // kb_cf 다시 로드
            $kb_cf = sql_fetch("SELECT * FROM `{$g5['k_battle_config']}` WHERE cf_id = 1", false);
        }
    }
    
    // 실시간 레이드 상태 확인
    if ($realtime_exists) {
        // ver_realtime 컬럼 존재 여부 확인
        $realtime_installed = column_exists($g5['k_battle_config'], 'ver_realtime');
        if ($realtime_installed) {
            $realtime_ver = !empty($kb_cf['ver_realtime']) ? $kb_cf['ver_realtime'] : '1.0.0';
            $realtime_need_update = version_compare($realtime_ver, K_REALTIME_VERSION, '<');
        }
        
        // 실시간 레이드 설치 / 재설치
        if (isset($_POST['action']) && ($_POST['action'] === 'install_realtime' || $_POST['action'] === 'reinstall_realtime')) {
            $GLOBALS['_k_migration_errors'] = array();
            $realtime_result = k_run_sql_file($realtime_sql_file);
            if ($realtime_result['success']) {
                // ver_realtime 컬럼 추가
                if (!column_exists($g5['k_battle_config'], 'ver_realtime')) {
                    run_migration('ver_realtime 컬럼 추가', 
                        "ALTER TABLE `{$g5['k_battle_config']}` ADD `ver_realtime` varchar(20) NOT NULL DEFAULT '".K_REALTIME_VERSION."'");
                }
                sql_query("UPDATE `{$g5['k_battle_config']}` SET `ver_realtime` = '".K_REALTIME_VERSION."' WHERE cf_id = 1", false);
                
                if (empty($GLOBALS['_k_migration_errors'])) {
                    $realtime_result['message'] = ($_POST['action'] === 'reinstall_realtime')
                        ? '실시간 레이드 초기화 및 재설치가 완료되었습니다.'
                        : '실시간 레이드 설치가 완료되었습니다.';
                    $realtime_installed = true;
                    $realtime_ver = K_REALTIME_VERSION;
                    $realtime_need_update = false;
                } else {
                    $realtime_result = array('success' => false, 'message' => implode("\n", $GLOBALS['_k_migration_errors']));
                }
            }
        }
        
        // 실시간 레이드 업데이트
        if ($realtime_installed && $realtime_need_update && isset($_POST['action']) && $_POST['action'] === 'update_realtime') {
            $GLOBALS['_k_migration_errors'] = array();
            $realtime_result = k_battle_migration($realtime_ver, 'realtime');
            if ($realtime_result['success'] && !empty($realtime_result['version'])) {
                $realtime_ver = $realtime_result['version'];
                $realtime_need_update = version_compare($realtime_ver, K_REALTIME_VERSION, '<');
            }
        }
    }
}
?>

<div class="local_desc02 local_desc">
    <p>(K)종합 배틀 플러그인 설치 및 업데이트를 관리합니다.</p><br>
    <p>가이드 문서 : <a href="https://docs.google.com/spreadsheets/d/1eIlJzz6Kr8iaf77x9SgX4KVOlGU6bqo-LRmtX8c9uUw/edit?gid=1366290134#gid=1366290134">종합 배틀 플러그인(K)-가이드 문서</a></p><br>
    <p><a href="https://www.postype.com/@gd3745/post/16417922"><img src="https://untitled848.cafe24.com/test/version.png" alt="버전 정보"></a></p>

</div>

<!-- 배틀 플러그인 -->
<h2 class="h2_frm" style="margin-top:20px;">배틀 플러그인</h2>

<?php if ($install_result): ?>
<div style="padding:15px; margin:10px 0; border-radius:5px; background:<?php echo $install_result['success'] ? '#d4edda' : '#f8d7da'; ?>; border:1px solid <?php echo $install_result['success'] ? '#c3e6cb' : '#f5c6cb'; ?>;">
    <strong><?php echo $install_result['success'] ? '✓ 성공' : '✗ 오류'; ?></strong><br>
    <pre style="margin:10px 0 0 0; white-space:pre-wrap;"><?php echo h($install_result['message']); ?></pre>
</div>
<?php endif; ?>


<div class="tbl_frm01 tbl_wrap">
    <table>
        <colgroup>
            <col style="width:200px;">
            <col>
            <col style="width:200px;">
        </colgroup>
        <tbody>
            <tr>
                <th>설치 상태</th>
                <td>
                    <?php if ($is_installed): ?>
                        <span style="color:green; font-weight:bold;">✓ 설치됨</span>
                    <?php else: ?>
                        <span style="color:red; font-weight:bold;">✗ 미설치</span>
                    <?php endif; ?>
                </td>
                <td rowspan="3" style="text-align:center; vertical-align:middle;">
                    <?php if (!$is_installed): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="install">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('플러그인을 설치하시겠습니까?');">
                                <i class="fa fa-download"></i> 설치
                            </button>
                        </form>
                    <?php else: ?>
                        <?php if ($need_update): ?>
                        <form method="post" style="margin-bottom:5px;">
                            <input type="hidden" name="action" value="update">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('v<?php echo h($current_ver); ?> → v<?php echo h(K_BATTLE_VERSION); ?>로 업데이트하시겠습니까?');">
                                <i class="fa fa-refresh"></i> 업데이트
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:green; display:block; margin-bottom:5px;">✓ 최신</span>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="action" value="reinstall">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('경고: 모든 데이터가 삭제됩니다!\n\n정말 초기화 및 재설치하시겠습니까?');">
                                <i class="fa fa-trash"></i> 초기화
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>현재 버전</th>
                <td>
                    <?php if ($is_installed): ?>
                        <strong><?php echo h($current_ver); ?></strong>
                    <?php else: ?>
                        <span style="color:#999;">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>최신 버전</th>
                <td>
                    <strong><?php echo h(K_BATTLE_VERSION); ?></strong>
                    <?php if ($need_update): ?>
                        <span style="color:orange; margin-left:10px;">⚠ 업데이트 필요</span>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 실시간 레이드 -->
<h2 class="h2_frm" style="margin-top:40px;">실시간 레이드</h2>

<?php if ($realtime_result): ?>
<div style="padding:15px; margin:10px 0; border-radius:5px; background:<?php echo $realtime_result['success'] ? '#d4edda' : '#f8d7da'; ?>; border:1px solid <?php echo $realtime_result['success'] ? '#c3e6cb' : '#f5c6cb'; ?>;">
    <strong><?php echo $realtime_result['success'] ? '✓ 성공' : '✗ 오류'; ?></strong><br>
    <pre style="margin:10px 0 0 0; white-space:pre-wrap;"><?php echo h($realtime_result['message']); ?></pre>
</div>
<?php endif; ?>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <colgroup>
            <col style="width:200px;">
            <col>
            <col style="width:200px;">
        </colgroup>
        <tbody>
            <?php if (!$realtime_exists): ?>
            <tr>
                <th>설치 상태</th>
                <td colspan="2">
                    <span style="color:#999;">실시간 레이드 플러그인 파일이 없습니다.</span><br>
                    <small style="color:#999;">database_realtime.sql 파일을 k_battle/extend/install/ 폴더에 추가해주세요.</small>
                </td>
            </tr>
            <?php elseif (!$is_installed): ?>
            <tr>
                <th>설치 상태</th>
                <td colspan="2">
                    <span style="color:orange;">⚠ 배틀 플러그인을 먼저 설치해주세요.</span>
                </td>
            </tr>
            <?php else: ?>
            <tr>
                <th>설치 상태</th>
                <td>
                    <?php if ($realtime_installed): ?>
                        <span style="color:green; font-weight:bold;">✓ 설치됨</span>
                    <?php else: ?>
                        <span style="color:red; font-weight:bold;">✗ 미설치</span>
                    <?php endif; ?>
                </td>
                <td rowspan="3" style="text-align:center; vertical-align:middle;">
                    <?php if (!$realtime_installed): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="install_realtime">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('실시간 레이드를 설치하시겠습니까?');">
                                <i class="fa fa-download"></i> 설치
                            </button>
                        </form>
                    <?php else: ?>
                        <?php if ($realtime_need_update): ?>
                        <form method="post" style="margin-bottom:5px;">
                            <input type="hidden" name="action" value="update_realtime">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('v<?php echo h($realtime_ver); ?> → v<?php echo h(K_REALTIME_VERSION); ?>로 업데이트하시겠습니까?');">
                                <i class="fa fa-refresh"></i> 업데이트
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:green; display:block; margin-bottom:5px;">✓ 최신</span>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="action" value="reinstall_realtime">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('⚠️ 경고: 모든 레이드 데이터가 삭제됩니다!\n\n정말 초기화 및 재설치하시겠습니까?');">
                                <i class="fa fa-trash"></i> 초기화
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>현재 버전</th>
                <td>
                    <?php if ($realtime_installed): ?>
                        <strong><?php echo h($realtime_ver); ?></strong>
                    <?php else: ?>
                        <span style="color:#999;">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>최신 버전</th>
                <td>
                    <strong><?php echo h(K_REALTIME_VERSION); ?></strong>
                    <?php if ($realtime_need_update): ?>
                        <span style="color:orange; margin-left:10px;">⚠ 업데이트 필요</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>