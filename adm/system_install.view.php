<?php if (!defined('_GNUBOARD_') || !defined('G5_SYSTEM_INSTALLER') || $is_admin !== 'super') exit; ?>

<div class="system-install">
    <p class="local_desc01 local_desc">기능별 설치 상태와 버전을 확인하고, 필요한 기능을 설치하거나 업데이트하세요.</p>
    <div class="local_ov01 local_ov"><a href="<?php echo G5_ADMIN_URL; ?>/system_install.php">상태 다시 확인</a></div>

    <?php if ($result !== null): ?>
    <section class="local_desc01 local_desc" role="status" aria-live="polite">
        <h2 class="h2_frm"><?php echo $result['errors'] ? '작업 중 확인이 필요합니다' : '작업 결과'; ?></h2>
        <?php if ($result['errors']): ?>
        <p>완료된 변경은 유지됩니다. 아래 오류와 현재 상태를 확인한 뒤 다시 실행하면 남은 단계부터 진행합니다.</p>
        <ul class="si-errors">
            <?php foreach ($result['errors'] as $message): ?><li><?php echo system_install_html($message); ?></li><?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <p>완료 <?php echo count($result['success']); ?>단계 · 건너뜀 <?php echo count($result['skipped']); ?>건 · 오류 <?php echo count($result['errors']); ?>건</p>
        <details><summary>실행 내역 보기</summary><ul>
            <?php foreach (array_merge($result['success'], $result['skipped']) as $message): ?><li><?php echo system_install_html($message); ?></li><?php endforeach; ?>
        </ul></details>
    </section>
    <?php endif; ?>

    <h2 class="h2_frm">기능별 설치 / 버전</h2>
    <div class="tbl_head01 tbl_wrap"><table class="si-modules"><caption>기능별 설치 상태와 버전</caption><colgroup><col><col class="grid_5"><col class="grid_3"></colgroup><thead><tr><th scope="col">기능 / 상세</th><th scope="col">설치 상태 / 버전</th><th scope="col">관리</th></tr></thead><tbody>
    <?php foreach ($registry as $module=>$info):
        if (!empty($info['manual'])) continue;
        $state = $states[$module];
        $current = $state['version'] === '0' ? '버전 미등록' : '현재 v'.$state['version'];
        $latest = $state['installed'] && $state['status'] === '설치됨';
        $can_apply = !$latest && !$state['manual'] && !$state['blocked'];
        $button = $state['version'] === '0' ? ($state['status'] === '일부 설치' ? '계속 설치' : '설치') : ($state['status'] === '구조 이상' ? '누락 항목 보완' : '업데이트');
        if ($state['manual']) $button = '수동 확인 필요';
        elseif ($state['blocked']) $button = '선행 기능 필요';
    ?>
        <tr class="si-module" aria-labelledby="si-<?php echo $module; ?>" data-module="<?php echo $module; ?>">
            <td class="si-name"><strong id="si-<?php echo $module; ?>"><?php echo system_install_html($info['name']); ?></strong>
                <?php if ($state['blocked']): ?><p class="si-warning">선행 작업 필요: <?php echo system_install_html(implode(', ', array_map(function($dep) use ($registry) { return $registry[$dep]['name']; }, $state['blocked']))); ?></p><?php endif; ?>
                <?php if ($state['manual']): ?><p class="si-warning"><?php echo system_install_html(implode('; ', $state['manual'])); ?></p><?php endif; ?>
                <?php foreach ($state['blocked'] as $dep): ?><a href="#si-<?php echo $dep === 'inventory_engine' ? 'inventory' : $dep; ?>"><?php echo system_install_html($registry[$dep]['name']); ?> 준비하기</a> <?php endforeach; ?>
            <?php if (!$latest): ?>
            <details class="si-details"><summary>상세 보기<span class="sound_only"> — <?php echo system_install_html($info['name']); ?></span></summary>
                <?php if ($info['deps']): ?><p>선행 기능: <?php echo system_install_html(implode(', ', array_map(function($dep) use ($registry) { return $registry[$dep]['name']; }, $info['deps']))); ?></p><?php endif; ?>
                <?php if ($state['manual']): ?><p>자동 변경할 수 없는 구조입니다. 현재 데이터와 아래 조건을 확인해 수동 조치한 뒤 다시 점검해 주세요.</p><?php endif; ?>
                <ul><?php foreach (array_merge($state['manual'], $state['missing'], $state['data_missing']) as $message): ?><li><?php echo system_install_html($message); ?></li><?php endforeach; ?></ul>
                <?php if (!$state['missing'] && !$state['manual'] && $state['version'] === '0'): ?><p>기존 구조를 확인했습니다. 설치하면 기본 설정과 버전을 등록합니다.</p><?php endif; ?>
            </details>
            <?php endif; ?>
            </td>
            <td class="si-state">
                <strong class="<?php echo $latest ? 'si-ok' : ($state['status'] === '미설치' ? '' : 'si-warning'); ?>"><?php echo system_install_html($state['status']); ?></strong>
                <p><?php echo system_install_html($current); ?><?php echo $latest ? ' · 최신' : ' / 최신 v'.system_install_html($info['version']); ?></p>
            </td>
            <td class="si-action">
                <?php if (!$latest): ?>
                <form method="post" action="<?php echo G5_ADMIN_URL; ?>/system_install.php">
                    <input type="hidden" name="token" value="<?php echo system_install_html($token); ?>">
                    <input type="hidden" name="action" value="apply">
                    <input type="hidden" name="module" value="<?php echo $module; ?>">
                    <button type="submit" class="si-button btn_submit" <?php echo $can_apply ? '' : 'disabled'; ?>><?php echo $button; ?></button>
                </form>
                <?php endif; ?>
            </td>

        </tr>
    <?php endforeach; ?>
    </tbody></table></div>

    <section class="si-inventory" id="si-inventory" aria-labelledby="inventory-title">
        <h2 class="h2_frm" id="inventory-title">Inventory 준비</h2>
        <p>현재 엔진: <strong><?php echo system_install_html($inventory['engine']); ?></strong> · 미궁에 필요한 엔진: InnoDB</p>
        <?php if ($states['inventory_engine']['installed']): ?><p class="si-ok">준비 완료</p>
        <?php else: ?>
        <p class="si-warning">데이터 테이블의 엔진을 변경하는 별도 작업입니다. 백업과 복원 가능 여부를 확인하고 인벤토리 쓰기를 중단한 뒤 실행하세요.</p>
        <details><summary>엔진 전환 상세 / 확인 후 실행</summary>
            <p>테이블을 다시 만들고 잠글 수 있어 데이터 양에 따라 시간이 걸립니다. 디스크 여유 공간도 확인하세요. 일반 기능 설치에는 포함되지 않습니다.</p>
            <?php if ($inventory['reasons']): ?><ul><?php foreach ($inventory['reasons'] as $message): ?><li><?php echo system_install_html($message); ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if (!$states['base']['installed']): ?><p>기본 확장을 먼저 설치해 주세요.</p><?php endif; ?>
            <form method="post" action="<?php echo G5_ADMIN_URL; ?>/system_install.php">
                <input type="hidden" name="token" value="<?php echo system_install_html($token); ?>">
                <input type="hidden" name="action" value="inventory_engine">
                <input type="hidden" name="module" value="inventory_engine">
                <label><input type="checkbox" name="backup_confirm" value="1" required> 인벤토리 백업과 복원 가능 여부를 확인했습니다.</label>
                <label><input type="checkbox" name="writers_confirm" value="1" required> 인벤토리를 변경하는 사이트 작업을 중단했습니다.</label>
                <button type="submit" class="si-button btn_submit" <?php echo $inventory['reasons'] || !$states['base']['installed'] ? 'disabled' : ''; ?>>확인 후 InnoDB로 전환</button>
            </form>
        </details>
        <?php endif; ?>
    </section>

    <section class="si-qa" aria-labelledby="qa-title">
        <h2 class="h2_frm" id="qa-title">전체 설치 상태</h2>
        <?php if (!$qa_missing): ?><p class="si-ok">전체 기능의 설치 구조와 버전 확인 완료</p>
        <?php else: ?><p>다음 기능의 준비가 필요합니다.</p><ul><?php foreach ($qa_missing as $message): ?><li><?php echo system_install_html($message); ?></li><?php endforeach; ?></ul><?php endif; ?>
        <p class="si-note">테이블 구조와 설치 버전을 확인한 결과입니다. 테스트용 캐릭터·던전 등 QA 데이터가 들어 있는지는 검사하지 않습니다.</p>
    </section>
</div>
<script>
document.querySelectorAll('.system-install form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        if (form.dataset.submitting) { event.preventDefault(); return; }
        form.dataset.submitting = '1';
        var button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = '처리 중…';
        form.setAttribute('aria-busy', 'true');
    });
});
</script>
