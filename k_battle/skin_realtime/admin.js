function changeUnit(rm_id){
    if(rm_id){
        $rm_id = rm_id;
        // 페이지 새로고침하여 해당 유닛의 스킬 목록 등을 갱신
        var url = new URL(window.location.href);
        url.searchParams.set('select_rm_id', rm_id);
        window.location.href = url.toString();
    }
}

function action(type,unitType){

    $("#raid_act_btn").attr("onclick","");
    var targetId=$("#action_target").val();
    var targetType=$("#target_type").val();
    var targetCnt=$("#action_target_cnt").val();
    var bsId=$("#bs_id").val();

    if(targetId&&targetType){
        var formData = new FormData();
        formData.append("type", type);
        formData.append("unit_type", unitType);
        formData.append("target_id", targetId);
        formData.append("target_type", targetType);
        formData.append("target_cnt", targetCnt);
        if(bsId&&bsId>0){formData.append("bs_id", bsId);}

        if($log){
            formData.append("lo_id", $lo_id);
        }
        $.ajax({
            url: g5_url+"/k_battle/skin_realtime/_action_realtime.php"+$get+"&rm_id="+$rm_id
            , data: formData
            , processData: false
            , contentType: false
            , dataType: "json"
            , type: 'POST'
            , success: function(data){
                location.reload();
            }, error: function(xhr, status, error){
                console.log('AJAX ERROR:', status, error);
                console.log('RESPONSE:', xhr.responseText);
            }
        });

    }
    
  
}

function skip(){
    var formData = new FormData();
    $.ajax({
        url: g5_url+"/k_battle/skin_realtime/_action_skip.php"+$get
        , data: formData
        , processData: false
        , contentType: false
        , dataType: "json"
        , type: 'POST'
        , success: function(data){
            if(data){
               location.reload();
            }
        }
    });
}

function messageInsert(){
    var msg=$("#admin-msg").val();
    if(msg){

        if(!confirm("시스템 메시지를 전송합니다. ["+msg+"]")) {
			return false;
		}

        var formData = new FormData();
        formData.append("type", 'system');
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
                   location.reload();
                }
            }
        });
    }
   
}

function raidReset(type){
    if(type){

        if(!confirm("정말로 초기화할까요?")) {
			return false;
		}

        var formData = new FormData();
        formData.append("type", type);
        $.ajax({
            url: g5_url+"/k_battle/skin_realtime/_action_reset.php"+$get
            , data: formData
            , processData: false
            , contentType: false
            , dataType: "json"
            , type: 'POST'
            , success: function(data){
                if(data){
                   location.reload();
                }
            }
        });
    }
}

function raidState(type){
    
    if(type){
        var msg = "";
        switch(type) {
            case 'start':
                msg = "레이드를 시작할까요?";
                break;
            case 'ch_win':
                msg = "멤버 승리로 레이드를 종료할까요?";
                break;
            case 'mo_win':
                msg = "몬스터 승리로 레이드를 종료할까요?";
                break;
            case 'end':
                msg = "레이드를 단순 종료할까요? (보상 없음)";
                break;
            default:
                msg = "진행할까요?";
        }
        
        if(!confirm(msg)) {
			return false;
		}

        var formData = new FormData();
        formData.append("type", type);
        $.ajax({
            url: g5_url+"/k_battle/skin_realtime/_action_state.php"+$get
            , data: formData
            , processData: false
            , contentType: false
            , dataType: "json"
            , type: 'POST'
            , success: function(data){
                if(data){
                   location.reload();
                }
            }
        });
    }

}
