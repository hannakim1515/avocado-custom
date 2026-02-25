<?php
$sub_menu = "980130";
include_once('./_common.php');

$g5['title'] = '커스텀 함수 복사';
include_once('./admin.head.php');
include_once('./admin.stat.php'); // 여기서 $func_list가 정의된다고 가정하되, 아래에서 부재 시 방어

// 상태 설정 목록 로드
$status = array();
$st_result = sql_query("SELECT * FROM {$g5['status_config_table']} ORDER BY st_order ASC");
for ($i = 0; $row = sql_fetch_array($st_result); $i++) {
    $status[$i] = $row;
}

// $func_list 방어
if (!isset($func_list) || !is_array($func_list)) {
    $func_list = array();
}
?>
<style>
#container{display:flex;height:calc(100vh - 110px);width:calc(100vw - 220px);padding:10px;position:relative;}
.php_func{max-width:100%;height:calc(100% - 150px);}
.php_func textarea{height:100%;}
.php_func p{background-color:black;color:white;padding:10px;font-size:14px;}
.php_func p span{color:yellow;}
.box{max-width:800px;width:100%;padding:10px;position:relative;}
.box:hover{background-color:lightgrey;}
</style>

<div class="box">
    <h2 class="h2_frm">커스텀 스탯</h2>
    <?php
    $sc = sql_query("SELECT * FROM {$g5['k_stat_table']} WHERE sc_category='stat'");
    $all_func = '';

    for ($i = 0; $row = sql_fetch_array($sc); $i++) {
        $func = '';
		
        $val  = explode('|', (string)$row['sc_value']);
        $type = explode('|', (string)$row['sc_type']);

        $cnt  = min(count($val), count($type));

        for ($h = 0; $h < $cnt; $h++) {
            $t = (string)$type[$h];
            $v = (string)$val[$h];

            if ($t === 'stat') {
                // $v는 st_id 숫자
                $func .= "\$unit_st[".(int)$v."]";
            } else {
                // 제한 없이 원문 토큰 그대로 붙임
                $func .= $v;
            }
        }

        $body  = "\t\$result=".$func.";\n";
        if ($row['sc_1'] !== '') { $body .= "\t\$round=\"".$row['sc_1']."\";\n"; }
        if ($row['sc_2'] !== '') { $body .= "\t\$minvalue=".$row['sc_2'].";\n"; }
        if ($row['sc_3'] !== '') { $body .= "\t\$maxvalue=".$row['sc_3'].";\n"; }

        $all_func .= "if(\$sc_id==".(int)$row['sc_id']."){//".$row['sc_name']."\n".$body."}\n";
    }
    ?>
    <div class="php_func">
        <p>아래 상자 안의 모든 글자를 <span>[아보카도 경로/k_battle/extend/battle/status.inc.php]</span> 파일 안에 붙여넣으세요. ctrl+a 후 ctrl+c 추천.</p>
        <textarea><?php echo "<?php\n".$all_func."?>"; ?></textarea>
    </div>
</div>

<div class="box">
    <h2 class="h2_frm">전투 함수</h2>
    <?php
    $all_func = '';

    for ($i = 0; $i < count($func_list); $i++) {
		
        $code = isset($func_list[$i]['code']) ? (string)$func_list[$i]['code'] : '';
        if ($code === '') { continue; }

        // sql_escape_string 부재 시 addslashes 사용
        $esc_code = function_exists('sql_escape_string') ? sql_escape_string($code) : addslashes($code);
        $sc = sql_fetch("SELECT * FROM {$g5['k_stat_table']} WHERE sc_name='{$esc_code}' AND sc_category='battle'");

        if (!empty($sc['sc_name'])) {
            $val  = explode('|', (string)$sc['sc_value']);
            $type = explode('|', (string)$sc['sc_type']);
            $cnt  = min(count($val), count($type));

            $func = '';
            for ($h = 0; $h < $cnt; $h++) {
                $t = (string)$type[$h];
                $v = (string)$val[$h];

                if     ($t === 'stat')   { $func .= "\$unit[\$k_unit_stat[".(int)$v."]]"; }
                elseif ($t === 'stat_r') { $func .= "\$target[\$k_unit_stat[".(int)$v."]]"; }
                elseif ($t === 'func_m') { $func .= "(get_k_battle_func(\"".addslashes($v)."\", \$unit, \$target)['value'])"; }
                elseif ($t === 'func_r') { $func .= "(get_k_battle_func(\"".addslashes($v)."\", \$target, \$unit)['value'])"; }
                else { $func .= $v; }
            }

            $body  = "\t\$result=".$func.";\n";
            if ($sc['sc_1'] !== '') { $body .= "\t\$round=\"".$sc['sc_1']."\";\n"; }
            if ($sc['sc_2'] !== '') { $body .= "\t\$minvalue=".$sc['sc_2'].";\n"; }
            if ($sc['sc_3'] !== '') { $body .= "\t\$maxvalue=".$sc['sc_3'].";\n"; }
            if (!empty($sc['sc_4'])) { $body .= "\t\$cri=true;\n"; }
            if ($sc['sc_5'] !== '') { $body .= "\t\$randbonus=".$sc['sc_5'].";\n"; }

            $all_func .= "if(\$type==\"".addslashes($sc['sc_name'])."\"){//".addslashes($sc['sc_name'])."\n".$body."}\n";
        }
    }
    ?>
    <div class="php_func">
        <p>아래 상자 안의 모든 글자를 <span>[아보카도 경로/k_battle/extend/battle/battlefunc.inc.php]</span> 파일 안에 붙여넣으세요. ctrl+a 후 ctrl+c 추천.</p>
        <textarea><?php echo "<?php\n".$all_func."?>"; ?></textarea>
    </div>
</div>

<?php
include_once('./admin.tail.php');
