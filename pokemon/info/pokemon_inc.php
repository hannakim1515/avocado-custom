<?
if(!$po['ph_id']||!$character['ch_id']){
    echo "포켓몬 정보를 찾을 수 없습니다.";
}else{
    if($po['ch_id']==$character['ch_id']){$ismine=true;}?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
    <style>
        @import url(<?=G5_URL?>/pokemon/info/info.css);
    </style>
    
    <div class="pokemon_setting">
        
        <div class="pokemon_info">
            <?@include(G5_PATH."/pokemon/info/pokemon_info.php");?>
        </div>
        <div class="pokemon_adm">
            <?@include(G5_PATH."/pokemon/info/pokemon_adm.php");?>
        </div>
    </div>
<?}?>
