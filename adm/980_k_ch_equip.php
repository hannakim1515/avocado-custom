<?php
$sub_menu = '980310';
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
// 스탯 목록 로드
$st_list = array();
$stat_sql = sql_query("SELECT st_name FROM {$g5['status_config_table']} ORDER BY st_order ASC LIMIT 10");
for ($i = 0; $row = sql_fetch_array($stat_sql); $i++) {
    $tag = 'st_'.($i + 1);
    $row['tag'] = $tag;
    $st_list[] = $row;
}

// 공통 FROM/JOIN
$sql_common = " FROM {$g5['k_ch_equip_table']} eq
INNER JOIN {$g5['item_table']} it   ON it.it_id = eq.it_id
INNER JOIN {$g5['inventory_table']} inven ON inven.in_id = eq.in_id
LEFT  JOIN {$g5['k_upgrade_table']} ug ON eq.ug_id = ug.ug_id";

// 전체 건수
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common}");
$total_count = (int)$row['cnt'];

// 페이징
$rows = 50;
$total_page = $total_count > 0 ? (int)ceil($total_count / $rows) : 1;
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;

// 목록 쿼리
$select_cols = "eq.*, it.it_name, it.it_img, inven.ch_id, ug.ug_name";
$sql = "SELECT {$select_cols} {$sql_common} ORDER BY eq.eq_id DESC LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

$g5['title'] = '보유 장비 관리';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
?>
<h2 class="h2_frm">보유장비관리</h2>

<div class="tbl_head01 tbl_wrap">
  <table>
    <colgroup>
      <col style="width:100px" />
      <col style="width:100px" />
      <col />
      <col style="width:70px" />
      <?php for ($j = 0; $j < count($st_list); $j++) { echo "<col style=\"width:70px\" />"; } ?>
      <col style="width:100px" />
    </colgroup>
      <thead>
      <tr>
        <th>캐릭터</th>
        <th>장비</th>
        <th>커스텀</th>
        <th>강화</th>
        <?php for ($j = 0; $j < count($st_list); $j++) { echo "<th>".h(ses($st_list[$j], 'st_name', '', 'raw'), ENT_QUOTES)."</th>"; } ?>
        <th>장착</th>
      </tr>
    </thead>
    <tbody>
      <?php
      for ($i = 0; $row = sql_fetch_array($result); $i++) {
          $bg = 'bg'.($i % 2);
          $ch_name = get_character_name($row['ch_id']);
          $it_name = h(ses($row, 'it_name', '', 'raw'), ENT_QUOTES);
          $eq_name = h(ses($row, 'eq_name', '', 'raw'), ENT_QUOTES);
          $eq_content = h(ses($row, 'eq_content', '', 'raw'), ENT_QUOTES);
          $ug_name = h(ses($row, 'ug_name', '', 'raw'), ENT_QUOTES);
          $eq_use = ses($row, 'eq_use', '', 'raw');
          $equip_state = $eq_use ? ("장착중-".h($eq_use, ENT_QUOTES)) : "해제중";
          $it_img = h(ses($row, 'it_img', '', 'raw'), ENT_QUOTES);
          $eq_img = h(ses($row, 'eq_img', '', 'raw'), ENT_QUOTES);
      ?>
      <tr class="<?php echo $bg; ?>">
        <td><?php echo h(($ch_name ? $ch_name : ''), ENT_QUOTES); ?></td>
        <td>
          <?php if ($it_img) { ?><img src="<?php echo $it_img; ?>" alt="item"><?php } ?><br>
          <?php echo $it_name; ?>
        </td>
        <td class="txt-left">
          <?php if ($eq_img) { ?><img src="<?php echo $eq_img; ?>" alt="equip"><?php } ?>
          <?php echo $eq_name; ?><br>
          <?php echo $eq_content; ?>
        </td>
        <td><?php echo $ug_name; ?></td>
        <?php for ($j = 0; $j < count($st_list); $j++) {
            $tag = $st_list[$j]['tag'];
            $val = ses($row, $tag, 0, 'int');
            echo "<td>{$val}</td>";
        } ?>
        <td><?php echo $equip_state; ?></td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<?php
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page=');
include_once(G5_ADMIN_PATH.'/admin.tail.php');
