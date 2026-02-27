<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 1. 테이블명 적어서 확인할것
$bo_table = "test2"; 
$table_name = "avo_write_" . $bo_table;

if (preg_match('/%/', $width)) {
    $col_width = "14%";
} else {
    $col_width = round($width/7);
}
$col_height = 30;
$today = getdate(); 
$b_mon = $today['mon']; 
$b_day = $today['mday']; 
$b_year = $today['year']; 

if ($year < 1) {
    $month = $b_mon;
    $year = $b_year;
}

if(!$year) $year = date("Y");
$lastday = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
if ($year%4 == 0) $lastday[2] = 29;
$dayoftheweek = date("w", mktime(0,0,0,$month,1,$year));

add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);

$months_m = array("", "JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER");
$month_m = $months_m[(int)$month];
?> 

<div class="sched-list">
<div class="cal-nav"> 
    <h2 class="txt-point txt-center">
        <span><?=$month_m?> <?=$year?></span>
    </h2> 
</div>
<table border="0" cellspacing="1" class="theme-list">
<thead>
  <tr align="center">     
    <th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th>
  </tr>
</thead>
<tbody>
<tr><td colspan="7" style="height:10px;"></td></tr>
<?php
$cday = 1;
$sel_mon = sprintf("%02d",$month);
$current_month_str = $year . $sel_mon;

// 쿼리문
$sql = " SELECT * FROM `{$table_name}` 
          WHERE wr_1 != '' 
          AND REPLACE(LEFT(wr_1,10),'-','') <= '{$current_month_str}31' 
          AND (wr_2 = '' OR REPLACE(LEFT(wr_2,10),'-','') >= '{$current_month_str}01') 
          AND wr_is_comment = 0 
          ORDER BY wr_1 ASC, wr_id ASC ";
$result = sql_query($sql);

$html_day = array();
if($result) {
    while ($row = sql_fetch_array($result)) {
        $s_val = preg_replace("/[^0-9]/", "", $row['wr_1']);
        $e_val = $row['wr_2'] ? preg_replace("/[^0-9]/", "", $row['wr_2']) : $s_val;

        $start_day = (substr($s_val, 0, 6) < $current_month_str) ? 1 : (int)substr($s_val, 6, 2);
        $end_day = (substr($e_val, 0, 6) > $current_month_str) ? (int)$lastday[$month] : (int)substr($e_val, 6, 2);

        $imgown = $row['wr_3'] ? $row['wr_3'] : 'icon';
        $subject = cut_str($row['wr_subject'], 15);
        
        // 오류가 났던 get_pretty_url 대신 기본 방식의 링크를 생성합니다.
        $post_url = G5_BBS_URL."/board.php?bo_table=".$bo_table."&wr_id=".$row['wr_id'];

        for ($i = $start_day; $i <= $end_day; $i++) {
            $d = date("w", mktime(0,0,0,$month,$i,$year));
            $starter = "liner" . ($i==$start_day ? " starter" : "") . ($i==$end_day ? " ender" : "") . ($d==0 ? " first" : "") . ($d==6 ? " last" : "");
            
            $html_day[$i] .= '<a href="'.$post_url.'" class="txt-default '.$starter.'">';
            if($i == $start_day) $html_day[$i] .= '<p class="s_subject '.$imgown.'">'.$subject.'</p>';
            else $html_day[$i] .= '<p class="s_subject '.$imgown.'"><span class="sound_only">'.$row['wr_subject'].'</span></p>';
            
            if($i == $start_day || $d == 0){
                $html_day[$i] .= '<div class="popup_layer '.$imgown.'"><p class="popup_title">'.$row['wr_subject'].'</p>';
                $html_day[$i] .= '<p class="popup_cont">'.date("Y.m.d", strtotime($row['wr_1'])).' ~ '.date("Y.m.d", strtotime($row['wr_2'] ? $row['wr_2'] : $row['wr_1'])).'<br>'.$row['wr_10'].'</p></div>';
            }
            $html_day[$i] .= '</a>';
        }
    }
}

$temp = 7 - (($lastday[$month] + $dayoftheweek) % 7);
if ($temp == 7) $temp = 0;
$lastcount = $lastday[$month] + $dayoftheweek + $temp;

for ($iz = 1; $iz <= $lastcount; $iz++) {
    $re = $iz % 7;
    if ($re == 1) echo "<tr>";

    if ($iz > $dayoftheweek && $cday <= $lastday[$month]) {
        $bgcolor = ($b_year==$year && $b_mon==$month && $b_day==$cday) ? "today" : "days";
        $col = ($re==0) ? "right" : (($re==1) ? "left" : "");
        
        echo "<td width='{$col_width}' height='{$col_height}' class='{$bgcolor} {$col}' valign='top'>";
        echo "<i>{$cday}</i>";
        echo isset($html_day[$cday]) ? $html_day[$cday] : "";
        echo "</td>";
        $cday++;
    } else {
        echo "<td class='noday'>&nbsp;</td>";
    }
    if ($re == 0) echo "</tr>";
}
?>
</tbody>
</table>
</div>