<div class="white"></div>

<div class="upgrade-area">

    <div class="slot-area">
        <div class="slot blink" id="slot_1" onclick="loadItemList('equip');">
            <div class="slot-img equip-img"></div>
            <span class="name"></span>
            <span class="title">장비</span>
        </div>

        <div class="slot" id="slot_3">
            <div class="slot-img equip-img"></div>
            <span class="title">결과물</span>
        </div>

        <div class="slot" id="slot_2">
            <div class="slot-img equip-img"></div>
            <span class="name"></span>
            <span class="title">재료</span>
        </div>
    </div>

    <div id="text-area">
        <?php
        // 방어 및 XSS 최소화
        $msg = isset($msg) ? (string)$msg : '';
        echo $msg; // 안내문에 HTML이 포함될 수 있으면 그대로 출력, 신뢰할 수 없는 경우 htmlspecialchars 적용 고려
        ?>
    </div>

    <div class="theme-box" id="items">
        <ul></ul>
    </div>

</div>

<input type="hidden" id="slot_1_value" value="">
<input type="hidden" id="slot_2_value" value="">

<script>
    // 방어: 서버 값 존재 확인
    var act = <?php
        $type = ses($_REQUEST, 'type', '', 'raw');
        echo json_encode($type, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    ?>;
</script>

<script src="./js/upgrade.js"></script>
<?php include_once './_tail.php'; ?>
