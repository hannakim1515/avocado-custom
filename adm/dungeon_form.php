<?php
$sub_menu = '730100';
include_once './_common.php';
require_once G5_PATH.'/lib/monster_shared.lib.php';
require_once __DIR__.'/dungeon_admin.lib.php';
require_once __DIR__.'/dungeon_maze.admin.lib.php';
auth_check($auth[$sub_menu], 'w');
$w=$_GET['w']??'';$dg_id=(int)($_GET['dg_id']??0);$maze_ready=maze_readiness()['ready'];
$token=get_token();
if($w==='u') {
    $dungeon=sql_fetch('SELECT * FROM '.$g5['dungeon_table'].' WHERE dg_id='.$dg_id);
    if(empty($dungeon['dg_id']))alert('존재하지 않는 던전입니다.');
} else {
    $dungeon=array();
    $columns=sql_query('SHOW COLUMNS FROM '.$g5['dungeon_table']);
    while($column=sql_fetch_array($columns))$dungeon[$column['Field']]=$column['Default']??(preg_match('/^(int|tinyint)/',$column['Type'])?0:'');
    $dungeon['dg_use']=1;$dungeon['dg_count']=4;$dungeon['dg_per_e']=100;
}
$regions=da_dungeon_regions(explode('||',$dungeon['ma_ids']??''));
$stats=monster_rows('SELECT st_id,st_name FROM '.$g5['status_config_table'].' ORDER BY st_id');
$items=monster_rows("SELECT it_id,it_name FROM ".$g5['item_table']." WHERE it_use='Y' ORDER BY it_category,it_type,it_name");
$rewards=$dg_id?monster_rows('SELECT * FROM '.$g5['dungeon_item_table'].' WHERE dg_id='.$dg_id.' ORDER BY di_id'):array();
$money=(string)($config['cf_money_pice']??'') ?: '포인트';
$g5['title']=$w==='u'?'던전 정보 수정':'던전 정보 등록';
include_once './admin.head.php';
echo '<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/combat_editor_shell.css"><script src="'.G5_ADMIN_URL.'/js/combat_editor_shell.js"></script>';
echo '<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/dungeon_form.css">';
include_once './990_unified_menu_bootstrap.php';
if(!empty($_GET['saved']))echo '<div class="local_desc01 local_desc" role="status">저장했습니다. 다음에 출발하는 탐험부터 적용됩니다.</div>';
function dungeon_reward_row($item,$template=false) {
    $name=$template?'':get_item_name($item['it_id']);
    $disabled=$template?' disabled':'';
    ?>
    <tr <?=$template?'class="original" hidden':''?>>
      <td data-label="아이템"><input type="text" name="it_name[]" value="<?=da_h($name)?>" class="frm_input" list="it_name_list" aria-label="보상 아이템" placeholder="등록된 아이템 이름" <?=$disabled?>></td>
      <td data-label="수량"><input type="number" name="di_count[]" value="<?=da_h($item['di_count']??1)?>" class="frm_input" min="1" max="100" aria-label="보상 수량" <?=$disabled?>> 개</td>
      <td data-label="획득 확률"><input type="number" name="di_percent[]" value="<?=da_h($item['di_percent']??100)?>" class="frm_input" min="0" max="100" step="0.01" aria-label="획득 확률 (%)" <?=$disabled?>> %</td>
      <td data-label="적용 예시" class="reward-example"></td>
      <td class="reward-control"><button type="button" class="btn btn_02 btn-item-dell" aria-label="보상 행 삭제" onclick="fn_del_itemFrom(this)">삭제</button></td>
    </tr>
    <?php
}
?>
<datalist id="it_name_list"><?php foreach($items as $item): ?><option value="<?=da_h($item['it_name'])?>"></option><?php endforeach; ?></datalist>
<form name="fdungeonform" id="fdungeonform" action="./dungeon_form_update.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?=da_h($w)?>"><input type="hidden" name="token" value="<?=da_h($token)?>"><input type="hidden" name="dg_id" value="<?=$dg_id?>">
<?php foreach(array('sfl','stx','sst','sod','page') as $key): ?><input type="hidden" name="<?=$key?>" value="<?=da_h($$key??'')?>"><?php endforeach; ?>
<p class="editor-intro">이름과 참가 인원을 입력하고, 몬스터·탐험·보상을 설정하세요. 탭은 자유롭게 이동할 수 있으며 <strong>모든 변경은 한 번에 저장</strong>됩니다.</p>
<nav class="dungeon-form-tabs" role="tablist" aria-label="던전 편집 항목">
 <button type="button" data-dungeon-tab="anc_001" aria-controls="anc_001" aria-selected="true">기본 설정</button>
 <?php if(monster_shared_ready()): ?><button type="button" data-dungeon-tab="anc_002" aria-controls="anc_002" aria-selected="false">몬스터 출현</button><?php endif; ?>
 <?php if($maze_ready): ?><button type="button" data-dungeon-tab="maze-settings" aria-controls="maze-settings" aria-selected="false">탐험 설정</button><?php endif; ?>
 <button type="button" data-dungeon-tab="anc_003" aria-controls="anc_003" aria-selected="false">보상 설정</button>
</nav>
<p class="local_desc01 local_desc editor-error" id="dungeon-form-error" role="alert" tabindex="-1" hidden></p>
<section id="anc_001">
<h2 class="h2_frm">기본 설정</h2>
<p class="editor-section-help">모든 던전은 방 탐험 → 몬스터 전투 → 클리어로 진행합니다.</p>
<div class="tbl_frm01 tbl_wrap editor-fields"><table><caption>던전 기본 정보</caption><colgroup><col class="grid_4"><col></colgroup><tbody>
<tr><th scope="row"><label for="dg_title">던전 이름 <span class="required-label">필수</span></label></th><td><input id="dg_title" name="dg_title" type="text" class="frm_input required editor-name" required maxlength="255" value="<?=da_h($dungeon['dg_title'])?>" placeholder="예: 시계탑의 지하 미궁"></td></tr>
<tr><th scope="row"><label for="description">소개</label></th><td><textarea id="description" name="description" rows="3" placeholder="입장 전에 보여줄 장소 설명"><?=da_h($dungeon['dg_description']??'')?></textarea><p class="frm_info">소개는 비워도 저장할 수 있습니다.</p></td></tr>
<tr><th scope="row"><label for="dg_count">최대 참가 인원</label></th><td><input id="dg_count" name="dg_count" type="number" class="frm_input" min="1" max="999" required value="<?=da_h($dungeon['dg_count'])?>"> 명 <p class="frm_info">대기실에 들어올 수 있는 인원입니다. 최소 출발 인원은 탐험 설정에서 정합니다.</p></td></tr>
<tr><th scope="row"><label for="dg_rank">랭크</label></th><td><input id="dg_rank" name="dg_rank" type="text" class="frm_input" maxlength="255" value="<?=da_h($dungeon['dg_rank'])?>" placeholder="예: A"><p class="frm_info">목록에 표시할 난이도 표기입니다. 비워도 됩니다.</p></td></tr>
<tr><th scope="row">사용 여부</th><td><label class="editor-check"><input id="dg_use" name="dg_use" type="checkbox" value="1" <?=$dungeon['dg_use']?'checked':''?>> 새 던전 발생에 사용</label></td></tr>
</tbody></table></div>
<details class="editor-advanced" <?=!empty($config['cf_dungeon_map'])?'open':''?>>
<summary>지역·발생 조건</summary>
<p class="frm_info"><?=empty($config['cf_dungeon_map'])?'현재 공통 설정은 지역 제한 없이 입장하도록 되어 있습니다. 지역을 지정하려면 던전 목록의 공통 설정에서 지역 사용을 켜세요.':'현재 공통 설정은 지정된 지역에서만 던전에 입장하도록 되어 있습니다.'?></p>
<div class="tbl_frm01 tbl_wrap editor-fields"><table><caption>던전 지역과 발생 조건</caption><colgroup><col class="grid_4"><col></colgroup><tbody>
<tr><th scope="row">발생 지역</th><td><div class="editor-region-list"><?php foreach($regions as $region): ?><label class="editor-check"><input type="checkbox" name="ma_ids[]" value="<?=$region['id']?>" <?=strpos($dungeon['ma_ids'],'||'.$region['id'].'||')!==false?'checked':''?>> <?=da_h($region['name'])?></label><?php endforeach; ?></div><?php if(!$regions): ?><p class="frm_info">출현 가능한 지역이 없습니다. <a href="map_list.php">지역 관리</a>에서 지역의 사용·던전을 설정하세요.</p><?php endif; ?></td></tr>
<tr><th scope="row">발생 추첨 구간</th><td><div class="editor-field-pair"><label for="dg_per_s">시작 <input id="dg_per_s" name="dg_per_s" type="number" min="0" max="100" required value="<?=da_h($dungeon['dg_per_s'])?>"></label><span>~</span><label for="dg_per_e">끝 <input id="dg_per_e" name="dg_per_e" type="number" min="0" max="100" required value="<?=da_h($dungeon['dg_per_e'])?>"></label></div><p class="frm_info" id="dungeon-spawn-example"></p></td></tr>
<tr><th scope="row">참가자 능력치 보정</th><td><div class="editor-field-pair"><label for="dg_status" class="sound_only">보정할 능력치</label><select id="dg_status" name="dg_status"><option value="">사용 안 함</option><?php foreach($stats as $stat): ?><option value="<?=$stat['st_id']?>" <?=(int)$dungeon['dg_status']===(int)$stat['st_id']?'selected':''?>><?=da_h($stat['st_name'])?></option><?php endforeach; ?></select><label for="dg_status_type" class="sound_only">보정 방식</label><select id="dg_status_type" name="dg_status_type"><option value="+" <?=$dungeon['dg_status_type']!=='x'?'selected':''?>>더하기</option><option value="x" <?=$dungeon['dg_status_type']==='x'?'selected':''?>>곱하기</option></select><label for="dg_status_value" class="sound_only">보정 값</label><input id="dg_status_value" name="dg_status_value" type="number" step="any" value="<?=da_h($dungeon['dg_status_value'])?>" placeholder="보정 값"></div><p class="frm_info">출발할 때 참가자의 지정 능력치에 적용됩니다. 예: HP에 더하기 10 → HP가 10 증가합니다.</p></td></tr>
</tbody></table></div></details>
</section>
<?php if(monster_shared_ready()): ?><section id="anc_002"><h2 class="h2_frm">몬스터 출현</h2><?php require __DIR__.'/dungeon_pool.fields.php'; ?></section><?php endif; ?>
<?php if($maze_ready): ?><fieldset id="maze-settings"><?php require __DIR__.'/dungeon_maze.view.php'; ?></fieldset><?php endif; ?>
<section id="anc_003">
<h2 class="h2_frm">클리어 보상</h2><p class="editor-section-help">클리어한 생존 참가자에게 지급합니다. 포인트와 아이템을 함께 설정할 수 있습니다.</p>
<div class="tbl_frm01 tbl_wrap editor-fields"><table><caption>클리어 포인트</caption><colgroup><col class="grid_4"><col></colgroup><tbody><tr><th scope="row"><label for="dg_point"><?=da_h($money)?> 보상</label></th><td><input id="dg_point" name="dg_point" type="number" class="frm_input" min="0" max="2147483647" required value="<?=da_h($dungeon['dg_point'])?>"> <?=da_h($money)?><p class="frm_info">참가자마다 지급되는 금액입니다. 0이면 지급하지 않습니다.</p></td></tr></tbody></table></div>
<div class="editor-section-toolbar"><h3>아이템 보상</h3><button type="button" class="btn btn_02 btn-item-add" onclick="fn_add_itemFrom()">아이템 추가</button></div>
<p class="frm_info">아이템마다 따로 추첨합니다. 예: 회복약 2개·30%는 클리어할 때 30% 확률로 2개를 지급합니다. 100%는 확정 지급이며 확률의 합을 100%로 맞출 필요가 없습니다.</p>
<div class="tbl_head01 tbl_wrap editor-rewards"><table id="itemAddTable"><caption>클리어 아이템 보상</caption><colgroup><col><col style="width:105px"><col style="width:130px"><col style="width:260px"><col style="width:70px"></colgroup><thead><tr><th scope="col">아이템 이름</th><th scope="col">수량</th><th scope="col">획득 확률</th><th scope="col">현재 적용 예시</th><th scope="col">관리</th></tr></thead><tbody><?php dungeon_reward_row(array(),true);foreach($rewards as $reward)dungeon_reward_row($reward); ?></tbody></table></div>
<p id="dungeon-reward-empty" class="editor-empty" <?=$rewards?'hidden':''?>>아이템 보상이 없습니다. 포인트만 지급하거나, 아이템 추가로 클리어 보상을 등록하세요.</p>
</section>
<div class="btn_confirm01 btn_confirm editor-save-bar"><span id="dungeon-save-state" role="status">입력한 설정을 한 번에 저장합니다.</span><div><a href="./dungeon_list.php?<?=da_h($qstr??'')?>">목록</a><input type="submit" value="저장" class="btn_submit" accesskey="s"></div></div>
</form>
<script src="<?=G5_ADMIN_URL?>/js/dungeon_form_editor.js"></script>
<?php include_once './admin.tail.php'; ?>
