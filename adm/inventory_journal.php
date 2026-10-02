<?php
$sub_menu = '500300';
include_once('./_common.php');
auth_check($auth[$sub_menu], 'r');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth_check($auth[$sub_menu], 'w');
    try {
        inventory_boundary_check_token();
        inventory_boundary_reconcile((int)$_POST['journal_id'], isset($_POST['resolution']) && $_POST['resolution'] === 'restore', isset($_POST['note']) ? $_POST['note'] : '');
    } catch (Throwable $error) { alert($error->getMessage()); }
    goto_url('./inventory_journal.php');
}
$g5['title'] = '아이템 처리 확인';
include_once('./admin.head.php');
$readiness = inventory_boundary_readiness();
$journal_token = inventory_boundary_token();
?>
<div class="local_desc01 local_desc"><p>효과 처리가 중단된 기록입니다. 실제 포인트·회복·제작 결과를 확인한 후 보정하세요. 효과가 일부라도 적용됐다면 아이템 전체를 복원하지 마세요.</p></div>
<?php if (!$readiness['ready']) { ?>
<div class="local_desc01 local_desc"><p>아이템 안전 처리 DB migration이 적용되지 않았습니다.</p></div>
<?php } else {
    $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
    $offset = ($page - 1) * 30;
    $table = inventory_boundary_table('journal');
    $rows = inventory_boundary_rows("SELECT * FROM `{$table}` WHERE state IN ('REVIEW','PROCESSING') ORDER BY created_at,journal_id LIMIT {$offset},31");
    $has_more = count($rows) > 30;
    if ($has_more) array_pop($rows);
    foreach ($rows as $row) {
?>
<section class="local_desc01 local_desc">
<h2><?php echo (int)$row['journal_id']; ?> · <?php echo htmlspecialchars($row['operation'], ENT_QUOTES, 'UTF-8'); ?></h2>
<p>캐릭터 <?php echo (int)$row['ch_id']; ?> · <?php echo htmlspecialchars($row['state'].' · '.$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
<p><?php echo htmlspecialchars($row['result_note'], ENT_QUOTES, 'UTF-8'); ?></p>
<details><summary>원본 아이템과 처리 의도</summary><pre><?php
    echo htmlspecialchars(print_r(array('originals' => inventory_boundary_decode($row['originals']), 'intent' => inventory_boundary_decode($row['intent'])), true), ENT_QUOTES, 'UTF-8');
?></pre></details>
<?php if ($row['state'] === 'REVIEW') { ?>
<form method="post">
<input type="hidden" name="token" value="<?php echo $journal_token; ?>">
<input type="hidden" name="journal_id" value="<?php echo (int)$row['journal_id']; ?>">
<label>확인 사유 <input type="text" class="frm_input" name="note" required maxlength="500"></label>
<button type="submit" name="resolution" value="done" class="btn">효과 적용 확인 · 완료</button>
<button type="submit" name="resolution" value="restore" class="btn" onclick="return confirm('효과가 전혀 적용되지 않았음을 확인했습니까? 원본 아이템을 복원합니다.');">효과 미적용 확인 · 원본 복원</button>
</form>
<?php } else { ?><p>진행 중입니다. 처리 중인 요청을 완료 처리하거나 복원하지 마세요.</p><?php } ?>
</section>
<?php }
    if (!$rows) echo '<p>확인이 필요한 기록이 없습니다.</p>';
    if ($page > 1) echo '<a href="?page='.($page - 1).'">이전</a> ';
    if ($has_more) echo '<a href="?page='.($page + 1).'">다음</a>';
}
include_once('./admin.tail.php');
