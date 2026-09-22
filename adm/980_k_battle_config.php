<?php
$sub_menu = '980010';
include_once('./_common.php');

// 소속 목록
$ch_si = array();
if (!empty($config['cf_side_title'])) {
    $side_result = sql_query("
        SELECT si_id, si_name
        FROM {$g5['side_table']}
        WHERE si_auth <= '".(int)$member['mb_level']."'
        ORDER BY si_id ASC
    ");

    for ($i = 0; ($row = sql_fetch_array($side_result)); $i++) {
        $ch_si[$i] = array(
            'id'   => (int)$row['si_id'],
            'name' => $row['si_name'],
        );
    }
}

// 기본값 방어
$kb_cf['limit_atk']      = ses($kb_cf, 'limit_atk', 0, 'int');
$kb_cf['limit_heal']     = ses($kb_cf, 'limit_heal', 0, 'int');
$kb_cf['limit_skill']    = ses($kb_cf, 'limit_skill', 0, 'int');
$kb_cf['limit_item']     = ses($kb_cf, 'limit_item', 0, 'int');
$kb_cf['limit_equip']    = ses($kb_cf, 'limit_equip', 0, 'int');
$kb_cf['skill_max']      = ses($kb_cf, 'skill_max', 5, 'int');
$kb_cf['skill_img']      = ses($kb_cf, 'skill_img', 0, 'int');
$kb_cf['equip_per_hide'] = ses($kb_cf, 'equip_per_hide', 0, 'int');
$kb_cf['upgrade_limit']  = ses($kb_cf, 'upgrade_limit', 0, 'int');
$kb_cf['equip_type']     = ses($kb_cf, 'equip_type', '', 'raw');
$kb_cf['equip_max']      = ses($kb_cf, 'equip_max', '5', 'raw');

// Pusher 설정
$kb_cf['pusher_app_id']  = ses($kb_cf, 'pusher_app_id', '', 'raw');
$kb_cf['pusher_key']     = ses($kb_cf, 'pusher_key', '', 'raw');
$kb_cf['pusher_secret']  = ses($kb_cf, 'pusher_secret', '', 'raw');
$kb_cf['pusher_cluster'] = ses($kb_cf, 'pusher_cluster', 'ap3', 'raw');

$equip_type_arr = $kb_cf['equip_type'] !== '' ? explode('|', $kb_cf['equip_type']) : array();
$slot_arr       = $kb_cf['equip_max'] !== '' ? explode('|', $kb_cf['equip_max'])   : array();

// 슬롯 최소 1개 확보
if (!isset($slot_arr[0])) {
    $slot_arr[0] = 0;
}

$g5['title'] = '플러그인 설정';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
?>

<section id="anc_002">
    <h2 class="h2_frm">플러그인 설정</h2>
    <?php if (isset($pg_anchor)) echo $pg_anchor; ?>
    <form method="post" action="./980_k_battle_config_update.php" autocomplete="off">
        <input type="hidden" name="token" value="<?php echo $token; ?>">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col style="width:120px;">
                    <col>
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row">일반공격 제한</th>
                    <td>
                        <select name="limit_atk">
                            <option value="">제한 없음</option>
                            <option value="999"<?php echo $kb_cf['limit_atk'] === 999 ? ' selected' : ''; ?>>일반공격 사용 안함</option>
                            <?php if (!empty($config['cf_side_title'])) { ?>
                                <?php foreach ($ch_si as $si) { ?>
                                    <option value="<?php echo $si['id']; ?>"
                                        <?php echo $kb_cf['limit_atk'] === $si['id'] ? ' selected' : ''; ?>>
                                        <?php echo h($config['cf_side_title'], ENT_QUOTES); ?> 제한:
                                        <?php echo h($si['name'], ENT_QUOTES); ?>만 사용
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">일반치유 제한</th>
                    <td>
                        <select name="limit_heal">
                            <option value="">제한 없음</option>
                            <option value="999"<?php echo $kb_cf['limit_heal'] === 999 ? ' selected' : ''; ?>>일반치유 사용 안함</option>
                            <?php if (!empty($config['cf_side_title'])) { ?>
                                <?php foreach ($ch_si as $si) { ?>
                                    <option value="<?php echo $si['id']; ?>"
                                        <?php echo $kb_cf['limit_heal'] === $si['id'] ? ' selected' : ''; ?>>
                                        <?php echo h($config['cf_side_title'], ENT_QUOTES); ?> 제한:
                                        <?php echo h($si['name'], ENT_QUOTES); ?>만 사용
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">스킬 제한</th>
                    <td>
                        <select name="limit_skill">
                            <option value="">제한 없음</option>
                            <option value="999"<?php echo $kb_cf['limit_skill'] === 999 ? ' selected' : ''; ?>>스킬 사용 안함</option>
                        </select>
                        <br>
                        최대
                        <input type="number" name="skill_max"
                               value="<?php echo (int)$kb_cf['skill_max']; ?>"
                               class="frm_input" style="width:60px;"> 개
                        <br>
                        스킬컷 사용
                        <label>
                            <input type="checkbox" name="skill_img" value="1"
                                <?php echo $kb_cf['skill_img'] ? ' checked' : ''; ?>>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">아이템 제한</th>
                    <td>
                        <select name="limit_item">
                            <option value="">제한 없음</option>
                            <option value="999"<?php echo $kb_cf['limit_item'] === 999 ? ' selected' : ''; ?>>아이템 사용 안함</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">장비</th>
                    <td>
                        <select name="limit_equip">
                            <option value="">사용제한 없음</option>
                            <option value="999"<?php echo $kb_cf['limit_equip'] === 999 ? ' selected' : ''; ?>>장비 사용 안함</option>
                        </select>

                        <select name="upgrade_limit">
                            <option value="">강화제한 없음</option>
                            <?php
                            $ug_list = sql_query("SELECT ug_id, ug_name FROM {$g5['k_upgrade_table']}");
                            while ($row = sql_fetch_array($ug_list)) {
                                $ug_id = (int)$row['ug_id'];
                                $sel  = $kb_cf['upgrade_limit'] === $ug_id ? ' selected' : '';
                                echo '<option value="'.$ug_id.'"'.$sel.'>'.h($row['ug_name'], ENT_QUOTES).'까지 허용</option>';
                            }
                            ?>
                        </select>

                        <br>
                        <label>
                            <input type="checkbox" name="equip_per_hide" value="1"
                                <?php echo $kb_cf['equip_per_hide'] ? ' checked' : ''; ?>>
                            강화 확률 숨기기
                        </label>

                        <br>
                        장비 종류
                        <input type="text" name="equip_type"
                               value="<?php echo h($kb_cf['equip_type'], ENT_QUOTES); ?>"
                               style="width:100%;" placeholder="|로 구분">

                        <?php
                        // 슬롯 출력
                        ?>
                        <br>장비 슬롯
                        <p>
                            공용
                            <input type="number" name="slot[0]"
                                   value="<?php echo ses($slot_arr, 0, 0, 'int'); ?>"
                                   class="frm_input" style="width:60px;"> 개
                        </p>

                        <?php
                        if (!empty($equip_type_arr)) {
                            foreach ($equip_type_arr as $idx => $type) {
                                $type = trim($type);
                                if ($type === '') {
                                    continue;
                                }
                                $slot_idx = $idx + 1;
                                $slot_val = ses($slot_arr, $slot_idx, 0, 'int');
                                ?>
                                <p>
                                    <?php echo h($type, ENT_QUOTES); ?>
                                    <input type="number" name="slot[<?php echo $slot_idx; ?>]"
                                           value="<?php echo $slot_val; ?>"
                                           class="frm_input" style="width:60px;"> 개
                                </p>
                                <?php
                            }
                        }
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
<!--
        <h2 class="h2_frm" style="margin-top:20px;">Pusher 설정 (실시간 레이드용)</h2>
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col style="width:120px;">
                    <col>
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row">App ID</th>
                    <td>
                        <input type="text" name="pusher_app_id"
                               value="<?php echo h($kb_cf['pusher_app_id'], ENT_QUOTES); ?>"
                               class="frm_input" style="width:200px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Key</th>
                    <td>
                        <input type="text" name="pusher_key"
                               value="<?php echo h($kb_cf['pusher_key'], ENT_QUOTES); ?>"
                               class="frm_input" style="width:200px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Secret</th>
                    <td>
                        <input type="password" name="pusher_secret"
                               value="<?php echo h($kb_cf['pusher_secret'], ENT_QUOTES); ?>"
                               class="frm_input" style="width:200px;">
                        <small>(저장 후 표시되지 않음)</small>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Cluster</th>
                    <td>
                        <select name="pusher_cluster">
                            <option value="ap1"<?php echo $kb_cf['pusher_cluster'] === 'ap1' ? ' selected' : ''; ?>>ap1 (Asia Pacific - Mumbai)</option>
                            <option value="ap2"<?php echo $kb_cf['pusher_cluster'] === 'ap2' ? ' selected' : ''; ?>>ap2 (Asia Pacific - Singapore)</option>
                            <option value="ap3"<?php echo $kb_cf['pusher_cluster'] === 'ap3' ? ' selected' : ''; ?>>ap3 (Asia Pacific - Tokyo)</option>
                            <option value="ap4"<?php echo $kb_cf['pusher_cluster'] === 'ap4' ? ' selected' : ''; ?>>ap4 (Asia Pacific - Sydney)</option>
                            <option value="mt1"<?php echo $kb_cf['pusher_cluster'] === 'mt1' ? ' selected' : ''; ?>>mt1 (US East - N. Virginia)</option>
                            <option value="us2"<?php echo $kb_cf['pusher_cluster'] === 'us2' ? ' selected' : ''; ?>>us2 (US East - Ohio)</option>
                            <option value="us3"<?php echo $kb_cf['pusher_cluster'] === 'us3' ? ' selected' : ''; ?>>us3 (US West - Oregon)</option>
                            <option value="eu"<?php echo $kb_cf['pusher_cluster'] === 'eu' ? ' selected' : ''; ?>>eu (Europe - Ireland)</option>
                        </select>
                        <small>한국 사용자는 ap3 (Tokyo) 권장</small>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <small>
                            <strong>사용 방법:</strong> 
                            <a href="https://pusher.com" target="_blank">pusher.com</a>에서 무료 계정 생성 후 Channels 앱을 만들어 위 정보를 입력하세요.<br>
                            레이드별로 ra_system을 'pusher'로 설정하면 해당 레이드에서 실시간 푸시가 활성화됩니다.
                        </small>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
-->
        <div class="btn_confirm01 btn_confirm">
            <input type="submit" name="act_button" value="등록">
        </div>
    </form>
</section>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
