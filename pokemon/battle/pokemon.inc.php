
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>@import url(<?=G5_URL?>/pokemon/info/info.css);</style>
<div class="po_info_area">
    <div class="po_name_area">
        <div class="name_area_top">
            <?if($po['po_ribbon']['it_value']){echo "<span class=\"po_ribbon_title\">[".$po['po_ribbon']['it_value']."]</span>";}else{echo "[{$po_ch['ch_name']}의 파트너]";}?>
        </div>
            <div class="name_area_left">
            <span class="po_ball_img"><img src="<?if(!$po['po_ball']['it_img']){echo G5_URL."/pokemon/img/objects/ball_default.png";}else{echo $po['po_ball']['it_img'];}?>"></span>
            <div class="name_area">
                <span><?=$po['po_name']?></span>
            </div>
            <div class="type_area">
                <?if($po_ch['ch_state']=='승인'){?>
                <p>
                    <span class="po_type <?=$po['po_type1']?>"><?=$po['po_type1']?></span>
                    <?if($po['po_type2']){?><span class="po_type <?=$po['po_type2']?>"><?=$po['po_type2']?></span><?}?>
                </p>
                <?}?>
                <p>
                    <span class="po_species"><?=$po['po_species']?></span>
                    <?if($po['ph_sex']==1){echo "<span class=\"po_gender\">♀</span>";}elseif($po['ph_sex']==2){echo "<span class=\"po_gender\">♂</span>";}?>
                </p>
              
            </div>
        </div>
        <div class="name_area_right">
            <?if($po_ch['ph_id']==$po['ph_id']){?><span class="po_main"></span><?}?>
            <?if($po['po_ribbon']['it_img']){?><span class="po_ribbon_img" style="background-image:url(<?=$po['po_ribbon']['it_img']?>)"></span><?}?>
        </div>
    </div>
    <div class="po_detail_area">
        <div class="po_chart">
            <canvas id="chart_<?=$ph['ph_id']?>"></canvas>
            <div class="po_dot" style="width:auto;"><img src="<?=$ph['po_dot']?>"><p style="text-align:center;">HP <?=$ph['hp_max']?></p></div>
            <div class="stat_name st_1 po_<?=$ph['po_st1']?>">sports_mma<span>공격 <?=$ph['a']?></span></div>
            <div class="stat_name st_2 po_<?=$ph['po_st2']?>">shield<span>방어 <?=$ph['b']?></span></div>
            <div class="stat_name st_3 po_<?=$ph['po_st3']?>">brightness_5<span>특공 <?=$ph['c']?></span></div>
            <div class="stat_name st_4 po_<?=$ph['po_st4']?>">shield_moon<span>특방 <?=$ph['d']?></span></div>
            <div class="stat_name st_5 po_<?=$ph['po_st5']?>">double_arrow<span>속도 <?=$ph['s']?></span></div>
        </div>
        <div class="po_memo">
            <div class="po_content_area">
                <?if(!$po['po_content']){$po['po_content']="트레이너 메모가 없습니다.";}echo $po['po_content'];?>
            </div>
        </div>
    </div>
    <?if($po['ch_id']==$character['ch_id']){?><a class="po_admin" target="_blank" href="<?=G5_URL?>/pokemon/battle/pokemon_insert.php?ph_id=<?=$ph_id?>" class="ui-btn">관리</a><?}?>
</div>
<script>
var data = {
    labels: ['','','','',''],
    datasets: [{
        data: [	<?=$ph['a']?>,<?=$ph['b']?>,<?=$ph['c']?>,<?=$ph['d']?>,<?=$ph['s']?>],
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
        max : 150,
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
var marksCanvas = document.getElementById("chart_<?=$ph['ph_id']?>");
var radar = new Chart(marksCanvas, config);
</script>