<?include_once('../../common.php');
require_once(G5_PATH.'/head.sub.php');
@include("./menu.php");

$lo_sql=sql_query("SELECT lo.*,atk.ch_id AS atk_ch_id,atk.po_name AS atk_name,def.po_name AS def_name, def.ch_id AS def_ch_id 
                    FROM {$g5['pokemon_battle_log_table']} lo 
                    LEFT JOIN {$g5['pokemon_battle_table']} atk ON lo.ph_id=atk.ph_id 
                    LEFT JOIN {$g5['pokemon_battle_table']} def ON lo.re_ph_id=def.ph_id 
                    WHERE (lo.ph_id='{$ph['ph_id']}' or lo.re_ph_id='{$ph['ph_id']}') and lo.lo_order=4 order by lo_datetime desc");
?>
	<table class="theme-form">
		<colgroup>
			<col/>
			<col/>
			<col/>
			<col/>
			<col/>
		</colgroup>
		<thead>
			<tr>
                <th scope="col">날짜</th>
				<th scope="col">파트너</th>
				<th scope="col">상대</th>
				<th scope="col">승패</th>
				<th scope="col">로그</th>
			</tr>
		</thead>
		<tbody>
			<?for ($i=0; $row=sql_fetch_array($lo_sql); $i++) {
                if($row['atk_ch_id']==$character['ch_id']){
                    $enemy_name=$row['def_name'];
					$my_name=$row['atk_name'];
                    $battle_result="승리";
                }else{
                    $enemy_name=$row['atk_name'];
					$my_name=$row['def_name'];
                    $battle_result="패배";
                }
                if(strstr($row['lo_msg'], '무승부')){
                    $battle_result="무승부";
                }
                $board_link=get_board_link($row['bo_table'],$row['wr_id']);
            ?>
			<tr>
				<td style="text-align:center; color:black"><?=$row['lo_datetime']?></td>
				<td style="text-align:center; color:black"><?=$my_name?></td>
				<td style="text-align:center; color:black"><?=$enemy_name?></td>
				<td style="text-align:center; color:black"><?php echo $battle_result; ?></td>
				<td style="text-align:center; color:black"><a href="<?=$board_link?>" target="_blank">로그</a></td>
			</tr>
			<?php
			}

			if ($i == 0) echo '<tr><td colspan="5" class="no-data">자료가 없습니다.</td></tr>';
			?>
		</tbody>
	</table>

<?require_once(G5_PATH.'/tail.sub.php');?>