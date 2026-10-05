<?php
// This constant is set before common.php: rendering this route cannot bootstrap
// game tables. All DDL goes through the authorized installer library below.
define('G5_SYSTEM_INSTALLER', true);
$sub_menu = '100950';
include_once './_common.php';
if ($is_admin !== 'super') {
    http_response_code(403);
    exit('최고관리자만 시스템 설치 화면을 사용할 수 있습니다.');
}
include_once G5_LIB_PATH.'/system_installer.lib.php';
// Pure shared UI helpers; this extension contains no schema bootstrap.
include_once G5_EXTEND_PATH.'/community.config.php';
include_once G5_EXTEND_PATH.'/version.extend.php';

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $result = system_install_run($_POST['module'] ?? '', $_POST['action'] ?? '');
    } catch (Throwable $error) {
        $result = array('success'=>array(), 'errors'=>array($error->getMessage()), 'skipped'=>array());
    }
    // POST/Redirect/GET also prevents accidental form resubmission on refresh.
    $_SESSION['system_install_result'] = $result;
    header('Location: '.G5_ADMIN_URL.'/system_install.php', true, 303);
    exit;
}
if (isset($_SESSION['system_install_result'])) {
    $result = $_SESSION['system_install_result'];
    unset($_SESSION['system_install_result']);
}
$registry = system_install_registry();
$states = system_install_all_status();
try {
    $inventory = system_install_inventory_preflight();
    $qa_missing = system_install_qa_status($states);
} catch (Throwable $error) {
    $inventory = array('engine'=>'확인 실패','reasons'=>array($error->getMessage()));
    $qa_missing = array('DB 상태를 확인하지 못했습니다. 상세 오류를 확인해 주세요.');
}
$token = system_install_token();
function system_install_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$g5['title'] = '시스템 설치 / 업데이트';
include_once './admin.head.php';
include __DIR__.'/system_install.view.php';
include_once './admin.tail.php';
