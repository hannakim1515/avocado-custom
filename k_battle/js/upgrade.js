/* global g5_url, $, act */
(() => {
 
  // 캐시
  const $itemsUl   = $("#items ul");
  const $textArea  = $("#text-area");
  const $slot1Val  = $("#slot_1_value");
  const $slot2Val  = $("#slot_2_value");
  const $slot1     = $("#slot_1");
  const $slot2     = $("#slot_2");
  const $slot3     = $("#slot_3");
  const $white     = $(".white");

  let option = '';

  // 유틸
  const clamp = (n, min, max) => Math.min(Math.max(n, min), max);
  const setBlink = (onEl, offSel) => {
    $(onEl).addClass("blink");
    $(offSel).not(onEl).removeClass("blink");
  };
  const clearSlotsExcept = (keepSel) => {
    $(".slot .slot-img").not(`${keepSel} .slot-img`).empty();
    $(".slot .name").not(`${keepSel} .name`).empty();
  };
  const safeAppendText = ($el, text) => { $el.empty().text(text); };
  const ensureUrl = () => {
    if (typeof g5_url !== 'string' || !g5_url) {
      alert('요청 URL이 올바르지 않습니다.');
      return false;
    }
    return true;
  };

  // 공용: 아이템 클릭
  window.itemClick = function itemClick(e, slot, id, name) {
    const $li = $(e);
    $li.addClass('selected').siblings('li').removeClass('selected');
    $('#' + slot + ' .slot-img').empty().html($li.html());
    $('#' + slot + ' .name').empty().text(String(name ?? ''));
    $('#' + slot + '_value').val(String(id ?? ''));

    if (slot == 'slot_1') {
      equipReselect();
    } else {
      $('.slot').removeClass('blink');
      if (act == 'upgrade') {
        $textArea.empty().append('<span class="ui-btn" id="btn_equipAction">강화하기' + (option || '') + '</span>');
      } else if (act == 'custom') {
        $textArea.empty().append('<span class="ui-btn" id="btn_equipAction">커스텀하기</span>');
      }
      // 버튼 핸들러 바인딩
      $("#btn_equipAction").off('click').on('click', equipAction);
    }
  };

  // 1번 슬롯 재선택 단계로 전환
  window.equipReselect = function equipReselect() {
    setBlink('#slot_2', '.slot');
    clearSlotsExcept('#slot_1');

    $slot2.off('click').on('click', () => loadItemList(act));
    $slot3.off('click');

    if (act == 'upgrade') {
      safeAppendText($textArea, '강화 재료를 확인해 주세요.');
    } else if (act == 'custom') {
      safeAppendText($textArea, '커스텀 재료를 확인해 주세요.');
    }
  };

  // 목록 로드
  window.loadItemList = function loadItemList(type) {
    if (!ensureUrl()) return;

    const formData = new FormData();
    formData.append('type', type);
    formData.append('act', act);

    if (type !== 'equip') {
      const eq_id = $slot1Val.val();
      if (!eq_id) {
        alert('장비 아이템을 먼저 선택해 주세요.');
        return;
      }
      formData.append('eq_id', eq_id);
    } else {
      clearSlotsExcept('#slot_1');
      if ($slot3.hasClass('blink')) {
        $slot3.removeClass('blink');
        $slot1.addClass('blink');
        $slot3.off('click');
      }
    }

    $.ajax({
      url: g5_url + '/k_battle/ajax/equip.php?load=list',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      type: 'POST'
    })
    .done((data) => {
      $itemsUl.empty();
      if (data && data.msg) {
        $itemsUl.append('<p>' + String(data.msg) + '</p>');
      } else if (data && data.list) {
        $itemsUl.append(data.list); // 서버 생성 HTML 신뢰 전제
      }

      if (data && typeof data.text == 'string') {
        $textArea.empty().append(data.text);
      }

      if (data && data.option) option = data.option;
      if (data && data.target) $slot2Val.val('no');
    })
    .fail(() => {
      alert('목록을 불러오지 못했습니다.');
    });
  };

  // 강화/커스텀 액션
  window.equipAction = function equipAction() {
    if (!ensureUrl()) return;

    const eq_id = $slot1Val.val();
    const tg_id = $slot2Val.val();

    $('#text-area .ui-btn').off('click');
    $slot2.off('click');
    $('.slot').removeClass('blink');

    if (!eq_id || !tg_id) {
      alert('장비와 아이템을 선택해 주세요.');
      return;
    }

    const formData = new FormData();
    formData.append('eq_id', eq_id);
    formData.append('tg_id', tg_id);
    formData.append('act', act);

    // 이펙트 시작
    $white.stop(true).animate({ opacity: 1 }, 900);

    $.ajax({
      url: g5_url + '/k_battle/ajax/equip.php?load=action',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      type: 'POST'
    })
    .done((data) => {
      if (!data) return;

      if (data.result == 'alert') {
        alert(String(data.msg || '처리할 수 없습니다.'));
        return;
      }

      // 결과 아이콘 표시 약간 지연
      setTimeout(() => {
        if (data.html) $('#slot_3 .slot-img').html(data.html);
      }, 1000);

      const isSuccess = data.result == '성공';
      const resultClass = isSuccess ? 'S' : 'F';
      const plus = data.plus ? "<p class='plus'>" + String(data.plus) + "</p>" : '';
      const text = "<p class='" + resultClass + "'>" + String(data.msg || '') + "</p>" + plus;

      // 이펙트 종료 및 UI 반영
      setTimeout(() => {
        $white.stop(true).animate({ opacity: 0 }, 500);
        $textArea.html(text);
        $slot3.off('click').on('click', take_result).addClass('blink');
      }, 1200);

      // 성공/실패와 무관하게 좌측 슬롯 초기화
      clearSlotsExcept('#slot_3');
      $itemsUl.empty();
      $slot1Val.val('');
      $slot2Val.val('');
    })
    .fail(() => {
      $white.stop(true).animate({ opacity: 0 }, 300);
      alert('처리에 실패했습니다.');
    });
  };

  // 결과 수령
  window.take_result = function take_result() {
    $slot3.off('click');
    $('#slot_3 .slot-img').empty();
    $('#slot_3 .name').empty();

    alert('아이템을 수령했습니다.');

    if (act == 'upgrade') {
      $textArea.html('강화 대상 장비를 선택해 주세요.');
    } else if (act == 'custom') {
      $textArea.html('모습을 바꿀 장비를 선택해 주세요.');
    }

    $slot3.removeClass('blink');
    $slot1.addClass('blink');
  };
})();
