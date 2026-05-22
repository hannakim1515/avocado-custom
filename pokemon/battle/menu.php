<?php
    $po_list=array();
    $ph_sql = sql_query("SELECT po_name, ph_id, po_content from {$g5['pokemon_has_table']} where ch_id='{$character['ch_id']}' and ph_type!='friend' order by ph_type desc");
    for($i = 0; $row = sql_fetch_array($ph_sql); $i++) {
        $po_list[] = $row;
    };
    if(!$ph_id){$ph_id=$po_list[0]['ph_id'];}
    $po=get_pokemon($ph_id);
    $ph=get_battle_pokemon($ph_id, false);
    if($po['ch_id']!=$character['ch_id']&&!$is_admin){
        alert("잘못된 접근입니다.");
    }
    $po_ch=$character;
?>
<style>@import url("./style.css");</style>
<ul class="menu">
    <?for ($i=0; $i < count($po_list); $i++) {
    ?>
        <li class="<?if($po_list[$i]['ph_id']==$ph_id){echo 'on';}?>"><a href="?ph_id=<?=$po_list[$i]['ph_id']?>"><?=$po_list[$i]['po_name']?></a></li>
    <?}?>
</ul>
<ul class="menu">
    <li><a href="./index.php?ph_id=<?=$ph_id?>">능력치</a></li>
    <li><a href="./skill.php?ph_id=<?=$ph_id?>">기술</a></li>
    <li><a href="./record.php?ph_id=<?=$ph_id?>">전적</a></li>
</ul>
