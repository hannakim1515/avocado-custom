<?if($character['ch_state']=='승인'){
    $cm_list=array();
    $command_sql = sql_query("SELECT * from {$g5['pokemon_map_table']} where ma_type='command'");
    for($i = 0; $row = sql_fetch_array($command_sql); $i++) {
        $cm_list[$row['ma_name']] = $row;
    };

    $encount_able=$partner_able=$evo_able=$release_able=$battle_able=false;

    $encount_check=count_pokemon_item($character['ch_id'], 'encount');
    $partner_check=count_pokemon_item($character['ch_id'], 'partner');
    $evo_check=count_pokemon_item($character['ch_id'], 'evo');
    $release_check=count_pokemon_item($character['ch_id'], 'release');
    $count_friend=sql_fetch (" SELECT count(ph_id) as cnt from {$g5['pokemon_has_table']} where ch_id='{$character['ch_id']}' and ph_type='friend' and ph_egg=0 ");
    $evo_list=array();
    $partner_list=get_pokemon_list($character['ch_id'], false);
    for ($i=0; $i < count($partner_list); $i++) { 
        $evo_che=sql_fetch (" SELECT po_id from {$g5['pokemon_table']} where po_ex='{$partner_list[$i]['po_id']}'");
        if($evo_che['po_id']){
            $evo_list[]=$partner_list[$i];
            $partner_list[$i]['is_evo']="evo select";
        }
    }
    $battle_ph_list=array();
    $battle_ph_sql = sql_query("SELECT ph_id, po_name, is_battle from {$g5['pokemon_battle_table']} where ch_id='{$character['ch_id']}'");
    for($i = 0; $row = sql_fetch_array($battle_ph_sql); $i++) {
        $battle_ph_list[] = $row;
    }; 
                      

    if($cm_list['encount']['ma_use']&&$encount_check>0){$encount_able=true;}
    if($cm_list['partner']['ma_use']&&$partner_check>0&&$count_friend['cnt']>0&&count($partner_list)<$pkm_cf['po_max']){$partner_able=true;}
    if($cm_list['evolution']['ma_use']&&$evo_check>0&&count($evo_list)>0){$evo_able=true;}
    if($cm_list['release']['ma_use']&&$release_check>0&&count($partner_list)>1){$release_able=true;}
    if($cm_list['battle']['ma_use']&&count($battle_ph_list)){$battle_able=true;}
}

$cm_list=array();
$command_sql = sql_query("SELECT * from {$g5['pokemon_map_table']} where ma_type='command'");
for($i = 0; $row = sql_fetch_array($command_sql); $i++) {
    $cm_list[$row['ma_name']] = $row;
};
?>

<style>
    @import url(<?=G5_URL?>/pokemon/encount/encount.css);
</style>

<div class="mmb_action_area">

    <div id="action_pokemon" class="action_main">
        <label for="select_normal" class="action on" value="normal">
            <input type="radio" name="action_pokemon" value="0" id="select_normal">
                일반
        </label>
        <?if($character['ch_state']=='승인'){
            
            if($encount_able){?>
                <label for="select_encount" class="action" data-value="encount">
                    <input type="radio" name="action_pokemon" value="encount" id="select_encount">
                    조우
                </label>
            <?}
            if($partner_able){?>
                <label for="select_partner" class="action" data-value="partner">
                    <input type="radio" name="action_pokemon" value="partner" id="select_partner">
                    포획
                </label>
            <?}
            if($evo_able){?>
                <label for="select_evolution" class="action" data-value="evolution">
                    <input type="radio" name="action_pokemon" value="evolution" id="select_evolution">
                    진화
                </label>
            <?}
            if($release_able){?>
                <label for="select_release" class="action" data-value="release">
                    <input type="radio" name="action_pokemon" value="release" id="select_release">
                    이별
                </label>
            <?}
            if($battle_able){?>
                <label for="select_battle" class="action" data-value="battle">
                    <input type="radio" name="action_pokemon" value="battle" id="select_battle">
                    배틀
                </label>
            <?}
        }?>
    </div>

    <?if($character['ch_state']=='승인'){
        if($encount_able){//일반조우?>
            <div class="comment-data pokemon" id="action_encount">
                <div class="action_inner">
                    <div class="select_list">
                        <?$map_list=array();
                        $map_sql = sql_query("SELECT * from {$g5['pokemon_map_table']} where ma_type='default' and ma_use=1");
                        for($i = 0; $row = sql_fetch_array($map_sql); $i++) {?>
                            <label for="encount_<?=$row['ma_id']?>" class="select"><input id="encount_<?=$row['ma_id']?>" type="radio" name="encount" value="<?=$row['ma_id']?>"><?=$row['ma_name']?></label>
                        <?};?>
                    </div>
                    <div class="select_inner" style="background-image:url(<?=$cm_list['encount']['ma_img']?>)"></div>
                    <p class="select_msg">
                        <?
                        $cm_list['encount']['ma_content'] = str_replace('{갯수}', $encount_check, $cm_list['encount']['ma_content']);
                        echo nl2br($cm_list['encount']['ma_content']);
                        ?>
                    </p>
                </div>
            </div>
        <?}
        if($partner_able){//파트너?>
            <div class="comment-data pokemon" id="action_partner">
                <div class="action_inner">
                    <div class="select_list" style="background-image:url(<?=$cm_list['partner']['ma_img']?>)">
                        <p>만난 포켓몬</p>                    
                        <div class="box_wrapper">
                            <?$friend_list=array();
                            $friend_sql = sql_query("SELECT * from {$g5['pokemon_has_table']} ph, {$g5['pokemon_table']} po where ph.ch_id='{$character['ch_id']}' and ph.ph_type='friend' and ph.ph_egg=0 and ph.po_id=po.po_id");
                            for($i = 0; $row = sql_fetch_array($friend_sql); $i++) {
                                if(!$row['po_dot']){$row['po_dot']=G5_URL."/pokemon/img/{$row['po_id']}.png";}
                                if($row['po_name']){$row['po_species']=$row['po_name'];}?>
                                <label for="partner_<?=$row['ph_id']?>" class="select"><input id="partner_<?=$row['ph_id']?>" type="radio" name="partner" value="<?=$row['ph_id']?>"><img src="<?=$row['po_dot']?>"></label>
                            <?};?>
                        </div>
                    </div>
                    <div class="select_inner"></div>
                    <p class="select_msg"><?echo nl2br($cm_list['partner']['ma_content'])?></p>
                </div>
            </div>
        <?}
        if($evo_able){//진화?>
            <div class="comment-data pokemon" id="action_evolution">
                <div class="action_inner">
                    <div class="select_list">
                        <?for ($i=0; $i < count($partner_list); $i++) { 
                            if($partner_list[$i]['po_name']){$partner_list[$i]['po_species']=$partner_list[$i]['po_name'];}?>
                            <label for="evolution_<?=$partner_list[$i]['ph_id']?>" class="<?=$partner_list[$i]['is_evo']?>">
                                <?if($partner_list[$i]['is_evo']){?><input id="evolution_<?=$partner_list[$i]['ph_id']?>" type="radio" name="evolution" value="<?=$partner_list[$i]['ph_id']?>"><?}?>
                                <img src="<?=$partner_list[$i]['po_dot']?>"><?=$partner_list[$i]['po_species']?>
                            </label>
                        <?};?>
                    </div>
                    <div class="select_inner" style="background-image:url(<?=$cm_list['evolution']['ma_img']?>)"></div>
                    <p class="select_msg" id="evo_msg"><?echo nl2br($cm_list['evolution']['ma_content'])?></p>			
                </div>						
            </div>
        <?}					
        if($release_able){//이별?>
            <div class="comment-data pokemon" id="action_release">
                <div class="action_inner">
                    <div class="select_list"  style="background-image:url(<?=$cm_list['release']['ma_img']?>)">
                        <?for ($i=0; $i < count($partner_list); $i++) { 
                            if($partner_list[$i]['ph_type']=='partner'){
                            if($partner_list[$i]['po_name']){$partner_list[$i]['po_species']=$partner_list[$i]['po_name'];}?>
                            <label for="release_<?=$partner_list[$i]['ph_id']?>" class="select"><input id="release_<?=$partner_list[$i]['ph_id']?>" type="radio" name="release" value="<?=$partner_list[$i]['ph_id']?>">
                                <img src="<?=$partner_list[$i]['po_dot']?>">
                                <p><?=$partner_list[$i]['po_species']?></p>
                            </label>
                        <?}
                        };?>
                    </div>
                    <p class="select_msg"><?echo nl2br($cm_list['release']['ma_content'])?></p>	
                </div>
            </div>
        <?} 
        if($battle_able){//배틀    
            $exclude_ch_id = (int)($character['ch_id'] ?? 0);
            $sql = "
            SELECT DISTINCT ch.ch_id, ch.ch_name
            FROM {$g5['character_table']} AS ch
            INNER JOIN {$g5['pokemon_battle_table']} AS ph ON ph.ch_id = ch.ch_id
            WHERE ch.ch_id <> {$exclude_ch_id}
            ORDER BY ch.ch_name ASC
            ";
            $rs = sql_query($sql);

            $member_list = '';
            while ($row = sql_fetch_array($rs)) {
                $ch_id   = (int)($row['ch_id'] ?? 0);
                $ch_name = htmlspecialchars($row['ch_name'] ?? '', ENT_QUOTES, 'UTF-8');
                if ($ch_id > 0) {
                        $member_list .= "<option value=\"{$ch_id}\">배틀 신청 상대: {$ch_name}</option>";
                }
            }
            if ($member_list === '') {
                $member_list = '<option>대전 가능한 상대가 없습니다.</option>';
            }

            // 내 포켓몬 목록
            $ph_list = '';
            if (!empty($battle_ph_list) && is_array($battle_ph_list)) {
                foreach ($battle_ph_list as $ph) {
                        $ph_id   = (int)($ph['ph_id'] ?? 0);
                        $po_name = htmlspecialchars($ph['po_name'] ?? '', ENT_QUOTES, 'UTF-8');
                        if ($ph_id > 0&&$ph['is_battle']==0) {
                        $ph_list .= "<option value=\"{$ph_id}\">내보낼 포켓몬: {$po_name}</option>";
                        }
                }
            }
            if ($ph_list === '') {
                    $ph_list = '<option>선택 가능한 포켓몬이 없습니다.</option>';
            }
            $inner='<select name="battle_enemy">'.$member_list.'</select><select name="battle_ph_id">'.$ph_list.'</select><input type="text" name="battle_msg" placeholder="배틀 신청 메시지">';           ?>
            <div class="comment-data pokemon battle" id="action_battle">
                <div class="action_inner">
                    <div class="select_list" style="background-image:url(<?=$cm_list['battle']['ma_img']?>)">
                         <?=$inner?>
                    </div>
                    <p class="select_msg"><?echo nl2br($cm_list['battle']['ma_content'])?></p>
                </div>
            </div>
        <?}
    }?>
</div>

<?if($character['ch_state']=='승인'&&$character['egg_id']){
    $egg=get_pokemon($character['egg_id'],false);?>
    <div class="egg_area">
        <input type="hidden" name="egg_id" value="<?=$egg['ph_id']?>">
        <div class="egg_thumb"><img src="<?=G5_URL?>/pokemon/img/egg/egg_center.png"></div>
        <p class="egg_content"><?=$egg['po_content']?></p>
        <?if($character['egg_item']){?><p class="egg_item">알 영양제 사용중입니다.</p><?}?>
    </div>
<?}?>

<script>
    $('.action_main').on('change', function() {
        var view_idx = $(this).val();
        $(this).parent().siblings('.comment-data').removeClass('on');
        $('#action_'+ view_idx).addClass('on');
    });

    $('#action_pokemon label.action').on('click', function() {
        var view_idx = $(this).data('value');
        $(this).addClass('on');
        $(this).siblings().removeClass('on');
        $(this).parent().siblings('.comment-data').removeClass('on');
        $('#action_'+ view_idx).addClass('on');
    });

    $(".select_list label.select").on('click', function(){
        var val=$(this).find('input').val();
        var type=$(this).find('input').attr('name');
        var form_url ='/pokemon/encount/select_ajax.php';
        
        var parent=$(this).parents('.select_list');
        $(this).addClass('on');
        $(this).siblings().removeClass('on');
        if(val!=''){
            var formData = new FormData();
            formData.append('val', val);
            formData.append('type', type);
            $.ajax({
                type: 'post'
                , url : g5_url + form_url
                , data: formData
                , dataType:'json'
                , contentType: false
                , processData: false
                , success : function(data) {
                    if(data.inner){
                        parent.siblings('.select_inner').html(data.inner);
                    }
                    if(data.msg){
                        parent.siblings('.select_msg').html(data.msg);
                    }
                    
                }
            });
        }else{
            parent.siblings('.select_inner').empty();
        
            parent.siblings('.select_msg').html("행동을 그만둡니다.");
        
        }

    });
    function evo_select(e){
        if($(e).data('type')=='m'){
            var name1=$("input[name='color_select']:checked").parent().data('name');
            var name2=$("input[name='sugar_select']:checked").parent().data('name');
            $("#evo_msg").html(name1+"/"+name2+"(으)로 진화시킬까?");
        }else{
            var name=$(e).data('name');
            $("#evo_msg").html(name+"(으)로 진화시킬까?");
        }
        $(e).addClass('on');
        $(e).siblings().removeClass('on');
    }

</script>