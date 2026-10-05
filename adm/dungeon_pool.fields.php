<?php
if(!defined('_GNUBOARD_'))exit;
$pool_rows=$dg_id?monster_rows('SELECT * FROM '.monster_table('dungeon_monster_pool').' WHERE dg_id='.(int)$dg_id.' AND enabled=1'):array();
$pool_rows=array_column($pool_rows,null,'monster_id');
$available_monsters=monster_rows('SELECT monster_id,name,enabled FROM '.monster_table('monster_master').' WHERE profile IS NOT NULL ORDER BY name,monster_id');
$selected_monsters=array_filter($available_monsters,function($monster)use($pool_rows){return isset($pool_rows[$monster['monster_id']]);});
function dungeon_pool_row($monster=array(),$entry=array()) {
    $id=(int)($monster['monster_id']??0);$name=$monster['name']??'';$enabled=!empty($monster['enabled']);
    ?>
    <tr data-monster-row data-monster-id="<?=$id?>" data-monster-name="<?=da_h($name)?>" data-enabled="<?=$enabled?'1':'0'?>">
    <td data-label="몬스터"><strong data-pool-name><?=da_h($name)?></strong><input type="hidden" data-pool-field="enabled" name="pool[<?=$id?>][enabled]" value="1"><p class="frm_info" data-pool-disabled <?=$enabled?'hidden':''?>>몬스터 사용이 꺼져 있습니다. 몬스터 수정에서 사용을 켜거나 이 던전에서 제외하세요.</p></td>
    <td data-label="가중치"><input class="frm_input" data-pool-field="weight" aria-label="<?=da_h($name)?> 가중치" type="number" name="pool[<?=$id?>][weight]" min="1" max="1000000" step="1" required value="<?=(int)($entry['weight']??1)?>"><span class="pool-ratio frm_info"></span></td>
    <td data-label="동시 출현 수"><div class="pool-count-fields"><label>최소 <input class="frm_input" data-pool-field="min_count" aria-label="<?=da_h($name)?> 최소 출현 수" type="number" name="pool[<?=$id?>][min_count]" min="1" max="20" step="1" required value="<?=(int)($entry['min_count']??1)?>"></label><label>최대 <input class="frm_input" data-pool-field="max_count" aria-label="<?=da_h($name)?> 최대 출현 수" type="number" name="pool[<?=$id?>][max_count]" min="1" max="20" step="1" required value="<?=(int)($entry['max_count']??1)?>"></label></div><span class="pool-count-example frm_info"></span></td>
    <td data-label="보스 지정"><label class="editor-check"><input type="checkbox" data-pool-field="boss" aria-label="<?=da_h($name)?> 보스 지정" name="pool[<?=$id?>][boss]" value="1" <?=!empty($entry['is_boss'])?'checked':''?>> 보스</label></td>
    <td class="pool-control"><a class="btn btn_03" data-pool-edit href="monster_form.php?monster_id=<?=$id?>" target="_blank" rel="noopener" aria-label="<?=da_h($name)?> 수정 · 새 창">수정<span class="sound_only"> · 새 창</span></a> <button type="button" class="btn btn_02" data-pool-remove aria-label="<?=da_h($name)?> 던전 출현에서 제외">제외</button></td></tr>
    <?php
}
?>
<input type="hidden" name="pool_present" value="1">
<p class="editor-section-help">이 던전에 등장할 몬스터를 추가하세요. 능력치와 행동은 <a href="monster.php">몬스터 관리</a>에서 수정합니다.</p>
<div class="editor-section-toolbar"><h3>이 던전의 몬스터</h3><button type="button" class="btn btn_02" id="pool-add-toggle" aria-controls="pool-add-panel" aria-expanded="false">몬스터 추가</button></div>
<div id="pool-add-panel" class="editor-pool-picker" hidden>
 <p class="frm_info">공용 목록에서 선택하면 이 던전의 출현 목록에 추가됩니다. 이미 추가한 몬스터는 선택 목록에서 빠집니다.</p>
 <label for="pool-add-search">이름 검색 <input id="pool-add-search" type="search" class="frm_input" placeholder="몬스터 이름으로 검색" autocomplete="off"></label>
 <label for="pool-add-choice">추가할 몬스터 <select id="pool-add-choice" size="6"></select></label>
 <p id="pool-add-empty" class="frm_info" hidden></p>
 <div class="pool-picker-actions"><button type="button" class="btn btn_02" id="pool-add-confirm">선택한 몬스터 추가</button> <button type="button" class="btn btn_02" id="pool-add-close">닫기</button> <a href="monster_form.php" target="_blank" rel="noopener">새 몬스터 등록<span class="sound_only"> · 새 창</span></a></div>
 <p id="pool-add-message" class="frm_info" role="status"></p>
</div>
<p class="frm_info">가중치로 몬스터 종류를 선택한 뒤, 설정한 최소~최대 마릿수가 한 전투에 함께 등장합니다. 예: 최소 1·최대 3이면 1~3마리 중 무작위, 최소 2·최대 2이면 항상 2마리입니다. 일반 몬스터와 보스에 각각 적용됩니다. 제외는 이 던전의 출현 연결만 해제합니다.</p>
<div class="tbl_head01 tbl_wrap editor-pool"><table><caption>이 던전에 출현할 몬스터</caption><colgroup><col><col style="width:155px"><col style="width:210px"><col style="width:100px"><col style="width:150px"></colgroup><thead><tr><th scope="col">몬스터</th><th scope="col">가중치 / 선택 비율</th><th scope="col">동시 출현 수</th><th scope="col">보스 지정</th><th scope="col">관리</th></tr></thead><tbody>
<?php foreach($selected_monsters as $monster)dungeon_pool_row($monster,$pool_rows[$monster['monster_id']]); ?>
<tr id="pool-empty" <?=$selected_monsters?'hidden':''?>><td colspan="5" class="empty_table">출현할 몬스터가 없습니다. 위의 몬스터 추가 버튼에서 선택하세요.</td></tr>
</tbody></table></div>
<template id="pool-row-template"><?php dungeon_pool_row(); ?></template>
<script type="application/json" id="pool-monster-catalog"><?=json_encode($available_monsters,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script>
<p id="pool-selection-hint" class="editor-inline-note"></p>
