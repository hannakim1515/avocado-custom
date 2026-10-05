<?php
$sub_menu = "730200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');
require_once __DIR__.'/dungeon_maze.admin.lib.php';
$maze_ready=maze_readiness()['ready'];
$ds_id=(int)($_REQUEST['ds_id']??0);
if($_SERVER['REQUEST_METHOD']==='GET' && $ds_id) goto_url('./dungeon_log.php?ds_id='.$ds_id);
$dg_id=0;
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['operation'])) {
    auth_check($auth[$sub_menu], 'w');
    inventory_boundary_check_token();
    try {
        maze_require_ready();
        $operation=$_POST['operation'];
        if (in_array($operation,array('clear','fail','kick'),true)) {
            maze_admin_end($ds_id,$operation,(int)($_POST['target_id']??0));
        } elseif ($operation==='reward_retry') {
            if (!maze_session($ds_id)) throw new RuntimeException('세션을 찾을 수 없습니다.');
            maze_pay_rewards($ds_id);
        } else throw new RuntimeException('지원하지 않는 요청입니다.');
        goto_url('./dungeon_log.php?ds_id='.$ds_id.'&saved=1');
    } catch (Throwable $error) { $message=$error->getMessage(); }
}

$token = get_token();

$sql_common = " from {$g5['dungeon_table']} dg, {$g5['dungeon_state_table']} ds ";

$sql_search = " where ds.dg_id = dg.dg_id ";

if (!$sst) {
	$sst  = "ds.ds_datetime";
	$sod = "desc";
}
$sql_order = " order by ds.ds_datetime desc, ds.ds_id desc ";

$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_search}
			{$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = max(1,(int)($config['cf_page_rows']??0) ?: 20);
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select *
			{$sql_common}
			{$sql_search}
			{$sql_order}
			limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '던전 발생 현황 관리';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
if ($message) echo '<div class="local_desc01 local_desc" role="status">'.maze_admin_h($message).'</div>';
if (!empty($_GET['saved'])) echo '<div class="local_desc01 local_desc" role="status">처리했습니다.</div>';

$colspan =9;

if($config['cf_dungeon_open']) {
	// 던전변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}

?>



<section id="anc_002">
	<div class="local_ov01 local_ov">
		<?php echo $listall ?>
		전체 <?php echo number_format($total_count) ?> 건
	</div>

	<form name="fsidelist" id="fsidelist" method="post" action="./dungeon_state_list_update.php" onsubmit="return fsidelist_submit(this);"  enctype="multipart/form-data">
		<input type="hidden" name="sst" value="<?php echo $sst ?>">
		<input type="hidden" name="sod" value="<?php echo $sod ?>">
		<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
		<input type="hidden" name="stx" value="<?php echo $stx ?>">
		<input type="hidden" name="page" value="<?php echo $page ?>">
		<input type="hidden" name="token" value="<?php echo $token ?>">

		<div class="tbl_head01 tbl_wrap" style="min-width:1400px;">
			<table>
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<colgroup>
				<col style="width: 50px;" />
				<col style="width: 80px;" />
				<col style="width: 150px;"/>
				<col style="width: 200px;"/>
				<col style="width: 80px;"/>
				<col />
				<col style="width: 250px;"/>
				<col style="width: 150px;"/>
				<col style="width: 80px;"/>
			</colgroup>
			<thead>
			<tr>
				<th scope="col">
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col">상태</th>
				<th scope="col">던전명</th>
				<th scope="col">등장지역</th>
				<th scope="col" colspan="2">입장현황</th>
				<th scope="col">탐험 진행</th>
				<th scope="col">열린시각</th>
				<th scope="col">관리</th>
			</tr>
			</thead>
			<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$one_update = '<a href="./dungeon_form.php?w=u&amp;dg_id='.$row['dg_id'].'&amp;'.$qstr.'">수정</a>';
				$bg = 'bg'.($i%2);

				// 던전 상태 가져오기
				$color = "";
				$state_txt = "";
				switch($row['ds_state']) { 
					case "S" : $color = "yellow"; $state_txt = "열림"; break;
					case "E" : $color = "#999"; $state_txt = "닫힘"; break;
				}

				$maze_session=$maze_ready?maze_session((int)$row['ds_id']):null;
				$maze_people=$maze_session?maze_party((int)$row['ds_id'],false):array();
				if($maze_session) {
					$state_txt=array('WAITING'=>'대기실','EXPLORE'=>'탐색 / 조사','BATTLE'=>'전투','BOSS_RESULT'=>'보스 처치','CLEAR'=>'클리어','FAILED'=>'실패','CLOSED'=>'종료')[$maze_session['phase']]??$maze_session['phase'];
					$color='';
				}
				$dg_mem_list = $maze_session?array():get_dungeon_member($row['ds_id'], "");
			?>

			<tr class="<?php echo $bg; ?>">
				<td style="text-align: center">
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>" <?=$maze_session?'disabled':''?>>
					<input type="hidden" name="ds_id[<?php echo $i ?>]" value="<?php echo $row['ds_id'] ?>" />
				</td>
				<td style="background:<?=$color?>"><?=$state_txt?></td>
				<td style="text-align:left;">
					<a href="./dungeon_form.php?w=u&dg_id=<?=$row['dg_id']?>"><?=$row['dg_title']?></a>
				</td>
				<td><?=get_map_name(($row['ds_ma_id'] ? $row['ds_ma_id'] : $row['ma_id']))?></td>

				<td>
					<?=$maze_session?count(array_filter($maze_people,function($p){return $p['state']==='ACTIVE';})):get_dungeon_member_count($row['ds_id'])?> / <?=$row['dg_count']?>
				</td>
				<td style="text-align:left;">
					<?php foreach($maze_people as $person): ?><p><?=maze_admin_h($person['name'])?> · HP <?=(int)$person['hp']?> · <?=maze_admin_h($person['state'])?></p><?php endforeach; ?>

				</td>
				<td>
					<?php if($maze_session): ?>
					방 <?=(int)$maze_session['room_id']?>
					<?php if($battle=maze_admin_battle($maze_session)): ?> · 턴 <?=(int)$battle['turn_no']?><?php endif; ?>
                    <?php else: ?>대기실 입장 전
                    <?php endif; ?>
				</td>
				<td>
					<? if($row['ds_datetime']) { echo $row['ds_datetime']; } ?>
				</td>
				<td>
					<a class="btn btn_03" href="./dungeon_log.php?ds_id=<?=(int)$row['ds_id']?>">로그확인</a>
				</td>
			</tr>

			<?php
			}

			if ($i == 0)
				echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
			?>
			</tbody>
			</table>
		</div>

		<div class="btn_list01 btn_list">
			<input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
			<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
		</div>

	</form>

	<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$qstr&amp;page="); ?>
</section>


<script>
function fsidelist_submit(f)
{
	if (!is_checked("chk[]")) {
		alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
		return false;
	}

	if(document.pressed == "선택삭제") {
		if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
			return false;
		}
	}

	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
