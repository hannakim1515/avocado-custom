function changemsg(id,msg='') {
	if(!msg) msg = "설명 없음";
	$("#s_msg_"+id).html(msg);
}

(function($){
  if(!window.initTurnPager){
    window.initTurnPager = function(target){
      var $wrap  = (target instanceof jQuery) ? target : $(target);
      if(!$wrap.length) return;

      var $pager = $wrap.find('.turn-pager');
      var $pages = $pager.find('.turn-page');
      var total  = $pages.length;
      var idx    = 0;

      if(!total){ $wrap.find('.turn-nav').hide(); return; }
      $pager.addClass('js-ready');

      function updateUI(){
        $pages.removeClass('is-active').eq(idx).addClass('is-active');
        $wrap.find('.page-indicator .curr').text(idx+1);
        $wrap.find('.page-indicator .total').text(total);
        $wrap.find('.btn-prev, .btn-first').prop('disabled', idx===0);
        $wrap.find('.btn-next, .btn-last').prop('disabled', idx===total-1);
      }

      function show(i){
        idx = Math.max(0, Math.min(i, total-1));
        updateUI();
      }

      // 내비게이션
      $wrap.on('click', '.btn-prev',  function(){ show(idx-1); });
      $wrap.on('click', '.btn-next',  function(){ show(idx+1); });
      $wrap.on('click', '.btn-first', function(){ show(0); });
      $wrap.on('click', '.btn-last',  function(){ show(total-1); });

      // 이 래퍼 활성화 시에만 키보드 ←/→/Home/End 동작
      $wrap.on('click', function(){
        $('.turn-pager-wrap').removeClass('is-active');
        $wrap.addClass('is-active');
      });
      $(document).on('keydown', function(e){
        if($('.turn-pager-wrap.is-active')[0] !== $wrap[0]) return;
        if($(e.target).is('input,textarea,select,[contenteditable]')) return;
        if(e.key === 'ArrowLeft')  show(idx-1);
        if(e.key === 'ArrowRight') show(idx+1);
        if(e.key === 'Home')       show(0);
        if(e.key === 'End')        show(total-1);
      });

      // ✅ 기본: 최신(마지막) 턴 표시
      show(total-1);

      // 퍼블릭 API
      $wrap.data('turnPager', {
        show: show,
        getIndex: function(){ return idx; },
        getTotal: function(){ return total; }
      });
    };
  }

  // 페이지 내 모든 래퍼 초기화
  $(function(){ $('.turn-pager-wrap').each(function(){ window.initTurnPager($(this)); }); });

  // ----- 전역 키보드 네비게이션: 한 번만 바인딩 -----
  if(!window.__turnPagerKeyHandlerBound){
    $(document).on('keydown.turnpager', function(e){
      var $active = $('.turn-pager-wrap.is-active');
      if(!$active.length) return;
      if($(e.target).is('input,textarea,select,[contenteditable]')) return;

      var api = $active.data('turnPager');
      if(!api) return;

      if(e.key === 'ArrowLeft')  api.show(api.getIndex()-1);
      else if(e.key === 'ArrowRight') api.show(api.getIndex()+1);
      else if(e.key === 'Home')  api.show(0);
      else if(e.key === 'End')   api.show(api.getTotal()-1);
    });
    window.__turnPagerKeyHandlerBound = true;
  }

  // ----- 귀하가 제공한 initTurnPager를 그대로 사용 (없으면 무시) -----
  $(function(){
    $('.turn-pager-wrap').each(function(){ if(window.initTurnPager){ window.initTurnPager($(this)); } });
  });

  // ----- 부분 새로고침: 버튼 기준으로 "바로 위" .battle-wrap만 교체 -----
  $(document).on('click', '.js-battle-reload', function(){
    var $btn  = $(this);
    var bid   = $btn.data('battleId'); // 숫자 또는 문자열
    var $wrap = $btn.closest('.battle_refresh')
                    .nextAll('.battle-wrap')
                    .filter(function(){ return $(this).data('battleId')==bid; })
                    .first();

    if(!$wrap.length){ $wrap = $btn.closest('.battle_refresh').prev('.battle-wrap'); }
    if(!$wrap.length){ alert('전투 영역을 찾지 못했습니다.'); return; }

    var base = location.href.replace(/#.*$/,'');
    var sep  = base.indexOf('?')>-1 ? '&' : '?';
    var url  = base + sep + 'partial_reload=' + Date.now(); // 캐시 우회

    $wrap.addClass('is-loading');

    // 동일 페이지를 다시 GET하여, 해당 battle-wrap만 자식요소로 교체
    var selector = '.battle-wrap[data-battle-id="'+ bid +'"] > *';
    $wrap.load(url + ' ' + selector, function(_, status){
      $wrap.removeClass('is-loading');
      if(status!=='success'){ alert('새로고침에 실패했습니다.'); return; }
      // 교체된 DOM 내 페이저 재초기화
      $wrap.find('.turn-pager-wrap').each(function(){
        if(window.initTurnPager){ window.initTurnPager($(this)); }
      });
    });
  });
})(jQuery);