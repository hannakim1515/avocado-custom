<style>
    @import url(<?php echo G5_URL ?>/k_battle/css/asset.css);
    <?php
    // CSS 동적 주입 파일 존재 시에만 include
    $equipCss = G5_PATH . '/k_battle/css/equip_custom.php';
    if (is_file($equipCss)) {include $equipCss;}
    ?>
</style>

<?php
// 캐릭터 선택 기본값
if (!isset($ch) || !is_array($ch)) {
    $ch = $character;
}

// 소유자 여부
$asset_mine = false;
if (isset($ch['mb_id'], $member['mb_id'])) {
    $asset_mine = (string)$ch['mb_id'] === (string)$member['mb_id'];
}

// 설정 기본값
$kb_cf['limit_skill'] = ses($kb_cf, 'limit_skill', 0, 'int');
$kb_cf['limit_equip'] = ses($kb_cf, 'limit_equip', 0, 'int');
?>

<?php if (!$kb_cf['limit_skill']) { ?>
    <hr class="padding" />
    <h3>SKILL</h3>
    <div class="theme-box">
        <?php
        $skillList = G5_PATH . '/k_battle/skin_default/skill_list.php';
        if (is_file($skillList)) {
            include $skillList;
        }
        ?>
    </div>
<?php } ?>

<?php
if (!$kb_cf['limit_equip']) {

    // 소유자 본인일 때만 미장착 장비 자동 등록
    if ($asset_mine && isset($g5['inventory_table'], $g5['item_table'], $g5['k_ch_equip_table'])) {
        $ch_id = ses($character, 'ch_id', 0, 'int');

        if ($ch_id > 0) {
            $sql = "
                SELECT it.*, inven.*
                FROM {$g5['inventory_table']} AS inven
                INNER JOIN {$g5['item_table']} AS it ON inven.it_id = it.it_id
                LEFT JOIN {$g5['k_ch_equip_table']} AS eq ON eq.in_id = inven.in_id
                WHERE eq.eq_id IS NULL
                  AND inven.ch_id = {$ch_id}
                  AND it.it_type = '장비(K)'
            ";
            $select = sql_query($sql);

            if ($select) {
                while ($row = sql_fetch_array($select)) {
                    insert_k_equip($row);
                }
            }
        }
    }
    ?>
    <hr class="padding" />
    <h3>EQUIP</h3>
    <div class="theme-box">
        <?php
        $equipList = G5_PATH . '/k_battle/skin_default/equip_list.php';
        if (is_file($equipList)) {
            include $equipList;
        }
        ?>
    </div>
<?php } ?>

<script>
    var $request_url = "<?php echo urlencode(ses($_SERVER, 'REQUEST_URI', '')) ?>";
    var viewslot = '';
    var $ch_id = <?php echo ses($ch, 'ch_id', 0, 'int') ?>;

    function viewImg(url, text = '') {
        if (url == 999) {
            $('#sk_img_view').remove();
        } else if (url === 'memo') {
            $('body').append("<div id='sk_img_view' onclick='viewImg(999)'><div class='theme-box'>" + text + "</div></div>");
        } else {
            $('body').append("<div id='sk_img_view' onclick='viewImg(999)'><img src='" + url + "'></div>");
        }
    }

    function assetInfo(id, type, slot = '') {
        if (type === 'equip' && slot) {
            loadItemList(slot, false);
        }

        $.ajax({
            async: true,
            url: g5_url + "/k_battle/skin_default/" + type + "_info.php?id=" + id + "&viewslot=" + viewslot,
            beforeSend: function () {},
            success: function (data) {
                $('.asset-info#' + type + '_info').empty().append(data);
            },
            error: function () {
                $('.asset-info#' + type + '_info').empty();
            },
            complete: function () {}
        });
    }

    function loadItemList(slot, reload = true) {
        var origin_viewslot = viewslot;
        viewslot = slot;

        var formData = new FormData();
        formData.append("slot", slot);
        formData.append("ch_id", $ch_id);

        $.ajax({
            url: g5_url + '/k_battle/ajax/equip.php?load=proflist',
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            type: 'POST',
            success: function (data) {
                $("#equip_list ." + viewslot).addClass('selected');
                $("#equip_list li").not("." + viewslot).removeClass('selected');
                $("#equip_has_list").empty().append(data.list);
                if (origin_viewslot !== viewslot && reload) {
                    $("#equip_info").empty();
                }
            }
        });
    }

    function equipAction(idx, type) {
        var formData = new FormData();
        formData.append("in_id", idx);
        formData.append("url", $request_url);

        if (type === 'sell') {
            // 아이템 판매
            if (confirm('정말 판매하시겠습니까?')) {
                $.ajax({
                    url: g5_url + '/inventory/sell_item.php',
                    data: formData,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    success: function (data) {
                        if (data) {
                            var arr = data.split("||||");
                            if (arr[0] === 'LOCATIONURL') {
                                location.href = arr[1];
                            }
                        }
                    }
                });
            }
        } else if (type === 'take') {
            // 아이템 선물
            $.ajax({
                url: g5_url + '/k_battle/skin_default/send_item.php',
                data: formData,
                processData: false,
                contentType: false,
                type: 'POST',
                success: function (data) {
                    if (data) {
                        $('body').append("<div id='sk_img_view'>" + data + "</div>");
                    }
                }
            });
        }
    }
</script>
