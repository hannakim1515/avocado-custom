<?php

$sub_menu = '980210';
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
// 현재 선택된 raid_type 필터
$cur_raid_type = ses($_REQUEST, 'raid_type', 'all', 'raw');
if (!isset($raid_types[$cur_raid_type])) {
    $cur_raid_type = 'all';
}

// 동적 sub_menu 설정
$type_keys = array_keys($raid_types);
$type_index = array_search($cur_raid_type, $type_keys);
$sub_menu = "98{$type_index}211";

$type_list = array();
$sc_list   = array();
$sk_list   = array();

$ch_list = get_character_list();

/* ---------- 참조 데이터 로드 ---------- */
$type_sql = sql_query("SELECT si_id, si_1 FROM {$g5['k_skill_info_table']} WHERE si_use = 1");
for ($i = 0; $row = sql_fetch_array($type_sql); $i++) {
    $type_list[(int)$row['si_id']] = $row['si_1'];
}

$sc_sql = sql_query("SELECT * FROM {$g5['k_stat_table']} WHERE sc_category = 'stat'");
for ($i = 0; $row = sql_fetch_array($sc_sql); $i++) {
    $sc_list[$row['sc_id']] = $row;
}

$sk_sql = sql_query("SELECT * FROM {$g5['k_skill_table']}");
for ($i = 0; $row = sql_fetch_array($sk_sql); $i++) {
    $sk_list[] = $row;
}

// raid_type 필터용 스킬 목록
$sk_list_filtered = array();
if ($cur_raid_type === 'all') {
    $sk_list_filtered = $sk_list;
} else {
    foreach ($sk_list as $sk_row) {
        $sk_raid_type = ses($sk_row, 'raid_type', 'mmbraid', 'raw');
        if (strpos($sk_raid_type, $cur_raid_type) !== false) {
            $sk_list_filtered[] = $sk_row;
        }
    }
}

/* ---------- 목록용 쿼리 구성 ---------- */
/* 명시적 JOIN으로 변경해 충돌 감소 */
$sql_common = "
  FROM {$g5['k_ch_skill_table']} AS cs
  INNER JOIN {$g5['k_skill_table']} AS sk ON cs.sk_id = sk.sk_id
";

$sql_search = " WHERE (1) ";
// raid_type 필터 (LIKE 검색)
if ($cur_raid_type !== 'all') {
    $sql_search .= " AND sk.raid_type LIKE '%".sql_escape_string($cur_raid_type)."%' ";
}

/* 전체 카운트 */
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}");
$total_count = ses($row, 'cnt', 0, 'int');

/* 페이징 */
$rows = 50;
$total_page  = max(1, (int)ceil($total_count / $rows));
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;

/* 정렬 기본값 */
$order_by = " ORDER BY cs.cs_id DESC ";

/* 페이지 데이터 */
$sql = "SELECT cs.*, sk.* {$sql_common} {$sql_search} {$order_by} LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

/* ---------- 화면 ---------- */
$g5['title'] = '보유 스킬 관리 (' . $raid_types[$cur_raid_type] . ')';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
?>

<style>
.raid_type_tabs { margin-bottom: 15px; }
.raid_type_tabs a { display: inline-block; padding: 8px 20px; background: #f5f5f5; border: 1px solid #ddd; margin-right: 5px; text-decoration: none; color: #333; }
.raid_type_tabs a.active { background: #2196F3; color: #fff; border-color: #2196F3; }
</style>

<!-- 레이드 타입 탭 -->
<div class="raid_type_tabs">
    <?php foreach ($raid_types as $rt_key => $rt_name): ?>
    <a href="?raid_type=<?php echo urlencode($rt_key); ?>" class="<?php echo $cur_raid_type === $rt_key ? 'active' : ''; ?>"><?php echo h($rt_name); ?></a>
    <?php endforeach; ?>
</div>

<h2 class="h2_frm">스킬 지급 (<?php echo h($raid_types[$cur_raid_type]); ?>)</h2>
<form action="./980_k_ch_skill_update.php" method="post" autocomplete="off">
  <input type="hidden" name="type" value="insert">
  <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type); ?>">
  <div class="tbl_head01 tbl_wrap">
    <table>
      <colgroup>
        <col style="width:100px;">
        <col style="width:100px;">
        <col>
      </colgroup>
      <thead>
        <tr>
          <th>캐릭터</th>
          <th>스킬</th>
          <th>정보</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <select name="ch_id">
              <option value="">캐릭터 선택</option>
              <?php for ($i = 0; $i < count($ch_list); $i++) { ?>
                <option value="<?php echo (int)$ch_list[$i]['ch_id']; ?>">
                  <?php echo h(ses($ch_list[$i], 'ch_name', ''), ENT_QUOTES); ?>
                </option>
              <?php } ?>
            </select>
          </td>
          <td>
            <select name="sk_id" onchange="sk_info(this.value)">
              <option value="">스킬 선택</option>
              <?php for ($i = 0; $i < count($sk_list_filtered); $i++) { ?>
                <option value="<?php echo (int)$sk_list_filtered[$i]['sk_id']; ?>">
                  <?php echo h(ses($sk_list_filtered[$i], 'sk_name', ''), ENT_QUOTES); ?>
                </option>
              <?php } ?>
            </select>
          </td>
          <td class="txt-left" id="sk_info"></td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="지급" onclick="document.pressed=this.value">
  </div>
</form>

<h2 class="h2_frm">보유스킬관리 (<?php echo h($raid_types[$cur_raid_type]); ?>)</h2>
<form action="./980_k_ch_skill_update.php" onsubmit="return f_submit(this);" method="post" autocomplete="off">
  <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type); ?>">
  <input type="hidden" name="sst" value="<?php echo h($sst, ENT_QUOTES); ?>">
  <input type="hidden" name="sod" value="<?php echo h($sod, ENT_QUOTES); ?>">
  <input type="hidden" name="sfl" value="<?php echo h($sfl, ENT_QUOTES); ?>">
  <input type="hidden" name="stx" value="<?php echo h($stx, ENT_QUOTES); ?>">
  <input type="hidden" name="page" value="<?php echo (int)$page; ?>">

  <div class="tbl_head01 tbl_wrap">
    <table>
      <colgroup>
        <col style="width:50px;">
        <col style="width:100px;">
        <col>
        <col>
        <col>
        <col style="width:90px;">
      </colgroup>
      <thead>
        <tr>
          <th scope="col">
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
          </th>
          <th>캐릭터</th>
          <th>스킬종류</th>
          <th>스킬정보</th>
          <th>커스텀</th>
          <th>장착</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $type='cs';
        for ($i = 0; $row = sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i % 2);
            $ch    = get_character($row['ch_id']);
            $ch_nm = h(ses($ch, 'ch_name', ''), ENT_QUOTES);
            $sk_nm = h(ses($row, 'sk_name', ''), ENT_QUOTES);
            $cs_id = ses($row, 'cs_id', 0, 'int');
            $sk_id = $cs_id;
            $cs_img = h(ses($row, 'cs_img', ''), ENT_QUOTES);
            $cs_icon = h(ses($row, 'cs_icon', ''), ENT_QUOTES);
            $cs_name = h(ses($row, 'cs_name', ''), ENT_QUOTES);
            $cs_content = h(ses($row, 'cs_content', ''), ENT_QUOTES);
            $sk_use = ses($row, 'sk_use', '', 'raw');
            $cs_use = ses($row, 'cs_use', 0, 'int');
            $sk = $row; // 변수 스코프 명시적 설정
        ?>
        <tr class="<?php echo $bg; ?>">
          <td>
            <input type="hidden" name="cs_id[<?php echo $i; ?>]" value="<?php echo $cs_id; ?>" id="sc_id_<?php echo $i; ?>">
            <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
          </td>
          <td><?php echo $ch_nm; ?></td>
          <td><?php echo $sk_nm; ?></td>
          <td class="txt-left">
            <?php include('./980_k_skill_info.php'); ?>
          </td>
          <td class="txt-left">
            <?php if (strpos($sk_use, 'custom') === false) { ?>
              커스텀 불가능한 스킬입니다.
            <?php } else { ?>
              <?php if ($cs_img) { ?><p><img src="<?php echo $cs_img; ?>" alt=""></p><?php } ?>
              <p>
                <?php if ($cs_icon) { ?><img src="<?php echo $cs_icon; ?>" alt=""><?php } ?>
                <?php echo $cs_name; ?>
              </p>
              <p><?php echo $cs_content; ?></p>
            <?php } ?>
          </td>
          <td><?php echo $cs_use === 1 ? '장착중' : '해제중'; ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

  <div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
  </div>
</form>

<?php
$paging_url = '?'.$qstr;
if ($cur_raid_type !== 'all') {
    $paging_url .= '&amp;raid_type=' . urlencode($cur_raid_type);
}
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $paging_url.'&amp;page=');
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>

<script>
function sk_info(val){
  var link = "./980_k_skill_info.php?sk_id=" + encodeURIComponent(val);
  $.ajax({
    async: true,
    url: link,
    success: function(data){ $('#sk_info').empty().append(data); },
    error: function(){ $('#sk_info').empty(); }
  });
}

function f_submit(f){
  if (!is_checked("chk[]")) {
    alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
    return false;
  }
  if (document.pressed === "선택삭제") {
    if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) return false;
  }
  return true;
}
</script>
