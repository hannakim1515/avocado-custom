function actInfo(type, bsId=0){
    if(type!=''){
        var formData = new FormData();
        if(bsId>0){formData.append("bs_id", bsId);}
        formData.append("type", type);
        $.ajax({
            url: g5_url+"/k_battle/ajax/action_info.php"+$get+"&rm_id="+$rm_id,
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            type: 'POST',
            success: function(data){
                if(data){
                    $("#action-info .warning").text(data.warning);
                    $("#action-info .info").text(data.info);
                    $("#action-info .target").empty().html(data.target);
                }
            },
            error: function(xhr){
                console.log('action_info error:', xhr.status, xhr.responseText);
            }
        });
    }else{
        $("#action-info .warning").text('아무 것도 하지 않습니다.');
        $("#action-info .info").text('');
        $("#action-info .target").empty().html('');
    }
}