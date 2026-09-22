<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/member.css">', 0);

/** 방어 기본값 */
$article = isset($article) && is_array($article) ? $article : array();
$ch      = isset($ch) && is_array($ch) ? $ch : array();
$status  = isset($status) && is_array($status) ? $status : array();
$title   = isset($title) && is_array($title) ? $title : array();
$relation= isset($relation) && is_array($relation) ? $relation : array();
$ch_ar   = isset($ch_ar) && is_array($ch_ar) ? $ch_ar : array();
$mb      = isset($mb) && is_array($mb) ? $mb : array();
$config  = isset($config) && is_array($config) ? $config : array();

$ch_id   = isset($ch['ch_id']) ? (int)$ch['ch_id'] : 0;
?>
<div id="character_profile">

    <nav id="profile_menu">
    <?php if(!empty($article['ad_use_closet']) && !empty($article['ad_use_body'])) { ?>
        <a href="<?php echo G5_URL; ?>/member/closet.php?ch_id=<?php echo $ch_id; ?>"
           onclick="window.open(this.href, 'big_viewer', 'width=800,height=800,menubar=no,status=no,toolbar=no,location=no,scrollbars=yes,resizable=yes'); return false;"
           class="ui-btn ico point camera circle big">
            옷장보기
        </a>
    <?php } ?>
    <?php if(!empty($article['ad_use_exp'])) { ?>
        <a href="<?php echo G5_URL; ?>/member/exp.php?ch_id=<?php echo $ch_id; ?>"
           onclick="popup_window(this.href, 'exp', 'width=400, height=500'); return false;"
           class="ui-btn ico point exp circle big">
            경험치 내역 보기
        </a>
    <?php } ?>
    </nav>

    <!-- 캐릭터 비쥬얼 (이미지) 출력 영역 -->
    <div class="visual-area">
        <?php if(!empty($article['ad_use_body']) && !empty($ch['ch_body'])) { ?>
            <div id="character_body">
                <img src="<?php echo h($ch['ch_body'], ENT_QUOTES); ?>" alt="캐릭터 전신">
            </div>
        <?php } ?>
        <?php if(!empty($article['ad_use_head']) && !empty($ch['ch_head'])) { ?>
            <div id="character_head">
                <img src="<?php echo h($ch['ch_head'], ENT_QUOTES); ?>" alt="캐릭터 흉상">
            </div>
        <?php } ?>
    </div>
    <!-- //캐릭터 비쥬얼 (이미지) 출력 영역 -->

    <!-- 캐릭터 기본정보 출력 영역 -->
    <table class="theme-form">
        <colgroup>
            <col style="width:110px;">
            <col>
        </colgroup>
        <tbody>

        <?php if(!empty($article['ad_use_name'])) { ?>
            <tr>
                <th scope="row"><?php echo h(isset($article['ad_text_name'])?$article['ad_text_name']:'이름', ENT_QUOTES); ?></th>
                <td><?php echo h(isset($ch['ch_name'])?$ch['ch_name']:'', ENT_QUOTES); ?></td>
            </tr>
        <?php } ?>

        <?php if(!empty($config['cf_side_title'])) { ?>
            <tr>
                <th><?php echo h($config['cf_side_title'], ENT_QUOTES); ?></th>
                <td><?php echo h(get_side_name(isset($ch['ch_side'])?$ch['ch_side']:''), ENT_QUOTES); ?></td>
            </tr>
        <?php } ?>

        <?php if(!empty($config['cf_class_title'])) { ?>
            <tr>
                <th><?php echo h($config['cf_class_title'], ENT_QUOTES); ?></th>
                <td><?php echo h(get_class_name(isset($ch['ch_class'])?$ch['ch_class']:''), ENT_QUOTES); ?></td>
            </tr>
        <?php } ?>

        <?php if(!empty($article['ad_use_rank'])) { ?>
            <tr>
                <th scope="row"><?php echo h(isset($config['cf_rank_name'])?$config['cf_rank_name']:'랭크', ENT_QUOTES); ?></th>
                <td><?php echo h(get_rank_name(isset($ch['ch_rank'])?$ch['ch_rank']:''), ENT_QUOTES); ?></td>
            </tr>
        <?php } ?>

        <?php if(!empty($article['ad_use_exp'])) { ?>
            <tr>
                <th scope="row"><?php echo h(isset($config['cf_exp_name'])?$config['cf_exp_name']:'경험치', ENT_QUOTES); ?></th>
                <td>
                    <?php echo (int)(isset($ch['ch_exp'])?$ch['ch_exp']:0); ?>
                    <?php echo h(isset($config['cf_exp_pice'])?$config['cf_exp_pice']:'', ENT_QUOTES); ?>
                </td>
            </tr>
        <?php } ?>

        <?php for($i=0; $i < count($ch_ar); $i++) {
            $ar  = $ch_ar[$i];
            $key = isset($ar['ar_code']) ? $ar['ar_code'] : '';
            if($key === '') continue;
        ?>
            <tr>
                <th><?php echo h(isset($ar['ar_name'])?$ar['ar_name']:'', ENT_QUOTES); ?></th>
                <?php if(isset($ar['ar_type']) && ($ar['ar_type'] === 'file' || $ar['ar_type'] === 'url')) { ?>
                    <td>
                        <img src="<?php echo h(isset($ch[$key])?$ch[$key]:'', ENT_QUOTES); ?>" alt="">
                    </td>
                <?php } else { ?>
                    <td>
                        <?php
                        $val = isset($ch[$key]) ? $ch[$key] : '';
                        if(isset($ar['ar_type']) && $ar['ar_type'] === 'textarea') {
                            echo nl2br(h($val, ENT_QUOTES));
                        } else {
                            echo h($val, ENT_QUOTES);
                        }
                        if(isset($ar['ar_type']) && $ar['ar_type'] !== 'textarea' && $ar['ar_type'] !== 'select') {
                            echo h(isset($ar['ar_text'])?$ar['ar_text']:'', ENT_QUOTES);
                        }
                        ?>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <!-- // 캐릭터 기본정보 출력 영역 -->

<?php if(!empty($article['ad_use_status'])) { // 스탯 설정 ?>
    <hr class="padding">
    <h3>
        STATUS
        <span style="float:right;">
            <em class="txt-point" data-type="point_space"><?php echo (int)get_space_status($ch_id); ?></em>
            /
            <?php echo (int)(isset($ch['ch_point'])?$ch['ch_point']:0); ?>
        </span>
    </h3>
    <div class="theme-box">
        <div class="status-bar">
            <?php
            $resent_use_point = 0;

            for($i = 0; $i < count($status); $i++) {
                $name = isset($status[$i]['name']) ? $status[$i]['name'] : '';
                if($name === '') continue;

                // st_id 조회
                $st_row = sql_fetch("SELECT st_id FROM {$g5['status_config_table']} WHERE st_name = '".sql_escape_string($name)."'");
                $st_id  = isset($st_row['st_id']) ? (int)$st_row['st_id'] : 0;

                $has = isset($status[$i]['has']) ? (int)$status[$i]['has'] : (int)(isset($status[$i]['min']) ? $status[$i]['min'] : 0);
                $max = isset($status[$i]['max']) ? (int)$status[$i]['max'] : 0;
                $now = isset($status[$i]['now']) ? (int)$status[$i]['now'] : 0;

                $final = function_exists('unified_stat_value') ? unified_stat_value($ch_id, $st_id) : $has;
                $status_percent = $max ? ($final / $max * 100) : 0;
                if ($status_percent > 100) $status_percent = 100;
                $mine_percent   = $max ? ($now / $max * 100) : 0;

                $resent_use_point += $final;

                $sub_text = '';
                if($final !== $has) {
                    $sub_text .= ' (기본 '.$has.')';
                }
                if(!empty($status[$i]['drop'])) {
                    $sub_text = '('.$now.')';
                }
            ?>
                <dl>
                    <dt><?php echo h($name, ENT_QUOTES); ?></dt>
                    <dd>
                        <p>
                            <i><?php echo $final; ?><?php echo $sub_text; ?></i>
                            <span style="width: <?php echo $status_percent; ?>%;"></span>
                            <sup style="width: <?php echo $mine_percent; ?>%;"></sup>
                        </p>
                    </dd>
                </dl>
            <?php } ?>
        </div>
    </div>
<?php } ?>

<?php include G5_PATH."/k_battle/asset.inc.php"; ?>

<?php
/* A 스킬 관리 UI도 함께 노출한다. 연결된 스킬은 동기화되어 K 레이드에도 나타난다. */
if ($ch_id && function_exists('skill_setting')) {
    echo skill_setting($ch_id, $ch);
}
?>

<?php if(!empty($article['ad_use_title'])) { // 타이틀 설정 ?>
    <hr class="padding">
    <h3>TITLE</h3>
    <div class="theme-box">
        <div class="title-list">
            <?php for($i=0; $i < count($title); $i++) { ?>
                <img src="<?php echo h(isset($title[$i]['ti_img'])?$title[$i]['ti_img']:'', ENT_QUOTES); ?>" alt="">
            <?php }
            if(!isset($i) || $i === 0) {
                echo "<div class='no-data'>보유중인 타이틀이 없습니다.</div>";
            } ?>
        </div>
    </div>
<?php } ?>

<?php if(!empty($article['ad_use_inven'])) { // 인벤토리 출력 ?>
    <hr class="padding">
    <h3>
        INVENTORY
        <?php if(!empty($article['ad_use_money'])) { ?>
            <span style="float:right;">
                <em class="txt-point"><?php echo (int)(isset($mb['mb_point'])?$mb['mb_point']:0); ?></em>
                <?php echo h(isset($config['cf_money_pice'])?$config['cf_money_pice']:'', ENT_QUOTES); ?>
            </span>
        <?php } ?>
    </h3>
    <div class="theme-box">
        <?php include G5_PATH."/inventory/list.inc.php"; ?>
    </div>
<?php } ?>

<?php if(isset($ch['ch_state']) && $ch['ch_state'] === '승인') { // 관계란 출력 ?>
    <hr class="padding">
    <h3>STORY</h3>
    <div class="relation-box">
        <ul class="relation-member-list">
            <?php for($i=0; $i < count($relation); $i++) {
                $re = $relation[$i];
                if(empty($re['rm_memo'])) continue;
                $rid = isset($re['re_ch_id']) ? (int)$re['re_ch_id'] : 0;
                $re_ch = get_character($rid);
            ?>
                <li>
                    <div class="ui-thumb">
                        <a href="<?php echo G5_URL; ?>/member/viewer.php?ch_id=<?php echo isset($re_ch['ch_id'])?(int)$re_ch['ch_id']:0; ?>" target="_blank">
                            <img src="<?php echo h(isset($re_ch['ch_thumb'])?$re_ch['ch_thumb']:'', ENT_QUOTES); ?>" alt="">
                        </a>
                    </div>
                    <div class="info">
                        <div class="rm-name"><?php echo h(isset($re_ch['ch_name'])?$re_ch['ch_name']:'', ENT_QUOTES); ?></div>
                        <div class="rm-like-style">
                            <p>
                                <?php
                                $like = isset($re['rm_like']) ? (int)$re['rm_like'] : 0;
                                for($j=0; $j<5; $j++) {
                                    $class = $j < $like ? 'txt-point' : '';
                                    $style = $j < $like ? '' : 'opacity:0.2;';
                                    echo "<i class=\"{$class}\" style=\"{$style}\"></i>";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="memo theme-box"><?php echo nl2br(h($re['rm_memo'], ENT_QUOTES)); ?></div>
                    <ol>
                        <?php
                        $raw = nl2br(isset($re['rm_link'])?$re['rm_link']:'');
                        $link_list = explode('<br />', $raw);
                        for($j=0; $j < count($link_list); $j++) {
                            $r_row = trim($link_list[$j]);
                            if($r_row === '') continue;
                        ?>
                            <li><a href="<?php echo h($r_row, ENT_QUOTES); ?>" class="btn-log" target="_blank" rel="noopener"></a></li>
                        <?php } ?>
                    </ol>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

    <div class="ui-btn point small full">
        오너 : <?php echo h(isset($mb['mb_name'])?$mb['mb_name']:'', ENT_QUOTES); ?>
    </div>

    <hr class="padding">
    <hr class="padding">
</div>
