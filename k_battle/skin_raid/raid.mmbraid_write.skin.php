<?php
if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}

$write['wr_k_raid_log'] = ses($write, 'wr_k_raid_log', '');
$bo_1_subj = ses($board, 'bo_1_subj', '');
$bo_1 = ses($board, 'bo_1', '');
$bo_10_subj = ses($board, 'bo_10_subj', '');


if ($bo_1_subj === 'mmbraid' && $bo_1 === 'true' && empty($write['wr_k_raid_log'])):
    $turn = true;
    $skill_turn = true;

    if ($bo_10_subj === 'turnall' && ses($rm, 'tt_done', 0, 'int') === 1) {
        $turn = false;
    } elseif ($bo_10_subj === 'turnskill' && ses($rm, 'tt_done', 0, 'int') === 1) {
        $skill_turn = false;
    }
    ?>
    <input type="hidden" name="mo_rm_id" value="<?php echo ses($mo, 'rm_id', 0, 'int'); ?>">
    <input type="hidden" name="rm_id" value="<?php echo ses($rm, 'rm_id', 0, 'int'); ?>">

    <div class="inner theme-box" id="raid_action">
        <dl>
            <?php
            if (ses($rm, 'rm_id', 0, 'int') === 0) {
                echo '이 레이드에 참여하지 않았습니다.';
            } elseif (ses($mo, 'hp_now', 0, 'int') < 1) {
                echo '적을 물리쳤다!';
            } elseif (ses($rm, 'hp_now', 0, 'int') < 1) {
                echo '더 싸울 체력이 없다!';
            } elseif (
                ses($board, 'bo_3', '') === 'true' ||
                ses($board, 'bo_4', '') === 'true' ||
                ses($board, 'bo_5', '') === 'true' ||
                ses($board, 'bo_6', '') === 'true'
            ) {
                // 액션 셀렉트에 전달할 값 정리
                $rm['ch_side'] = ses($character, 'ch_side', '');

                // 각 버튼 활성/비활성 값
                $a_false = ses($board, 'bo_3', ''); // 공격
                $h_false = ses($board, 'bo_4', ''); // 치유
                $i_false = ses($board, 'bo_6', ''); // 아이템
                $s_false = ses($board, 'bo_5', ''); // 스킬

                include G5_PATH . '/k_battle/skin_default/action_select.php';
            } else {
                echo '현재 할 수 있는 행동이 없다.';
            }
            ?>
        </dl>
    </div>

    <?php if ($is_admin === 'super' && ses($board, 'bo_2', '')): ?>
        <div class="inner" id="admin_action">
            <dl>
                <dt>
                    <label for="admin_action"><i class="icon act"></i>Raid_admin</label>
                </dt>
                <dd>
                    <select name="admin_action" id="admin_action">
                        <option value="" style="color:black; background-color:white;">몬스터 행동 선택(필수)</option>
                        <option value="msg" style="color:black; background-color:white;">메시지만 출력</option>
                        <option value="allatk" style="color:black; background-color:white;">전체 공격</option>
                        <option value="randatk" style="color:black; background-color:white;">무작위 공격</option>
                        <option value="randstun" style="color:black; background-color:white;">무작위 기절</option>
                        <option value="heal" style="color:black; background-color:white;">자기 치유</option>
                    </select>
                    <input type="text" name="rand_cnt" placeholder="무작위 인원수(명)">
                    <input type="text" name="mo_atk_bonus" placeholder="몬스터 공격력 보정(%)">
                    <p><input type="text" style="width:100%;" name="raid_msg" placeholder="출력 메시지"></p>
                    <p><input type="checkbox" name="admin_turn" value="1"> 턴 변경</p>
                    <p>
                        <details>
                            <summary>도움말</summary>
                            <div class="theme-box">
                                <p>*몬스터 행동을 선택하지 않으면 관리자 기능이 작동하지 않습니다. 메시지만 출력할 때도 반드시 선택해 주세요.</p>
                                <p>*관리자 행동과 캐릭터 행동 동시 선택시 관리자 행동만 발생합니다.</p>
                                <p>*<span>공격력 보정</span>: 몬스터의 공격력이 적은 %만큼만 반영됩니다. 예) 80 입력시 80%만 반영, 120 입력시 120% 반영</p>
                                <p>*<span>기절의 효과</span>: 1턴간 캐릭터가 행동하지 못하게 함</p>
                                <p>*<span>무작위 기절/공격시</span>: 도발을 사용한 캐릭터가 있을 경우 도발 사용자가 우선적으로 선택됩니다.</p>
                                <p>*<span>턴 변경</span>: 이곳에 체크하면 기절/버프/디버프 턴수 감소, 행동제한시 행동 리셋, 출혈 및 지속회복 처리가 이루어집니다.</p>
                            </div>
                        </details>
                    </p>
                </dd>
            </dl>
        </div>
    <?php endif; ?>

    <script src="<?php echo G5_URL ?>/k_battle/js/action.js"></script>
<?php endif; ?>
