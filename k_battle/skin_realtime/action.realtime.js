// iframe 내부에서 새로고침 (부모 프레임 유지)
function reloadRaid() {
    if (window.parent !== window) {
        window.parent.postMessage('reload-raid', '*');
    } else {
        location.reload();
    }
}

// F5 키 누르면 부모 프레임에 새로고침 요청 (BGM 유지)
window.addEventListener('keydown', function(e) {
    if (e.key === 'F5' || e.keyCode === 116) {
        e.preventDefault();
        reloadRaid();
    }
});

function action(type,unitType){

    $("#raid_act_btn").attr("onclick","");

    var targetID=$("#action_target").val();
    var targetType=$("#target_type").val();
    var bsID=$("#bs_id").val();

    if(targetID&&targetType){
        var formData = new FormData();
        formData.append("type", type);
        formData.append("unit_type", unitType);
        formData.append("target_id", targetID);
        formData.append("target_type", targetType);
        if(bsID&&bsID>0){formData.append("bs_id", bsID);}
        
        $.ajax({
            url: g5_url+"/k_battle/skin_realtime/_action_realtime.php"+$get+"&rm_id="+$rm_id
            , data: formData
            , processData: false
            , contentType: false
            , dataType: "json"
            , type: 'POST'
            , success: function(){
                reloadRaid();
            }, error: function(xhr, status, error){
                console.log('AJAX ERROR:', status, error);
                console.log('RESPONSE:', xhr.responseText);
            }
        });
    }

}

function page_reload(data){
    $.each(data.unit, function(index, item){
        var info_selector='.unit-list li[data-id="'+item.rm_id+'"]';
        $(info_selector+' .unit-hp i').text(item.hp_now+"/"+item.hp_max);
        $(info_selector+' .unit-hp span').css("width",(Number(item.hp_now)/Number(item.hp_max)*100)+"%");
        $(info_selector+' .unit-mp i').text(item.mp_now+"/"+item.mp_max);
        $(info_selector+' .unit-mp span').css("width",(Number(item.mp_now)/Number(item.mp_max)*100)+"%");
        
        // 도발/기절 갱신
        var is_aggr = Number(item.is_aggr) || 0;
        var is_stun = Number(item.is_stun) || 0;
        $(info_selector+' .unit-status .aggr').attr('data-cnt', is_aggr);
        $(info_selector+' .unit-status .aggr').contents().filter(function() { return this.nodeType === 3; }).first().replaceWith(is_aggr);
        $(info_selector+' .unit-status .aggr .bf-inner span').text('도발 | 남은 턴 ' + is_aggr);
        $(info_selector+' .unit-status .stun').attr('data-cnt', is_stun);
        $(info_selector+' .unit-status .stun').contents().filter(function() { return this.nodeType === 3; }).first().replaceWith(is_stun);
        $(info_selector+' .unit-status .stun .bf-inner span').text('기절 | 남은 턴 ' + is_stun);
        
        // 버프/디버프 갱신
        var buff_list = item.buff_list || {buff: [], debuff: []};
        var buffs = buff_list.buff || [];
        var debuffs = buff_list.debuff || [];
        
        var buff_html = '';
        $.each(buffs, function(i, bf) {
            buff_html += '<span>' + bf.name + ' +' + bf.value + ' | 남은 턴 ' + bf.turn + '</span>';
        });
        var debuff_html = '';
        $.each(debuffs, function(i, df) {
            debuff_html += '<span>' + df.name + ' ' + df.value + ' | 남은 턴 ' + df.turn + '</span>';
        });
        
        $(info_selector+' .unit-status .buff').attr('data-cnt', buffs.length);
        $(info_selector+' .unit-status .buff').contents().filter(function() { return this.nodeType === 3; }).first().replaceWith(buffs.length);
        $(info_selector+' .unit-status .buff .bf-inner').html(buff_html);
        $(info_selector+' .unit-status .debuff').attr('data-cnt', debuffs.length);
        $(info_selector+' .unit-status .debuff').contents().filter(function() { return this.nodeType === 3; }).first().replaceWith(debuffs.length);
        $(info_selector+' .unit-status .debuff .bf-inner').html(debuff_html);
        
        // 상태 클래스 갱신
        if(Number(item.hp_now)<=0){$(info_selector).addClass('retire');}else{$(info_selector).removeClass('retire');}
        if(item.tt_done>0){$(info_selector+' .unit-status .turn').addClass('done');}else{$(info_selector+' .unit-status .turn').removeClass('done');}
        if(is_stun>0){$(info_selector+' .unit-status .stun').addClass('done');}else{$(info_selector+' .unit-status .stun').removeClass('done');}
        if(is_aggr>0){$(info_selector+' .unit-status .aggr').addClass('done');}else{$(info_selector+' .unit-status .aggr').removeClass('done');}
        if(buffs.length>0){$(info_selector+' .unit-status .buff').addClass('done');}else{$(info_selector+' .unit-status .buff').removeClass('done');}
        if(debuffs.length>0){$(info_selector+' .unit-status .debuff').addClass('done');}else{$(info_selector+' .unit-status .debuff').removeClass('done');}
    });

    if(data.ra.ra_turn){
        $('#turn-area #turn-inner').text(data.ra.ra_turn);
    }

    if(typeof data.ra.ra_system_msg !== 'undefined'){
        var $msg = $('#system-msg');
        var newMsg = data.ra.ra_system_msg;
        if ($msg.text() !== newMsg) {
            $msg.addClass('msg-out');
            setTimeout(function() {
                $msg.text(newMsg);
                $msg.removeClass('msg-out').addClass('msg-in');
                setTimeout(function() {
                    $msg.removeClass('msg-in');
                }, 400);
            }, 200);
        }
    }

    if(data.ra.now_turn){
        $('.unit-list li[data-id="'+data.ra.now_turn+'"]').addClass('nowturn');
        $('.unit-list li').not('li[data-id="'+data.ra.now_turn+'"]').removeClass('nowturn');
    }

    if(data.warning){
        $("#action-info .warning").text(data.warning);
    }else{
        // 내 턴 아니거나, 내 턴이었는데 now_turn이 바뀌었으면 내 정보 갱신
        if($my_reload==1 || (data.ra && data.ra.now_turn != $rm_id)){
            updateMyArea(data);
        }
    }

    // 로그 영역 갱신 (로그 전용 엔드포인트 사용)
    $.get(g5_url + '/k_battle/skin_realtime/_ajax_log.php' + $get, function(html) {
        $('#log-inner').html(html);
    });
}

// 내 영역 정보 갱신 (load 대신 직접 렌더링)
function updateMyArea(data) {
    // 내 유닛 정보 찾기
    var myUnit = null;
    if (data.unit && $rm_id) {
        $.each(data.unit, function(index, item) {
            if (item.rm_id == $rm_id) {
                myUnit = item;
                return false; // break
            }
        });
    }
    
    if (!myUnit) return;
    
    var hp_now = Number(myUnit.hp_now) || 0;
    var hp_max = Number(myUnit.hp_max) || 1;
    var mp_now = Number(myUnit.mp_now) || 0;
    var mp_max = Number(myUnit.mp_max) || 1;
    var is_aggr = Number(myUnit.is_aggr) || 0;
    var is_stun = Number(myUnit.is_stun) || 0;
    var tt_done = Number(myUnit.tt_done) || 0;
    
    var hp_pct = Math.min(100, Math.max(0, (hp_now / hp_max) * 100));
    var mp_pct = Math.min(100, Math.max(0, (mp_now / mp_max) * 100));
    
    // HP/MP 바 갱신
    $('#my-info .unit-hp span').css('width', hp_pct + '%');
    $('#my-info .unit-hp i').text(hp_now + '/' + hp_max);
    $('#my-info .unit-mp span').css('width', mp_pct + '%');
    $('#my-info .unit-mp i').text(mp_now + '/' + mp_max);
    
    // 도발/기절 상태 갱신
    $('#my-info .aggr').attr('data-cnt', is_aggr);
    $('#my-info .aggr .bf-inner span').text('도발 | 남은 턴 ' + is_aggr);
    $('#my-info .stun').attr('data-cnt', is_stun);
    $('#my-info .stun .bf-inner span').text('기절 | 남은 턴 ' + is_stun);
    
    // 버프/디버프 갱신
    var buff_list = myUnit.buff_list || {buff: [], debuff: []};
    var buffs = buff_list.buff || [];
    var debuffs = buff_list.debuff || [];
    
    var buff_html = '';
    $.each(buffs, function(i, bf) {
        buff_html += '<span>' + bf.name + ' +' + bf.value + ' | 남은 턴 ' + bf.turn + '</span>';
    });
    var debuff_html = '';
    $.each(debuffs, function(i, df) {
        debuff_html += '<span>' + df.name + ' ' + df.value + ' | 남은 턴 ' + df.turn + '</span>';
    });
    
    $('#my-info .buff').attr('data-cnt', buffs.length);
    $('#my-info .buff').contents().filter(function() { return this.nodeType === 3; }).first().replaceWith(buffs.length);
    $('#my-info .buff .bf-inner').html(buff_html);
    $('#my-info .debuff').attr('data-cnt', debuffs.length);
    $('#my-info .debuff').contents().filter(function() { return this.nodeType === 3; }).first().replaceWith(debuffs.length);
    $('#my-info .debuff .bf-inner').html(debuff_html);
    
    // 행동완료 상태 갱신
    $('#my-info .done').attr('data-cnt', tt_done);
    
    // 내 턴 여부에 따른 활성화 상태
    var isMyTurn = (data.ra && data.ra.now_turn == $rm_id);
    var canAct = (hp_now > 0 && !tt_done && !is_stun && isMyTurn);
    
    if (canAct) {
        $('#my-area').addClass('active');
        $('#action-select .raid-action').removeClass('false');
        $('#action-info .warning').text('행동을 선택해 주세요.');
    } else {
        $('#my-area').removeClass('active');
        $('#action-select .raid-action').addClass('false');
        if (hp_now <= 0) {
            $('#action-info .warning').text('전투 불능 상태입니다.');
        } else if (tt_done) {
            $('#action-info .warning').text('이미 행동을 완료했습니다.');
        } else if (is_stun) {
            $('#action-info .warning').text('기절 상태입니다.');
        } else if (!isMyTurn) {
            $('#action-info .warning').text('내 턴이 아닙니다.');
        }
    }
}

function messageInsert(){
    var msg=$("#raid-msg").val();
    if(msg){
        $("#raid-msg").val('');
        var formData = new FormData();
        formData.append("type", 'ch-msg');
        formData.append("msg", msg);
        $.ajax({
            url: g5_url+"/k_battle/skin_realtime/_action_message.php"+$get
            , data: formData
            , processData: false
            , contentType: false
            , dataType: "json"
            , type: 'POST'
            , success: function(data){
                if(data){
                    reloadRaid();
                }
            }
        });
    }
   
}

function auto_reload() {
    // 전투중이 아니거나 새고 미사용시 호출x
    if (($ra_state !== 0 && $ra_state !== 1)||$turn_type=='none') {
        return;
    }

    var formData = new FormData();

    if ($ra_state == 1) {// 전투 진행 중
        formData.append("type", "default");
        formData.append("ra_turn", $ra_turn);
        formData.append("ra_count", $ra_count);
        formData.append("my_reload", $my_reload);
        formData.append("rm_id", $rm_id);
    } else {// 전투 대기/준비 상태
        formData.append("type", "pre");
    }

    $.ajax({
        url: g5_url + "/k_battle/skin_realtime/_action_reload.php" + $get,
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        type: "POST",
        success: function (data) {
            if (!data || !data.type) {
                return;
            }

            if (data.type == "all") {
                reloadRaid();
                return;
            }

            if ($ra_state == 1) {// 전투 중: 부분 갱신 + 턴/카운트 갱신
                if (data.type == "page") {
                    page_reload(data);
                }
                if (data.ra) {
                    if (typeof data.ra.ra_turn !== "undefined") {
                        $ra_turn = data.ra.ra_turn;
                    }
                    if (typeof data.ra.ra_count !== "undefined") {
                        $ra_count = data.ra.ra_count;
                    }
                    // 제한시간 갱신 (now_turn 변경 시)
                    if (typeof data.ra.now_turn !== "undefined" && data.ra.now_turn !== $now_turn) {
                        $now_turn = data.ra.now_turn;
                        // 서버에서 제한시간 정보가 있으면 갱신
                        if (typeof data.ra.time_remaining !== "undefined") {
                            $time_remaining = parseInt(data.ra.time_remaining);
                            timeLimitProcessing = false;
                            updateTimerDisplay();
                        }
                    }
                }
            }
        }
    });
}

// 폴링 인터벌 변수 (전역)
var battleReload = null;

if ($reload_type != 'none') {
    // Pusher 모드가 아닐 때만 폴링 사용
    if (!$use_pusher) {
        battleReload = setInterval(auto_reload, $reload_time);
    }
}

// ============================================
// Pusher 실시간 시스템 (Pusher 사용 시에만 초기화)
// ============================================
if ($use_pusher && typeof Pusher !== 'undefined') {
    (function() {
        var pusher = null;
        var pusherChannel = null;
        
        function initPusher() {
            if (!$pusher_key) {
                console.warn('Pusher: Key not configured');
                startPollingFallback();
                return;
            }
            
            try {
                // Pusher 초기화
                pusher = new Pusher($pusher_key, {
                    cluster: $pusher_cluster,
                    forceTLS: true
                });
                
                // 채널 구독
                pusherChannel = pusher.subscribe($pusher_channel);
                
                // 이벤트 바인딩
                pusherChannel.bind('raid-update', function(data) {
                    console.log('Pusher: raid-update received', data);
                    handlePusherUpdate(data);
                });
                
                pusherChannel.bind('raid-state', function(data) {
                    console.log('Pusher: raid-state received', data);
                    // 상태 변경 시 전체 새로고침
                    reloadRaid();
                });
                
                pusherChannel.bind('raid-message', function(data) {
                    console.log('Pusher: raid-message received', data);
                    // 메시지만 갱신
                    $.get(g5_url + '/k_battle/skin_realtime/_ajax_log.php' + $get, function(html) {
                        $('#log-inner').html(html);
                    });
                });
                
                // 연결 상태 로깅
                pusher.connection.bind('connected', function() {
                    console.log('Pusher: Connected');
                });
                
                pusher.connection.bind('error', function(err) {
                    console.error('Pusher: Error', err);
                    // 연결 실패 시 폴링 폴백
                    startPollingFallback();
                });
                
            } catch (e) {
                console.error('Pusher: Init failed', e);
                startPollingFallback();
            }
        }
        
        function startPollingFallback() {
            if (!battleReload && $reload_type != 'none') {
                console.log('Pusher: Falling back to polling');
                battleReload = setInterval(auto_reload, $reload_time);
            }
        }
        
        function handlePusherUpdate(data) {
            // 내 액션인 경우 스킵 (이미 처리됨)
            if (data.actor_rm_id && data.actor_rm_id == $rm_id) {
                return;
            }
            
            if (data.type === 'all') {
                reloadRaid();
                return;
            }
            
            if (data.type === 'page' && data.unit && data.ra) {
                // 페이지 부분 갱신
                page_reload(data);
                
                // 턴/카운트 동기화
                if (typeof data.ra.ra_turn !== 'undefined') {
                    $ra_turn = data.ra.ra_turn;
                }
                if (typeof data.ra.ra_count !== 'undefined') {
                    $ra_count = data.ra.ra_count;
                }
                // 제한시간 갱신
                if (typeof data.ra.now_turn !== 'undefined' && data.ra.now_turn !== $now_turn) {
                    $now_turn = data.ra.now_turn;
                    if (typeof data.ra.time_remaining !== 'undefined') {
                        $time_remaining = parseInt(data.ra.time_remaining);
                        timeLimitProcessing = false;
                        updateTimerDisplay();
                    }
                }
            }
        }
        
        // Pusher 초기화 실행
        $(document).ready(function() {
            initPusher();
        });
    })();
}

// ============================================
// 제한시간 타이머 시스템
// ============================================
var TimeLimitInterval = null;
var timeLimitProcessing = false;

function updateTimerDisplay() {
    var $timer = $('#time-remaining');
    if ($timer.length) {
        $timer.text($time_remaining);
        
        // 긴급 상태 표시 (5초 이하)
        if ($time_remaining <= 5) {
            $('#time-limit-area').addClass('urgent');
        } else {
            $('#time-limit-area').removeClass('urgent');
        }
    }
}

function handleTimeout() {
    // 이미 처리 중이면 중복 호출 방지
    if (timeLimitProcessing) {
        return;
    }
    timeLimitProcessing = true;
    
    // 타이머 정지
    if (TimeLimitInterval) {
        clearInterval(TimeLimitInterval);
        TimeLimitInterval = null;
    }
    
    var formData = new FormData();
    formData.append("rm_id", $rm_id);
    
    $.ajax({
        url: g5_url + "/k_battle/skin_realtime/_action_timeout.php" + $get,
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        type: "POST",
        success: function(data) {
            if (data && data.result === 'success') {
                reloadRaid();
            } else if (data && data.result === 'already_processed') {
                // 이미 다른 클라이언트에서 처리됨 - 새로고침으로 동기화
                reloadRaid();
            } else {
                // 기타 오류 - 새로고침
                reloadRaid();
            }
        },
        error: function() {
            // 오류 시 새로고침
            reloadRaid();
        }
    });
}

function startTimeLimit() {
    // 제한시간 미설정 또는 전투 중 아님
    if ($time_limit <= 0 || $ra_state !== 1) {
        return;
    }
    
    // free 타입은 제한시간 미적용
    if ($turn_type === 'free') {
        return;
    }
    
    // 기존 타이머 정리
    if (TimeLimitInterval) {
        clearInterval(TimeLimitInterval);
    }
    
    // 초기 표시
    updateTimerDisplay();
    
    // 1초마다 카운트다운
    TimeLimitInterval = setInterval(function() {
        $time_remaining--;
        updateTimerDisplay();
        
        if ($time_remaining <= 0) {
            handleTimeout();
        }
    }, 1000);
}

// 페이지 로드 시 타이머 시작
$(document).ready(function() {
    startTimeLimit();
});

