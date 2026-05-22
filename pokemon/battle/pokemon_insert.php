<?php
@include_once('../../common.php');
require_once(G5_PATH.'/head.sub.php');

@include("./menu.php");
@include("./pokemon.inc.php");

// 각 스탯 기본값 (HP도 다른 스탯과 동일: min=30, 초기값=30)
$h = isset($ph['h']) ? (int)$ph['h'] : 30;
$a = isset($ph['a']) ? (int)$ph['a'] : 30;
$b = isset($ph['b']) ? (int)$ph['b'] : 30;
$c = isset($ph['c']) ? (int)$ph['c'] : 30;
$d = isset($ph['d']) ? (int)$ph['d'] : 30;
$s = isset($ph['s']) ? (int)$ph['s'] : 0;

// 최대 배분 포인트
$totalPoint = 600;
?>

<style>@import url(./stat.distribution.css);</style>

<form id="stat-form" method="post" action="./submit.stat.php" enctype="multipart/form-data" autocomplete="off" style="max-width:1000px;width:98%;margin:20px auto;">
  <input type="hidden" name="ch_id" value="<?=$character['ch_id']?>">
  <table class="theme-form">
    <colgroup><col style="width:110px;"><col></colgroup>
    <tbody>
      <tr>
        <th>포켓몬</th>
        <td>
          <select name="ph_id" onchange="location.href='?ph_id=' + this.value;">
            <?php for($i=0;$i<count($po_list);$i++){ ?>
              <option value="<?=$po_list[$i]['ph_id']?>"<?php if($po_list[$i]['ph_id']==$ph_id) echo ' selected';?>><?=$po_list[$i]['po_name']?></option>
            <?php } ?>
          </select>
        </td>
      </tr>

      <tr>
        <th>스테이터스</th>
        <td>
          <div id="stat-distribution">
            <div id="remaining">남은 포인트: <span id="remain-val"></span></div>

            <div class="stat-row" data-stat="hp" data-min="30" data-max="225">
              <div class="stat-name">HP</div>
              <button type="button" class="minus5">-5</button>
              <button type="button" class="minus">-</button>
              <div class="stat-bar-container" data-tooltip=""><div class="stat-fill"></div></div>
              <button type="button" class="plus">+</button>
              <button type="button" class="plus5">+5</button>
              <span class="stat-value"><?=($h*2)+110?></span>
              <input type="hidden" name="h" value="<?=$h?>">
            </div>

            <div class="stat-row" data-stat="atk" data-min="30" data-max="150">
              <div class="stat-name">공격</div>
              <button type="button" class="minus5">-5</button>
              <button type="button" class="minus">-</button>
              <div class="stat-bar-container" data-tooltip=""><div class="stat-fill"></div></div>
              <button type="button" class="plus">+</button>
              <button type="button" class="plus5">+5</button>
              <span class="stat-value"><?=$a?></span>
              <input type="hidden" name="a" value="<?=$a?>">
            </div>

            <div class="stat-row" data-stat="def" data-min="30" data-max="200">
              <div class="stat-name">방어</div>
              <button type="button" class="minus5">-5</button>
              <button type="button" class="minus">-</button>
              <div class="stat-bar-container" data-tooltip=""><div class="stat-fill"></div></div>
              <button type="button" class="plus">+</button>
              <button type="button" class="plus5">+5</button>
              <span class="stat-value"><?=$b?></span>
              <input type="hidden" name="b" value="<?=$b?>">
            </div>

            <div class="stat-row" data-stat="spa" data-min="30" data-max="150">
              <div class="stat-name">특공</div>
              <button type="button" class="minus5">-5</button>
              <button type="button" class="minus">-</button>
              <div class="stat-bar-container" data-tooltip=""><div class="stat-fill"></div></div>
              <button type="button" class="plus">+</button>
              <button type="button" class="plus5">+5</button>
              <span class="stat-value"><?=$c?></span>
              <input type="hidden" name="c" value="<?=$c?>">
            </div>

            <div class="stat-row" data-stat="spd" data-min="30" data-max="200">
              <div class="stat-name">특방</div>
              <button type="button" class="minus5">-5</button>
              <button type="button" class="minus">-</button>
              <div class="stat-bar-container" data-tooltip=""><div class="stat-fill"></div></div>
              <button type="button" class="plus">+</button>
              <button type="button" class="plus5">+5</button>
              <span class="stat-value"><?=$d?></span>
              <input type="hidden" name="d" value="<?=$d?>">
            </div>

            <div class="stat-row" data-stat="spe" data-min="0" data-max="150">
              <div class="stat-name">속도</div>
              <button type="button" class="minus5">-5</button>
              <button type="button" class="minus">-</button>
              <div class="stat-bar-container" data-tooltip=""><div class="stat-fill"></div></div>
              <button type="button" class="plus">+</button>
              <button type="button" class="plus5">+5</button>
              <span class="stat-value"><?=$s?></span>
              <input type="hidden" name="s" value="<?=$s?>">
            </div>

            <div style="margin-top:16px;">
              <button type="button" id="auto-distribute" class="ui-btn">랜덤 배분</button>
              <button type="button" id="reset-stats" class="ui-btn">초기화</button>
            </div>
          </div>
        </td>
      </tr>

      <tr>
        <th>제출</th>
        <td><button type="submit" id="submit-btn">제출</button></td>
      </tr>
    </tbody>
  </table>
</form>

<script>
// 총 포인트(서버 값)
const totalPoint = <?=$totalPoint?>;

// raw 값 get/set
function getRaw($row){ return parseInt($row.find('input[type="hidden"]').val(),10)||0; }
function setRaw($row, v){ $row.find('input[type="hidden"]').val(parseInt(v,10)); }

// 표시값 변환(HP만 특수)
function toDisplay($row, raw){
  return ($row.data('stat')==='hp') ? (raw*2+110) : raw;
}

// 사용 포인트 합산(raw 기준)
function getUsedPoints(){
  let sum=0;
  $('.stat-row').each(function(){ sum+=getRaw($(this)); });
  return sum;
}

function updateDisplay(){
  const usedPoints=getUsedPoints();
  $('.stat-row').each(function(){
    const $row=$(this);
    const raw=getRaw($row);
    const min=parseInt($row.data('min'),10);
    const max=parseInt($row.data('max'),10);
    const statName=$row.find('.stat-name').text().trim();

    // 바/툴팁/버튼 상태
    $row.find('.stat-fill').css('width', (raw/max*100)+'%');
    $row.find('.stat-bar-container').attr('data-tooltip', statName+': '+toDisplay($row,raw)+' / '+toDisplay($row,max));
    $row.find('.minus').prop('disabled', raw<=min);
    $row.find('.plus').prop('disabled', usedPoints>=totalPoint || raw>=max);
    $row.find('.minus5').prop('disabled', raw-min<1);
    $row.find('.plus5').prop('disabled', usedPoints>=totalPoint || raw>=max);

    // 표시 숫자는 변환값
    $row.find('.stat-value').text(toDisplay($row, raw));
  });

  const remaining=totalPoint-usedPoints;
  $('#remain-val').text(remaining);
  $('#submit-btn').prop('disabled', remaining!==0)
                  .text(remaining===0?'제출':'포인트를 모두 배분해 주세요');
}

// 증감(raw 기준)
function statAdjust($row, delta){
  const min=parseInt($row.data('min'),10);
  const max=parseInt($row.data('max'),10);
  let raw=getRaw($row);
  let usedPoints=getUsedPoints();

  if(delta>0){
    const remainStat=max-raw;
    const remainTotal=totalPoint-usedPoints;
    const inc=Math.min(Math.abs(delta), remainStat, remainTotal);
    if(inc>0){ setRaw($row, raw+inc); updateDisplay(); }
  }else if(delta<0){
    const dec=Math.min(Math.abs(delta), raw-min);
    if(dec>0){ setRaw($row, raw-dec); updateDisplay(); }
  }
}

// 길게 누름 반복
function attachRepeatBtn(selector, delta){
  let timer, repeatDelay=400, repeatSpeed=80;
  $(document).on('mousedown touchstart', selector, function(e){
    e.preventDefault();
    const $row=$(this).closest('.stat-row');
    statAdjust($row, delta);
    timer=setTimeout(function loop(){
      statAdjust($row, delta);
      timer=setTimeout(loop, repeatSpeed);
    }, repeatDelay);
  });
  $(document).on('mouseup mouseleave touchend touchcancel', selector, function(){ clearTimeout(timer); });
}
attachRepeatBtn('.plus', 1);
attachRepeatBtn('.minus', -1);
attachRepeatBtn('.plus5', 5);
attachRepeatBtn('.minus5', -5);

// 랜덤 배분: 모두 min(raw)으로 리셋 후 남은 포인트를 랜덤 분배
$('#auto-distribute').on('click', function(){
  const $btn=$(this).prop('disabled', true);

  $('.stat-row').each(function(){
    const min=parseInt($(this).data('min'),10);
    setRaw($(this), min);
  });

  let remaining=totalPoint-getUsedPoints();
  const $rows=$('.stat-row');
  const pool=[];
  $rows.each(function(i){
    const max=parseInt($(this).data('max'),10);
    const min=parseInt($(this).data('min'),10);
    pool.push({i, available:max-min});
  });

  while(remaining>0){
    const valid=pool.filter(p=>p.available>0);
    if(!valid.length) break;
    const sel=valid[Math.floor(Math.random()*valid.length)];
    const $row=$($rows[sel.i]);
    setRaw($row, getRaw($row)+1);
    sel.available--; remaining--;
  }

  updateDisplay();
  $btn.prop('disabled', false);
});

// 초기화: 전부 min(raw)
$('#reset-stats').on('click', function(){
  $('.stat-row').each(function(){
    const min=parseInt($(this).data('min'),10);
    setRaw($(this), min);
  });
  updateDisplay();
});

// 초기 렌더
$(function(){ updateDisplay(); });

// 제출 중 중복 방지
$('#stat-form').on('submit', function(){
  $('#submit-btn').prop('disabled', true).text('제출 중...');
});

// 기존 함수
function get_ajax_po(obj, list_id, rel_id, etc_value){
  var url=g5_url+"/au/_pokemon_search.php";
  ajax_load(url, obj, list_id, rel_id);
}
</script>

<?php require_once(G5_PATH.'/tail.sub.php'); ?>
