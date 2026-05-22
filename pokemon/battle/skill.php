<?include_once('../../common.php');
require_once(G5_PATH.'/head.sub.php');
@include("./menu.php");
$slot_info = array();
if(!$ph['ph_id']||$ph['is_battle']){?>
  <p class="warning">현재는 변경할 수 없습니다.</p>
<?}else{

$wa_color=["공격"=>"1","방어"=>"2","보조"=>"3"];
$skill_list = array();
$sql = "SELECT *
        FROM {$g5['inventory_table']} inven
        JOIN {$g5['item_table']} it ON it.it_id = inven.it_id
        JOIN {$g5['pokemon_waza_table']} wz ON wz.wa_id = it.it_value
        WHERE inven.ch_id = '{$character['ch_id']}'
        AND it.it_type = '기술머신'
        order by it.it_name asc";
$res = sql_query($sql);
while($row = sql_fetch_array($res)) $skill_list[] = $row;

$sql = "SELECT * FROM {$g5['pokemon_waza_has_table']} wh, {$g5['pokemon_waza_table']} wa WHERE wh.wa_id=wa.wa_id and wh.ph_id = '{$ph['ph_id']}' order by wa_order";
$wa_sql = sql_query($sql);

$slot_info = array();
$i=0;
while ($row = sql_fetch_array($wa_sql)){
  $slot_info[$i]=$row;
  $i++;
}
  $i=0;
?>

<style>
    @import url(./skill.css);
</style>
<div class="flexbox-main">
  <div>
    <ul id="skillList">
      <?php foreach($skill_list as $item):
        $val = $item['wa_category'];
        if($item['wa_category']=='공격'){ 
          $val.="/{$item['wa_type']}/{$item['wa_type2']}";
          if($item['wa_type']=='무'||$item['wa_type2']==$ph['po_type1']||$item['wa_type2']==$ph['po_type2']){
          }else{
            $item['wa_pp_max']=floor($item['wa_pp_max']/2);
          }
        }
        $colorClass = "skill-color-{$wa_color[$item['wa_category']]}";
      ?>
      <li class="skill-item <?= $colorClass ?>"
        data-wa-id="<?= $item['wa_id'] ?>"
        data-pp="<?= $item['wa_pp_max'] ?>"
        data-in-id="<?= $item['in_id'] ?>"
        data-name="<?= htmlspecialchars($item['wa_name']) ?>"
        data-content="<?= htmlspecialchars($item['wa_content']) ?>"
        data-value="<?= $val ?>"
        data-img="<?= htmlspecialchars($item['it_img']) ?>"
      >
        <img src="<?= htmlspecialchars($item['it_img']) ?>" alt="기술" class="item-img">
        <span class="skill-name"><?= htmlspecialchars($item['wa_name']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <div id="learnSkillBox" class="skill-detail-box">
      <div id="learnSkillName" class="item-detail-name">기술머신 선택</div>
      <div id="learnSkillValue" class="item-detail-value"></div>
      <div id="learnSkillContent" class="item-detail-content">배울 기술머신을 선택해 주세요.</div>
      <div id="skillDetailAnnounce"></div>
      <button type="button" id="skillConfirmBtn" style="margin-top:10px;" disabled>습득</button>
    </div>
  </div>
  <div>
    <div class="skill-slots-grid">
      <div class="slots-grid-wrap">
        <?php for($i=0;$i<4;$i++): ?>
        <div class="slot <?= $slot_info[$i] ? "skill-color-".intval($wa_color[$slot_info[$i]['wa_category']]) : "" ?>" data-index="<?= $i ?>" tabindex="0">
          <span class="acquired-name"><?= $slot_info[$i] ? htmlspecialchars($slot_info[$i]['wa_name']) : "비어 있음" ?></span>
          <span class="acquired_pp"><?= $slot_info[$i] ? "PP {$slot_info[$i]['pp_max']}" : "비어 있음" ?></span>
          <input type="hidden" name="skill_items[]" id="skill_<?= $i ?>" value="<?= $slot_info[$i] ? $slot_info[$i]['wa_id'] : "" ?>">
        </div>
        <?php endfor; ?>
      </div>
    </div>
    <div id="slotDetailBox" class="skill-detail-box">
      <div id="slotDetailName" class="item-detail-name">슬롯 선택</div>
      <div id="slotDetailValue" class="item-detail-value"></div>
      <div id="slotDetailContent" class="item-detail-content">배울 슬롯을 선택해 주세요.</div>
      <div id="slotDetailMsg"></div>
    </div>
  </div>
</div>
<script>
var PH_ID = <?= isset($ph['ph_id']) ? (int)$ph['ph_id'] : 0 ?>;

var waColorMap = <?= json_encode($wa_color, JSON_UNESCAPED_UNICODE) ?>;
var acquired = [
  <?php for($i=0;$i<4;$i++): ?>
    <?= $slot_info[$i] ? intval($slot_info[$i]['wa_id']) : "null" ?>,
  <?php endfor; ?>
];
var acquiredName = [
  <?php for($i=0;$i<4;$i++): ?>
    <?= $slot_info[$i] ? '"'.addslashes($slot_info[$i]['wa_name']).'"' : 'null' ?>,
  <?php endfor; ?>
];
var acquiredValue = [
  <?php for($i=0;$i<4;$i++): 
        $val=$slot_info[$i]['wa_category'];
        if($slot_info[$i]['wa_category']=='공격'){ $val.="/{$slot_info[$i]['wa_type']}/{$slot_info[$i]['wa_type2']}";}
    ?>
    <?= $slot_info[$i] ? '"'.addslashes($val).'"' : "null" ?>,
  <?php endfor; ?>
];
var acquiredContent = [
  <?php for($i=0;$i<4;$i++): ?>
    <?= $slot_info[$i] ? '"'.addslashes($slot_info[$i]['wa_content']).'"' : 'null' ?>,
  <?php endfor; ?>
];
var selectedSkill = null;
var selectedSlotIdx = null;
updateSkillListUI();
// 기술 리스트 클릭
document.addEventListener('click', function(e){
  let t = e.target.closest('.skill-item:not(.disabled)');
  if(!t) return;
  selectedSkill = {
    wa_id: parseInt(t.getAttribute('data-wa-id')),
    pp: parseInt(t.getAttribute('data-pp')),
    in_id: parseInt(t.getAttribute('data-in-id')),
    name: t.getAttribute('data-name'),
    value: t.getAttribute('data-value'),
    content: t.getAttribute('data-content')
  };
  document.getElementById('learnSkillName').textContent = selectedSkill.name;
  document.getElementById('learnSkillValue').innerHTML = selectedSkill.value+'/PP '+selectedSkill.pp;
  document.getElementById('learnSkillContent').innerHTML = selectedSkill.content.replace(/\\n/g,'<br>');
  document.getElementById('learnSkillBox').style.display = '';
  document.getElementById('skillDetailMsg').textContent = '';
  document.querySelectorAll('.skill-item').forEach(x=>x.classList.remove('selected'));
  t.classList.add('selected');
  if(selectedSlotIdx === null) {
    document.getElementById('skillConfirmBtn').disabled = true;
    document.getElementById('skillDetailAnnounce').innerHTML = "<span style='color:#b6001a'>습득할 슬롯을 선택해 주세요.</span>";
    document.getElementById('slotDetailBox').style.display = 'none';
    document.querySelectorAll('.slot').forEach(x=>x.classList.remove('selected'));
  } else {
    document.getElementById('skillConfirmBtn').disabled = false;
    document.getElementById('skillDetailAnnounce').innerHTML =
      "<b style='color:#1c67d4'>" + (selectedSlotIdx+1) + "번 슬롯</b>에 <b style='color:#1c67d4'>" + selectedSkill.name + "</b>을(를) 습득합니다.";
    showSlotDetail(selectedSlotIdx);
  }
});

// 슬롯 클릭
document.querySelectorAll('.slot').forEach(function(div){
  div.onclick = function(){
    let idx = parseInt(this.getAttribute('data-index'));
    selectedSlotIdx = idx;
    document.querySelectorAll('.slot').forEach(x=>x.classList.remove('selected'));
    this.classList.add('selected');
    showSlotDetail(idx);
    if(selectedSkill) {
      document.getElementById('skillConfirmBtn').disabled = acquired.includes(selectedSkill.wa_id);
      document.getElementById('skillDetailAnnounce').innerHTML =
        "<b style='color:#1c67d4'>" + (selectedSlotIdx+1) + "번 슬롯</b>에 <b style='color:#1c67d4'>" + selectedSkill.name + "</b>을(를) 습득합니다.";
    } else {
      document.getElementById('skillConfirmBtn').disabled = true;
      document.getElementById('skillDetailAnnounce').innerHTML = "<span style='color:#b6001a'>배울 기술을 먼저 선택하세요</span>";
    }
  }
});

// 슬롯 정보 비교창
function showSlotDetail(idx){
  let name = acquiredName[idx], value = acquiredValue[idx], content = acquiredContent[idx];
  document.getElementById('slotDetailBox').style.display = '';
  document.getElementById('slotDetailName').textContent = name ? name : "아직 기술을 배우지 않았다";
  document.getElementById('slotDetailValue').textContent = name ? value : '';
  document.getElementById('slotDetailContent').innerHTML = name ? content.replace(/\\n/g,'<br>') : '';
  document.getElementById('slotDetailMsg').innerHTML = "슬롯 " + (idx+1) + (name ? "번에 습득한 기술입니다." : "번에 아직 기술을 배우지 않았습니다.");
}

// 습득 버튼
document.getElementById('skillConfirmBtn').onclick = function(){
  if (selectedSlotIdx === null || !selectedSkill) return;
  if (acquired.includes(selectedSkill.wa_id)) { alert("이미 이 기술을 습득하고 있습니다."); return; }

  const prevName = acquiredName[selectedSlotIdx];
  const msg = prevName
    ? `"${prevName}"을(를) 잊고 "${selectedSkill.name}"을(를) 배울까? ※ 교체된 기존 기술은 복구할 수 없습니다.`
    : `"${selectedSkill.name}"을(를) 이 슬롯에 배울까? ※ 습득한 기술은 해제할 수 없으며, 새로운 기술로만 교체할 수 있습니다.`;
  if (!confirm(msg)) return;

  var xhr = new XMLHttpRequest();

  xhr.open("POST", "<?=G5_URL?>/pokemon/battle/skill_update.php", true);

  xhr.responseType = 'json'; 
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
  xhr.withCredentials = true;

  xhr.onload = function(){
    const st = xhr.status;
    if (st !== 200){
      console.error("XHR status:", st, "headers:", xhr.getAllResponseHeaders(), "body:", xhr.responseText);
      if (st===301||st===302){ alert("로그인이 필요하거나 경로가 리다이렉트되고 있습니다."); }
      else if (st===401||st===403){ alert("권한이 없습니다. 로그인 세션을 확인해 주세요."); }
      else if (st===404){ alert("요청 경로(파일)가 존재하지 않습니다."); }
      else if (st===405){ alert("허용되지 않은 메서드입니다(POST 필요)."); }
      else if (st>=500){ alert("서버 내부 오류(500번대). 서버 로그를 확인해 주세요."); }
      else { alert("서버 통신 실패("+st+")"); }
      return;
    }
    const res = xhr.response;
    if (!res || typeof res !== 'object'){
      console.error("Non-JSON body:", xhr.responseText);
      alert("서버 응답 오류 또는 실패."); return;
    }

    if (res.result === "ok") {
      // 성공 반영
      acquired[selectedSlotIdx] = selectedSkill.wa_id;
      acquiredName[selectedSlotIdx] = selectedSkill.name;
      acquiredValue[selectedSlotIdx] = selectedSkill.value;
      acquiredContent[selectedSlotIdx] = selectedSkill.content;

      const slotDiv = document.querySelector('.slot[data-index="'+selectedSlotIdx+'"]');
      if (slotDiv){
        const nm = slotDiv.querySelector('.acquired-name');
        if (nm) nm.textContent = selectedSkill.name;

        const pp = slotDiv.querySelector('.acquired_pp');
        if (pp) pp.textContent = 'PP ' + (selectedSkill.pp || '');

        slotDiv.classList.remove('skill-color-1','skill-color-2','skill-color-3','skill-color-4');
        const _baseType = selectedSkill.value ? selectedSkill.value.split('/')[0] : '';
        if (_baseType && waColorMap[_baseType]) slotDiv.classList.add('skill-color-'+waColorMap[_baseType]);
      }

      document.querySelectorAll('#skillList .skill-item').forEach(li=>{
        if (parseInt(li.getAttribute('data-in-id')) === selectedSkill.in_id) li.remove();
      });

      document.getElementById('learnSkillBox').style.display = 'none';
      document.getElementById('slotDetailBox').style.display = 'none';
      document.getElementById('skillDetailAnnounce').innerHTML = "";
      document.querySelectorAll('.slot').forEach(x=>x.classList.remove('selected'));
      document.querySelectorAll('.skill-item').forEach(x=>x.classList.remove('selected'));
      selectedSkill = null; selectedSlotIdx = null;
      updateSkillListUI();
    } else {
      alert(res.msg || "습득 처리에 실패했습니다.");
    }
  };

  xhr.onerror = function(){
    console.error("XHR network error:", xhr.status, xhr.responseText);
    alert("네트워크 오류");
  };

  // ★ ph_id는 PH_ID(서버에서 보장된 값) 사용
  const body =
    "ph_id="+encodeURIComponent(PH_ID)+
    "&slot="+encodeURIComponent(selectedSlotIdx)+
    "&in_id="+encodeURIComponent(selectedSkill.in_id)+
    "&wa_id="+encodeURIComponent(selectedSkill.wa_id);

  xhr.send(body);
};


function updateSkillListUI() {
  document.querySelectorAll('#skillList .skill-item').forEach(function(el){
    let waId = parseInt(el.getAttribute('data-wa-id'));
    if(acquired && acquired.includes(waId)) el.classList.add('disabled');
    else el.classList.remove('disabled');
  });
}
</script>

<?}
require_once(G5_PATH.'/tail.sub.php');?>