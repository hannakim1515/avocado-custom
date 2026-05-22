<?include_once('../../common.php');
require_once(G5_PATH.'/head.sub.php');

@include("./menu.php");
@include("./pokemon.inc.php");

if($ph['ph_id']){
  $wa_color=["공격"=>"1","방어"=>"2","보조"=>"3"];
  $sql = "SELECT * FROM {$g5['pokemon_waza_has_table']} wh, {$g5['pokemon_waza_table']} wa WHERE wh.wa_id = wa.wa_id and wh.ph_id = '{$ph['ph_id']}' order by wa_order";
  $wa_sql = sql_query($sql);

  $slot_info = array();

  $i=0;
  while ($row = sql_fetch_array($wa_sql)){
    $slot_info[$i]=$row;
    $i++;
  }
  $i=0;
?>
  <style> @import url(./skill.css);</style>
  <div class="flexbox-main">
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
            <div id="slotDetailContent" class="item-detail-content">상세 정보를 보려면 슬롯을 선택해 주세요.</div>
        </div>
    </div>
  </div>

  <script>
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
        in_id: parseInt(t.getAttribute('data-in-id')),
        name: t.getAttribute('data-name'),
        value: t.getAttribute('data-value'),
        content: t.getAttribute('data-content')
      };
      document.getElementById('learnSkillName').textContent = selectedSkill.name;
      document.getElementById('learnSkillValue').innerHTML = selectedSkill.value;
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
    }

    // 습득 버튼
    document.getElementById('skillConfirmBtn').onclick = function(){
      if(selectedSlotIdx === null || !selectedSkill) return;
      if(acquired.includes(selectedSkill.wa_id)) {
        alert("이미 이 기술을 습득하고 있습니다.");
        return;
      }
      let prevName = acquiredName[selectedSlotIdx], prevContent = acquiredContent[selectedSlotIdx];
      let msg = '';
      if(prevName) {
        msg = `"${prevName}"을(를) 잊고 "${selectedSkill.name}"을(를) 배울까? ※ 교체된 기존 기술은 복구할 수 없습니다.`;
      } else {
        msg = `"${selectedSkill.name}"을(를) 이 슬롯에 배울까? ※ 습득한 기술은 해제할 수 없으며, 새로운 기술로만 교체할 수 있습니다.`;
      }
      if(!confirm(msg.replace(/\\\\n/g,"\\n"))) return;
      // AJAX로 서버에 저장
      var xhr = new XMLHttpRequest();
      xhr.open("POST", "/au/pokemon/skill_update.php");
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.onload = function(){
        try {
          var res = JSON.parse(xhr.responseText);
          if(res && res.result == "ok") {
            acquired[selectedSlotIdx] = selectedSkill.wa_id;
            acquiredName[selectedSlotIdx] = selectedSkill.name;
            acquiredValue[selectedSlotIdx] = selectedSkill.value;
            acquiredContent[selectedSlotIdx] = selectedSkill.content;
            let slotDiv = document.querySelector('.slot[data-index="'+selectedSlotIdx+'"]');
            slotDiv.querySelector('.acquired-name').textContent = selectedSkill.name;
            slotDiv.classList.remove('skill-color-1','skill-color-2','skill-color-3','skill-color-4');
            var _baseType = selectedSkill.value ? selectedSkill.value.split('/')[0] : '';
            if(_baseType && waColorMap[_baseType]) {
                slotDiv.classList.add('skill-color-' + waColorMap[_baseType]);
            }
            document.querySelectorAll('#skillList .skill-item').forEach(function(li){
              if(parseInt(li.getAttribute('data-in-id')) === selectedSkill.in_id) li.remove();
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
        } catch(e) { alert("서버 응답 오류 또는 실패."); }
      };
      xhr.send("slot="+selectedSlotIdx+"&in_id="+selectedSkill.in_id+"&wa_id="+selectedSkill.wa_id);
    };

    function updateSkillListUI() {
      document.querySelectorAll('#skillList .skill-item').forEach(function(el){
        let waId = parseInt(el.getAttribute('data-wa-id'));
        if(acquired && acquired.includes(waId)) el.classList.add('disabled');
        else el.classList.remove('disabled');
      });
    }
  </script>
<?}else{
  echo "<p class=\"warning\">이 포켓몬은 아직 배틀 등록을 하지 않았습니다.<br>관리 버튼을 눌러 배틀 등록을 해 주세요.</p>";
}
require_once(G5_PATH.'/tail.sub.php');?>