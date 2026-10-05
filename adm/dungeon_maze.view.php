<?php
if(!defined('_GNUBOARD_'))exit;
$options=da_options();
echo '<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/dungeon_maze.css">';
$dg=$dungeon;
$settings=array_replace(maze_defaults($dg),!empty($dg['dg_settings'])?maze_data($dg['dg_settings']):array());
if(empty($dg['dg_id']))$settings['encounter_percent']=0;
function maze_admin_field($name,$label,$value,$type='number',$min=0) {
    echo '<tr><th scope="row"><label for="'.da_h($name).'">'.da_h($label).'</label></th><td><input class="frm_input" id="'.da_h($name).'" name="'.da_h($name).'" type="'.$type.'" '.($type==='number'?'min="'.$min.'" step="1"':'').' value="'.da_h($value).'"></td></tr>';
}
function maze_admin_table($caption) {echo '<div class="tbl_frm01 tbl_wrap editor-fields"><table><caption>'.da_h($caption).'</caption><colgroup><col class="grid_4"><col></colgroup><tbody>';}
function maze_admin_table_end(){echo '</tbody></table></div>';}
?>
<section id="maze-basic"><h2 class="h2_frm">탐험 설정</h2><p class="editor-section-help">인원·방 수·전투 출현을 정하세요. 이미 출발한 탐험의 지도와 전투는 유지됩니다.</p>
<?php maze_admin_table('탐험 기본 설정'); ?>
<tr><th scope="row"><label for="min_players">최소 출발 인원</label></th><td><input id="min_players" name="min_players" type="number" min="1" max="999" required value="<?=(int)$settings['min_players']?>"> 명 <p id="maze-player-example" class="frm_info"></p></td></tr>
<tr><th scope="row">전체 방 수</th><td><div class="editor-field-pair"><label for="rooms_min">최소 <input id="rooms_min" name="rooms_min" type="number" min="3" max="500" required value="<?=(int)$settings['rooms_min']?>"></label><span>~</span><label for="rooms_max">최대 <input id="rooms_max" name="rooms_max" type="number" min="3" max="500" required value="<?=(int)$settings['rooms_max']?>"></label><span>개</span></div><p class="frm_info">출발할 때 이 범위에서 방 수를 정하고 지도를 한 번 만듭니다.</p><p class="frm_info" id="maze-layout-hint"></p></td></tr>
<tr><th scope="row"><label for="encounter_percent">일반 몬스터 출현 확률</label></th><td><input id="encounter_percent" name="encounter_percent" type="number" min="0" max="100" step="1" required value="<?=(int)$settings['encounter_percent']?>"> % <p id="maze-encounter-example" class="frm_info"></p></td></tr>
<tr><th scope="row">마지막 보스</th><td><label class="editor-check"><input type="checkbox" name="boss" value="1" <?=$settings['boss']?'checked':''?>> 마지막에 보스 전투 사용</label><p id="maze-boss-example" class="frm_info"></p></td></tr>
<?php maze_admin_table_end(); ?>
<details class="maze-advanced editor-advanced"><summary>고급 설정 · 갈림길, 도주, 함정, 방 가중치</summary><p class="frm_info">필요한 값만 조정하세요. 현재 값을 유지해도 탐험 지도를 만들 수 있습니다.</p>
<?php maze_admin_table('탐험 고급 설정'); ?>
<tr><th scope="row">갈림길 수</th><td><div class="editor-field-pair"><label for="branches_min">최소 <input id="branches_min" name="branches_min" type="number" min="0" required value="<?=(int)$settings['branches_min']?>"></label><span>~</span><label for="branches_max">최대 <input id="branches_max" name="branches_max" type="number" min="0" required value="<?=(int)$settings['branches_max']?>"></label><span>개</span></div><p class="frm_info">갈림길마다 방 2개가 필요합니다. 방 수가 부족하면 값을 늘리거나 갈림길을 줄이세요.</p></td></tr>
<?php maze_admin_field('dead_end_percent','막다른 길 확률 (%)',$settings['dead_end_percent']);maze_admin_field('escape_percent','도주 성공률 (%)',$settings['escape_percent']);maze_admin_field('trap_damage','기본 함정 피해',$settings['trap_damage']);maze_admin_table_end(); ?>
<h3 class="editor-subheading">방 종류별 비율</h3><p class="frm_info">가중치를 모두 더한 값에서 각 방의 비율을 계산합니다. 예: 빈 방 3·조사 3·보물 2·함정 1 → 빈 방 약 33.3%, 보물 약 22.2%.</p>
<?php maze_admin_table('방 종류 가중치');foreach(array('EMPTY'=>'빈 방','SEARCH'=>'조사 방','TREASURE'=>'보물 방','TRAP'=>'함정 방') as $key=>$label)maze_admin_field('weights['.$key.']',$label.' 가중치',$settings['weights'][$key]);maze_admin_table_end(); ?>
<p id="maze-weight-example" class="editor-inline-note"></p>
</details>
<details class="editor-advanced"><summary>배경·공통 제한시간</summary>
<?php maze_admin_table('탐험 배경과 제한시간');maze_admin_field('background','배경 이미지 주소',$settings['background'],'url'); ?>
<tr><th scope="row"><label for="background_file">배경 업로드</label></th><td><input id="background_file" name="background_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif"><p class="frm_info">이미지 주소를 입력하거나 파일을 올리세요. 둘 다 비우면 배경 이미지 없이 표시합니다.</p></td></tr>
<tr><th scope="row">공통 행동 제한시간</th><td><?php $meta=maze_one('SELECT action_timeout FROM '.maze_table('meta').' WHERE singleton=1'); ?><?=empty($meta['action_timeout'])?'제한 없음':(int)$meta['action_timeout'].'초'?> <a href="dungeon_maze_common.php" target="_blank" rel="noopener">공통 설정에서 변경<span class="sound_only"> · 새 창</span></a><p class="frm_info">모든 던전에 적용되는 값입니다.</p></td></tr>
<?php maze_admin_table_end(); ?></details>
</section>
<section id="maze-events"><h2 class="h2_frm">방 이벤트·조사 선택지</h2><p class="editor-section-help">조사 문구, 아이템 획득, 함정, 전투 같은 방의 결과를 추가하세요. 선택지를 만들면 참가자가 고른 선택지의 결과를 적용합니다. 전투 몬스터는 <strong>몬스터 출현 탭에 등록한 몬스터만</strong> 선택할 수 있으며, 동시 출현 수도 같은 설정을 사용합니다.</p><p id="maze-event-monster-warning" class="editor-inline-note" role="status" hidden></p>
<?php da_json_editor('events','events',$settings['events']); ?><p class="frm_info">상태이상 효과는 <a href="monster_status.php" target="_blank" rel="noopener">상태이상 목록<span class="sound_only"> · 새 창</span></a>에서 공통으로 관리합니다.</p></section>
<script type="application/json" id="dungeon-editor-options"><?=json_encode($options,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script><script src="<?=G5_ADMIN_URL?>/js/dungeon_admin.js"></script>
