<?php
include_once('../../../../common.php');
include_once(G5_PATH.'/head.sub.php');

if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}

// board 필드 기본값
$bo_1_subj  = ses($board, 'bo_1_subj', '');
$bo_9       = ses($board, 'bo_9', '');
$bo_9_subj  = ses($board, 'bo_9_subj', '');
$bo_12      = ses($board, 'bo_12', '');
?>
<style>@import url('<?php echo G5_URL; ?>/k_battle/css/admin.css');</style>

<?php if ($is_admin === 'super' && $bo_table !== ''): ?>
<?php
// 라벨 정의
$css_list = [
    ['영역 최대너비','영역 최대높이'],
    ['영역 테두리선','영역 배경','영역 둥글기'],
    ['이미지 최대너비','이미지 최대높이'],
    ['이미지 테두리선','이미지 배경','이미지 둥글기'],
    ['이름 크기','이름 색','메시지 크기','메시지 색'],
    ['hp바 색','mp바 색','빈 바 색'],
];
$s_css_list = [
    ['시스템 상단 문자','시스템 상단 글자색','시스템 상단 글자크기','시스템 영역 테두리선','시스템 영역 배경색','시스템 영역 둥글기','시스템 영역 글자색','시스템 영역 글자크기'],
    ['시스템 로그 최대너비','시스템 로그 테두리선','시스템 로그 배경색','시스템 로그 둥글기','시스템 로그 글자색','시스템 로그 글자크기'],
    ['캐릭터 로그 최대너비','캐릭터 로그 테두리선','캐릭터 로그 배경색','캐릭터 로그 둥글기','캐릭터 로그 글자색','캐릭터 로그 글자크기'],
];

// 저장값 분해(누락시 길이 맞춰 패딩)
$raid_css    = $bo_9      !== '' ? explode('|', $bo_9)      : [];
$comment_css = $bo_9_subj !== '' ? explode('|', $bo_9_subj) : [];
$system_css  = $bo_12     !== '' ? explode('|', $bo_12)     : [];
// PHP 5.6 호환: array_pad 헬퍼
$pad = function($src, $len){ return array_pad($src, $len, ''); };

// PHP 5.6 호환: arrow function 대신 일반 함수 사용
$need_raid = 0;
foreach ($css_list as $g) { $need_raid += count($g); }
$need_system = 0;
foreach ($s_css_list as $g) { $need_system += count($g); }

$raid_css    = $pad($raid_css, $need_raid);
$comment_css = $pad($comment_css, $need_raid);
$system_css  = $pad($system_css, $need_system);

// 렌더 헬퍼
function render_group_inputs(array $labels, array $values, string $name_prefix): void {
    $h = 0;
    $outerN = count($labels);
    for ($i = 0; $i < $outerN; $i++) {
        echo "<li>";
        $innerN = count($labels[$i]);
        for ($k = 0; $k < $innerN; $k++, $h++) {
            $label = h($labels[$i][$k]);
            $val   = h(ses($values, $h, ''));
            echo "<p><span>{$label}</span><input type=\"text\" name=\"{$name_prefix}[{$h}]\" value=\"{$val}\"></p>";
        }
        echo "</li>";
    }
}
?>

<div class="btns">
    <a class="ui-btn point" href="./admin.php?bo_table=<?php echo h($bo_table); ?>">기본설정</a>
    <a class="ui-btn" href="./admin.css.php?bo_table=<?php echo h($bo_table); ?>">스타일설정</a>
    <a class="ui-btn" href="./admin.text.php?bo_table=<?php echo h($bo_table); ?>">텍스트설정</a>
    <a class="ui-btn" href="./admin.member.php?bo_table=<?php echo h($bo_table); ?>">참여자관리</a>
</div>

<form action="./admin_update.php" method="post" onsubmit="return f_submit(this);">
    <input type="hidden" name="bo_table" value="<?php echo h($bo_table); ?>">
    <table class="theme-form">
        <colgroup><col style="width:100px;"><col></colgroup>
        <thead style="background-color:#000000cc;"><tr></tr><tr></tr></thead>
        <tbody>
        <?php if ($bo_1_subj === 'mmbraid'): ?>
            <tr>
                <td>몬스터 정보</td>
                <td><ul><?php render_group_inputs($css_list, $raid_css, 'raid_css'); ?></ul></td>
            </tr>
            <tr>
                <td>캐릭터 정보</td>
                <td><ul><?php render_group_inputs($css_list, $comment_css, 'comment_css'); ?></ul></td>
            </tr>
            <tr>
                <td>전투 로그</td>
                <td><ul><?php render_group_inputs($s_css_list, $system_css, 'system_css'); ?></ul></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="2">초기설정을 먼저 진행해 주세요.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align:right;">
        <?php if ($bo_1_subj === 'mmbraid'): ?>
            <input type="submit" class="ui-btn" name="act_button" value="스타일변경" onclick="document.pressed=this.value">
            <input type="submit" class="ui-btn admin" name="act_button" value="스타일초기화" onclick="document.pressed=this.value">
        <?php endif; ?>
    </div>
</form>

<script>
function f_submit(f){
    if(document.pressed === "스타일초기화"){
        if(!confirm("초기화한 데이터는 복구할 수 없습니다.")){ return false; }
    }
    return true;
}
</script>
<?php else: ?>
<script>window.close();</script>
<?php endif; ?>
<?php include_once(G5_PATH.'/tail.sub.php'); ?>
