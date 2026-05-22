<?
$po_list=get_pokemon_list($ch['ch_id'],false);
$po=get_pokemon($po_list[0]['ph_id']);
?>

<div class="partner-area" id="partner">
    <ul class="partner_tab" id="partner_tab">
        <?for ($i=0; $i < count($po_list); $i++) { ?>
            <li class="<?if($i==0){echo " on";}?>" data-id="<?=$po_list[$i]['ph_id']?>"><img src="<?=$po_list[$i]['po_dot']?>"><span><?=$po_list[$i]['po_name']?></span></li>	
        <?}
        for ($i=0; $i < ($pkm_cf['po_max'] - count($po_list)); $i++) {
            echo "<li class='empty'></li>";
        }
        ?>
    </ul>
    <div class="partner-inner on" id="partner-inner">
        <?@include(G5_PATH."/pokemon/info/pokemon_info.php");?>
    </div>
</div>
<script>
    $("#partner_tab li").on('click',function(){
        var id=$(this).data('id');
        
        $(this).siblings('li').removeClass('on');
        $(this).addClass('on');

        var h_link = g5_url+"/pokemon/info/_ajax.info.php?ph_id=" + id;
        $.ajax({
            async: true
            , url: h_link
            , beforeSend: function() {}
            , success: function(data) {
                // Toss
                var response = data;
                $('#partner-inner').empty().append(response);
            }
            , error: function(data, status, err) {
            }
            , complete: function() { 
            }
        });

    });
</script>