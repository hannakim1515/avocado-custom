<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 스타일시트 로드
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<div class="frame-theme-BODY">
    <div class="inner">

        <?php if($board['bo_content_head']) { ?>
            <div class="board-notice frm-theme-NOTICE">
                <?php echo stripslashes($board['bo_content_head']); ?>
            </div>
        <?php } ?>

        <section id="bo_w">
            <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
            <input type="hidden" name="w" value="<?php echo $w ?>">
            <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
            <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
            <input type="hidden" name="sca" value="<?php echo $sca ?>">
            <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
            <input type="hidden" name="stx" value="<?php echo $stx ?>">
            <input type="hidden" name="spt" value="<?php echo $spt ?>">
            <input type="hidden" name="sst" value="<?php echo $sst ?>">
            <input type="hidden" name="sod" value="<?php echo $sod ?>">
            <input type="hidden" name="page" value="<?php echo $page ?>">
            
            <?php echo $option_hidden; ?>

            <table class="theme-form">
                <colgroup>
                    <col style="width: 120px;" />
                    <col />
                </colgroup>
                <tbody>
                
                <tr>
                    <th scope="row"><label for="wr_subject">키워드명</label></th>
                    <td>
                        <input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input required" size="50" maxlength="255">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="wr_1">에피소드</label></th>
                    <td>
                        <input type="text" name="wr_1" value="<?php echo $write['wr_1'] ?>" id="wr_1" class="frm_input" size="50" placeholder="예: Chapter 1-1">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="wr_2">스토리 힌트</label></th>
                    <td>
                        <input type="text" name="wr_2" value="<?php echo $write['wr_2'] ?>" id="wr_2" class="frm_input" size="50" placeholder="하단에 뜰 힌트 내용">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="wr_content">내용</label></th>
                    <td class="wr_content">
                        <?php echo $editor_html; ?>
                    </td>
                </tr>

                <?php if ($option) { ?>
                <tr>
                    <th scope="row">옵션</th>
                    <td><?php echo $option ?></td>
                </tr>
                <?php } ?>

                </tbody>
            </table>

            <div class="btn_confirm">
                <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn-design">작성 완료</button>
                <a href="./board.php?bo_table=<?php echo $bo_table ?>" class="btn_cancel btn-design">목록으로</a>
            </div>
            </form>

            <script>
            function fwrite_submit(f)
            {
                <?php echo $editor_js; ?>

                // 제목 검사
                if (f.wr_subject.value.length < 1) {
                    alert("제목을 입력해 주세요.");
                    f.wr_subject.focus();
                    return false;
                }

                // 버튼 중복 클릭 방지
                document.getElementById("btn_submit").disabled = "disabled";
                return true;
            }
            </script>
        </section>
    </div>
</div>