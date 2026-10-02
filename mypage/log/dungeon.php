<?php
include_once('./_common.php');
include_once('./_head.php');



$dg_list = array();
if (function_exists('maze_installed') && maze_installed()) {
    $maze_before = isset($_GET['maze_before']) ? max(0,(int)$_GET['maze_before']) : 0;
    $maze_history = inventory_boundary_rows('SELECT m.dm_id,m.ds_id,m.state,m.settled,m.joined_at,s.phase,s.started_at,s.finished_at,s.snapshot FROM `'.maze_table('member').'` m JOIN `'.maze_table('session').'` s ON s.ds_id=m.ds_id WHERE m.ch_id='.(int)$character['ch_id'].' AND m.mb_id='.inventory_boundary_quote($member['mb_id']).($maze_before?' AND m.dm_id<'.$maze_before:'').' ORDER BY m.dm_id DESC LIMIT 20');
    echo '<section><h2>미궁 기록</h2><ul>';
    foreach ($maze_history as $entry) {
        $history_settings=maze_data($entry['snapshot']);
        echo '<li><a href="'.G5_URL.'/dungeon/maze.php?ds_id='.(int)$entry['ds_id'].'">'.htmlspecialchars(isset($history_settings['title'])?$history_settings['title']:'미궁 #'.$entry['ds_id'],ENT_QUOTES,'UTF-8').' · '.htmlspecialchars($entry['phase'].' / '.$entry['state'],ENT_QUOTES,'UTF-8').'</a> · 시작 '.htmlspecialchars((string)$entry['started_at'],ENT_QUOTES,'UTF-8').' · 종료 '.htmlspecialchars((string)$entry['finished_at'],ENT_QUOTES,'UTF-8').' · 참가자·획득 보상은 상세 기록에서 확인</li>';
    }
    echo '</ul>';
    if(count($maze_history)===20) echo '<a href="?maze_before='.(int)$maze_history[19]['dm_id'].'">이전 미궁 기록</a>';
    echo '</section>';
}

// 내가 쓴 자비란 로그 확인
$sql_common = " from {$g5['dungeon_table']} dg, {$g5['dungeon_member_table']} dm, {$g5['dungeon_state_table']} ds where (dg.dg_title = ds.dg_title or dg.dg_id = ds.dg_id) and ds.ds_id = dm.ds_id and dm.ch_id = '{$character['ch_id']}'";
$sql_order = " order by dm.dm_datetime desc, dm.dm_id desc ";

// 전체 갯수 가져오기
$sql = " select count(*) as cnt {$sql_common} ";
$total = sql_fetch($sql);
$total = $total['cnt'];

$rows = 12;
$total_page  = ceil($total / $rows);  // 전체 페이지 계산
if (!$page) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$list = array();

for ($i=0; $row=sql_fetch_array($result); $i++) {
	$dg_list[] = $row;
}

$write_pages = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "?gr_id=$gr_id&amp;view=$view&amp;mb_id=$mb_id&amp;type=$type&amp;page=");

?>



<nav id="tab_list">
	<ul>
		<li>
			<a href="./index.php">
				나의로그
			</a>
		</li>
		<li>
			<a href="./log_favorite.php">
				관심로그
			</a>
		</li>
		<li class="on">
			<a href="./dungeon.php" class="point">
				던전로그
			</a>
		</li>
	</ul>
</nav>

<i class="style-line horizon"></i>

<ul class="dungeon-list">
	<? for($i=0; $i < count($dg_list); $i++) {
		$dg = $dg_list[$i];
		$state = "진행";
		$link = G5_URL."/dungeon/ground.php?ds_id={$dg['ds_id']}";

		if($dg['ds_state'] == 'E') {
			if($dg['dm_state'] == 'S') {
				$state = "대기";
				$state_type = "M";
			} else {
				$state = "완료";
				$state_type = "S";
			}
		} else {
			$state = "진행";
			$state_type = "E";
		}

		$d_result = "";
		if($dg['dm_state'] == 'E') {
			if($dg['dm_result'] == '') {
				$d_result = "이탈";
			} else if($dg['dm_result'] == "던전 공략에 실패하였습니다.") {
				$d_result = "실패";
			} else {
				$d_result = "완료";
			}
		} else {
			$d_result = "진행";
		}
	?>
	<li>
		<a href="<?=$link?>" class="item" data-type="M" data-state="<?=$state?>">
			<em class="thumb">
				<i data-result="<?=$d_result?>"><?=$d_result?></i>
				<img src="<?=$dg['dg_mon_img']?>" alt="" />
			</em>
			<strong>
				<?=$dg['dg_mon_name']?>
			</strong>
			<span class="date"><?=$dg['dm_datetime']?></span>
		</a>
	</li>
	<? } ?>
</ul>

<style>
.dungeon-list {display:block; position:relative; margin:-3px; padding:20px 0;}
.dungeon-list:after {content:""; display:block; clear:both;}
.dungeon-list li {display:block; position:relative; width:25%; float:left; padding:3px; box-sizing:border-box;}
.dungeon-list .thumb {display:block; position:relative; border:1px solid rgba(255,255,255,.05); padding-top:45%; overflow:hidden; background:rgba(255,255,255,.2);}
.dungeon-list .thumb img {display:block;  position:absolute; top:-25%; left:50%; height:150%; transform:translateX(-50%); -webkit-transform:translateX(-50%);}
.dungeon-list strong {display:block; font-size:13px; color:#eaeaea; text-align:center; padding:.3em 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500;}
.dungeon-list .date {display:block; font-size:12px; color:#fff; opacity:.5; text-align:center;}
.dungeon-list .thumb i {display:block; position:absolute; border-radius:3px; padding:2px 4px; font-size:11px; color:#fff; background:#bb5a5a; top:3px; left:3px;}
.dungeon-list .thumb i[data-result="진행"] {background:#666;}
.dungeon-list .thumb i[data-result="완료"] {background:#5e5edd;}

@media all and (max-width:550px) {
	.dungeon-list li {width:33.33%;}
}
</style>

<i class="style-line horizon"></i>

<?php echo $write_pages ?>





<?php
include_once('./_tail.php');
?>
