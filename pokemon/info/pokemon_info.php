
<?
if(!$po){$po=get_pokemon($ch['ph_id']);}
$po_ch=get_character($po['ch_id']);?>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<div class="po_info_area">
    <div class="po_name_area">
        <div class="name_area_top">
            <?if($po['po_ribbon']['it_value']){echo "<span class=\"po_ribbon_title\">[".$po['po_ribbon']['it_value']."]</span>";
            }else{echo "[{$po_ch['ch_name']}의 파트너]";}?></span>
        </div>
        <div class="name_area_left">
            <span class="po_ball_img"><img src="<?if(!$po['po_ball']['it_img']){echo G5_URL."/pokemon/img/objects/ball_default.png";}else{echo $po['po_ball']['it_img'];}?>"></span>
            <div class="name_area">
                <span><?=$po['po_name']?></span>
            </div>
            <div class="type_area">
                <p>
                    <span class="po_type <?=$po['po_type1']?>"><?=$po['po_type1']?></span>
                    <?if($po['po_type2']){?><span class="po_type <?=$po['po_type2']?>"><?=$po['po_type2']?></span><?}?>
                </p>
                <p>
                    <span class="po_species"><?=$po['po_species']?></span>
                    <?if($po['ph_sex']==1){echo "<span class=\"po_gender\">♀</span>";}elseif($po['ph_sex']==2){echo "<span class=\"po_gender\">♂</span>";}?>
                </p>
              
            </div>
        </div>
        <div class="name_area_right">
            <?if($po_ch['ph_id']==$po['ph_id']){?><span class="po_main"></span><?}?>
            <?if($po['po_ribbon']['it_img']){?><span class="po_ribbon_img" style="background-image:url(<?=$po['po_ribbon']['it_img']?>)"></span><?}?>
            <span class="po_rev_img rev_<?=$po['po_rev']?>"></span>
        </div>
    </div>
    <div class="po_detail_area">
        <div class="po_chart">
            <canvas id="chart_<?=$po['ph_id']?>"></canvas>
            <div class="po_dot"><img src="<?=$po['po_dot']?>"></div>
            <div class="stat_name st_1 po_<?=$po['po_st1']?>"><?=$pkm_cf['st_1_icon']?><span><?=$pkm_cf['st1_name']?> <?=$po['po_st1']?></span></div>
            <div class="stat_name st_2 po_<?=$po['po_st2']?>"><?=$pkm_cf['st_2_icon']?><span><?=$pkm_cf['st2_name']?> <?=$po['po_st2']?></span></div>
            <div class="stat_name st_3 po_<?=$po['po_st3']?>"><?=$pkm_cf['st_3_icon']?><span><?=$pkm_cf['st3_name']?> <?=$po['po_st3']?></span></div>
            <div class="stat_name st_4 po_<?=$po['po_st4']?>"><?=$pkm_cf['st_4_icon']?><span><?=$pkm_cf['st4_name']?> <?=$po['po_st4']?></span></div>
            <div class="stat_name st_5 po_<?=$po['po_st5']?>"><?=$pkm_cf['st_5_icon']?><span><?=$pkm_cf['st5_name']?> <?=$po['po_st5']?></span></div>
        </div>
        <div class="po_memo">
            <div class="po_content_area">
                <?if(!$po['po_content']){$po['po_content']="트레이너 메모가 없습니다.";}echo $po['po_content'];?>
            </div>
            <div class="po_pers_area">
                <p><?=$po['po_pers1']?></p>
                <p><?=$po['po_date']?>에 <? if($po['ma_name']){echo $po['ma_name'];}else{echo "어딘가";}?>에서 만났다.</p>
                <p><?=$po['po_pers2']?></p>
            </div>
        </div>
    </div>
<?if($po['ch_id']==$character['ch_id']&&$character['ch_state']=="승인"){?><a class="po_admin" target="_blank" href="<?=G5_URL?>/mypage/pokemon/?ph_id=<?=$po['ph_id']?>" class="ui-btn">관리</a><?}?>
</div>
<script>
var data = {
    labels: ['','','','',''],
    datasets: [{
        data: [	<?=(15+$po['po_st1'])?>,<?=(15+$po['po_st2'])?>,<?=(15+$po['po_st3'])?>,<?=(15+$po['po_st4'])?>,<?=(15+$po['po_st5'])?>],
        fill: true,
        backgroundColor: '#7AB7A1',
        pointHoverRadius :0,
        pointRadius: 0,
        pointStyle: ''
    }]
    };
var config = {
  type: 'radar',
  data: data,
  options: {
	tooltips: {
         enabled: true
    },
	  legend: {
    	display: false
    },
  maintainAspectRatio: false,
  scale: {
    angleLines: {
        color: 'rgba(0,0,0,0)'
    },
    gridLines:{
    		color : 'transparent'
    },
   ticks: {
		beginAtZero : true,
        max : 45,
        backdropColor: "rgba(0, 0, 0, 0)",
        display:false
        }
    },
    elements: {
      line: {
        borderWidth: 1
      }
    }
  },
};
var marksCanvas = document.getElementById("chart_<?=$po['ph_id']?>");
var radar = new Chart(marksCanvas, config);
</script>