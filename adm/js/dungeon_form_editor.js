(() => {
  'use strict';
  const form = document.getElementById('fdungeonform');
  if (!form) return;
  const field = name => form.elements.namedItem(name);
  const num = name => Number(field(name)?.value || 0);
  const text = (id, value) => {
    const node = document.getElementById(id);
    if (node && node.textContent !== value) node.textContent = value;
  };
  const percent = n => n.toFixed(1).replace(/\.0$/, '') + '%';
  const tabs = [...form.querySelectorAll('[data-dungeon-tab]')];
  const panelIds = tabs.map(tab => tab.dataset.dungeonTab);
  const error = document.getElementById('dungeon-form-error');
  function activate(id) {
    const selected = tabs.find(tab => tab.dataset.dungeonTab === id) || tabs[0];
    tabs.forEach(tab => {
      const on = tab === selected;
      tab.setAttribute('aria-selected', String(on));
      tab.tabIndex = on ? 0 : -1;
      document.getElementById(tab.dataset.dungeonTab).hidden = !on;
    });
  }
  tabs.forEach((tab, index) => {
    tab.id = 'dungeon-tab-' + index;
    tab.setAttribute('role', 'tab');
    const panel = document.getElementById(tab.dataset.dungeonTab);
    panel.setAttribute('role', 'tabpanel');
    panel.setAttribute('aria-labelledby', tab.id);
    tab.addEventListener('click', () => {
      activate(tab.dataset.dungeonTab);
      history.replaceState(null, '', '#' + tab.dataset.dungeonTab);
    });
    tab.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      let next = index;
      if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = tabs.length - 1;
      else next = (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
      activate(tabs[next].dataset.dungeonTab);
      tabs[next].focus();
      history.replaceState(null, '', '#' + tabs[next].dataset.dungeonTab);
    });
  });
  activate(location.hash.slice(1));

  const selectedRows = () => [...form.querySelectorAll('.editor-pool [data-monster-row]')];
  const rowField = (row, key) => row.querySelector('[data-pool-field="' + key + '"]');
  const eligibleRows = () => selectedRows().filter(row => row.dataset.enabled === '1');
  const addToggle = document.getElementById('pool-add-toggle');
  const addPanel = document.getElementById('pool-add-panel');
  const addSearch = document.getElementById('pool-add-search');
  const addChoice = document.getElementById('pool-add-choice');
  const addConfirm = document.getElementById('pool-add-confirm');
  const catalogNode = document.getElementById('pool-monster-catalog');
  const catalog = new Map((catalogNode ? JSON.parse(catalogNode.textContent) : []).map(monster => [String(monster.monster_id), monster]));
  function pickerChoices() {
    if (!addChoice) return;
    const selected = new Set(selectedRows().map(row => row.dataset.monsterId));
    const previous = addChoice.value;
    const query = addSearch.value.trim().toLocaleLowerCase();
    const candidates = [...catalog.values()].filter(monster => !selected.has(String(monster.monster_id)) && monster.name.toLocaleLowerCase().includes(query));
    const options = candidates.map(monster => {
      const option = document.createElement('option');
      option.value = String(monster.monster_id);option.disabled = Number(monster.enabled) !== 1;
      option.textContent = monster.name + (option.disabled ? ' (사용 꺼짐)' : '');return option;
    });
    addChoice.replaceChildren(...options);
    const available = options.filter(option => !option.disabled);
    addChoice.disabled = !available.length;
    addChoice.value = available.find(option => option.value === previous)?.value || available[0]?.value || '';
    addConfirm.disabled = !available.length;
    const empty = document.getElementById('pool-add-empty');
    empty.hidden = available.length > 0;
    empty.textContent = !candidates.length ? (query ? '검색 결과가 없습니다. 이름을 바꿔 검색하세요.' : '추가할 몬스터가 없습니다. 새 몬스터를 등록할 수 있습니다.') : '사용 가능한 몬스터가 없습니다. 몬스터 관리에서 사용을 켜세요.';
  }
  function togglePicker(open) {
    addPanel.hidden = !open;addToggle.setAttribute('aria-expanded', String(open));
    if (open) { pickerChoices();addSearch.focus(); } else addToggle.focus();
  }
  function addMonster() {
    const monster = catalog.get(addChoice.value);
    if (!monster || Number(monster.enabled) !== 1 || selectedRows().some(row => row.dataset.monsterId === String(monster.monster_id))) return;
    const row = document.getElementById('pool-row-template').content.firstElementChild.cloneNode(true);
    row.dataset.monsterId = String(monster.monster_id);row.dataset.monsterName = monster.name;row.dataset.enabled = '1';
    row.querySelector('[data-pool-name]').textContent = monster.name;
    row.querySelector('[data-pool-disabled]').hidden = true;
    row.querySelectorAll('[data-pool-field]').forEach(input => { input.name = 'pool[' + monster.monster_id + '][' + input.dataset.poolField + ']'; });
    rowField(row, 'weight').setAttribute('aria-label', monster.name + ' 가중치');
    rowField(row, 'min_count').setAttribute('aria-label', monster.name + ' 최소 출현 수');
    rowField(row, 'max_count').setAttribute('aria-label', monster.name + ' 최대 출현 수');
    rowField(row, 'boss').setAttribute('aria-label', monster.name + ' 보스 지정');
    const edit = row.querySelector('[data-pool-edit]');edit.href = 'monster_form.php?monster_id=' + monster.monster_id;edit.setAttribute('aria-label', monster.name + ' 수정 · 새 창');
    row.querySelector('[data-pool-remove]').setAttribute('aria-label', monster.name + ' 던전 출현에서 제외');
    document.getElementById('pool-empty').before(row);
    refresh();text('pool-add-message', monster.name + '을(를) 추가했습니다. 아래 목록에서 가중치·동시 출현 수·보스를 설정하세요.');
    if (addChoice.disabled) document.getElementById('pool-add-close').focus();else addChoice.focus();
  }
  addToggle?.addEventListener('click', () => togglePicker(addPanel.hidden));
  document.getElementById('pool-add-close')?.addEventListener('click', () => togglePicker(false));
  addSearch?.addEventListener('input', pickerChoices);
  addConfirm?.addEventListener('click', addMonster);
  addPanel?.addEventListener('keydown', event => {
    if (event.key === 'Escape') { event.preventDefault();togglePicker(false); }
    if (event.key === 'Enter' && [addSearch, addChoice].includes(event.target)) { event.preventDefault();addMonster(); }
  });
  form.querySelector('.editor-pool tbody')?.addEventListener('click', event => {
    const remove = event.target.closest('[data-pool-remove]');if (!remove) return;
    const row = remove.closest('[data-monster-row]'), name = row.dataset.monsterName;
    row.remove();refresh();text('pool-add-message', name + '을(를) 이 던전에서 제외했습니다.');addToggle.focus();
  });
  function poolPreview() {
    const eligible = eligibleRows();
    const sums = {normal: 0, boss: 0};
    eligible.forEach(row => sums[rowField(row, 'boss').checked ? 'boss' : 'normal'] += Math.max(0, Number(rowField(row, 'weight').value)));
    selectedRows().forEach(row => {
      const weight = rowField(row, 'weight'), boss = rowField(row, 'boss');
      weight.setCustomValidity(row.dataset.enabled === '0' ? '이 몬스터의 사용이 꺼져 있습니다. 몬스터 수정에서 새 전투에 사용을 켜거나 이 던전에서 제외하세요.' : '');
      const min = rowField(row, 'min_count'), max = rowField(row, 'max_count');
      const valid = Number(max.value) >= Number(min.value);
      max.setCustomValidity(valid ? '' : '최대 출현 수는 최소 출현 수 이상이어야 합니다.');
      const example = row.querySelector('.pool-count-example');
      const countText = !valid ? '최소·최대값을 확인하세요.' : min.value === max.value ? '한 전투에 항상 ' + min.value + '마리' : '한 전투에 ' + min.value + '~' + max.value + '마리 무작위';
      if (example.textContent !== countText) example.textContent = countText;
      const group = boss.checked ? 'boss' : 'normal';
      const ratio = row.querySelector('.pool-ratio');
      const label = row.dataset.enabled === '1' && sums[group] > 0
        ? (boss.checked ? '보스 선택 ' : '전투 출현 시 ') + percent(Math.max(0, Number(weight.value)) * 100 / sums[group]) : '사용 꺼짐';
      if (ratio.textContent !== label) ratio.textContent = label;
    });
    const normal = eligible.filter(row => !rowField(row, 'boss').checked);
    const bosses = eligible.filter(row => rowField(row, 'boss').checked);
    text('pool-selection-hint', normal.length + '종이 일반 전투에, ' + bosses.length + '종이 보스 후보로 선택되어 있습니다. 출현 확률과 마지막 보스 사용 여부는 탐험 설정에서 정하세요.');
    const empty = document.getElementById('pool-empty');if (empty) empty.hidden = selectedRows().length > 0;
    pickerChoices();
    form.dispatchEvent(new Event('dungeon-pool-change'));
    return {normal, bosses};
  }
  const rewardRows = () => [...form.querySelectorAll('#itemAddTable tbody tr:not(.original)')];
  const allowedItems = new Set([...document.querySelectorAll('#it_name_list option')].map(option => option.value));
  rewardRows().forEach(row => allowedItems.add(row.querySelector('[name="it_name[]"]').value));
  const rewardTable = document.getElementById('itemAddTable');
  window.fn_add_itemFrom = () => {
    const row = rewardTable.querySelector('.original').cloneNode(true);
    row.classList.remove('original');row.hidden = false;
    row.querySelectorAll('input').forEach(input => { input.disabled = false; });
    rewardTable.tBodies[0].append(row);
    refresh();
    row.querySelector('input').focus();
  };
  window.fn_del_itemFrom = button => {
    button.closest('tr').remove();
    refresh();
    form.querySelector('.btn-item-add').focus();
  };
  function rewardsPreview() {
    rewardRows().forEach(row => {
      const name = row.querySelector('[name="it_name[]"]');
      const amount = row.querySelector('[name="di_count[]"]');
      const chance = row.querySelector('[name="di_percent[]"]');
      const value = name.value.trim(), rate = Number(chance.value);
      amount.required = chance.required = value !== '';
      name.setCustomValidity(value && !allowedItems.has(value) ? '등록된 아이템 이름을 선택하세요. 입력 칸의 목록에서 선택할 수 있습니다.' : '');
      const message = !value ? '아이템을 선택하세요.' : rate === 100 ? value + ' ' + amount.value + '개 확정 지급' : rate === 0 ? '현재 확률에서는 지급하지 않습니다.' : value + ' ' + amount.value + '개 · ' + chance.value + '% 확률';
      const example = row.querySelector('.reward-example');
      if (example.textContent !== message) example.textContent = message;
    });
    document.getElementById('dungeon-reward-empty').hidden = rewardRows().length !== 0;
  }
  function settingsPreview(pool) {
    const minPlayers = field('min_players');
    if (!minPlayers) return;
    minPlayers.setCustomValidity(num('min_players') > num('dg_count') ? '최소 출발 인원은 기본 설정의 최대 참가 인원 이하여야 합니다.' : '');
    text('maze-player-example', num('min_players') + '명 이상이 준비하면 출발할 수 있고, 최대 ' + num('dg_count') + '명까지 참가합니다.');
    const encounter = field('encounter_percent');
    encounter.setCustomValidity(num('encounter_percent') > 0 && !pool.normal.length ? '몬스터 출현 탭에서 일반 몬스터를 선택하세요. 전투가 없는 탐험은 출현 확률을 0으로 설정하세요.' : '');
    text('maze-encounter-example', num('encounter_percent') === 0 ? '일반 몬스터의 무작위 전투를 사용하지 않습니다. 이벤트 전투와 보스는 각각의 설정을 따릅니다.' : '전투 추첨 대상 방에서 ' + num('encounter_percent') + '% 확률로 일반 몬스터가 출현합니다. 현재 일반 몬스터 ' + pool.normal.length + '종이 선택되어 있습니다.');
    const boss = field('boss');
    boss.setCustomValidity(boss.checked && !pool.bosses.length ? '몬스터 출현 탭에서 보스로 사용할 몬스터를 선택하세요.' : '');
    text('maze-boss-example', boss.checked ? '선택한 보스 ' + pool.bosses.length + '종 중 한 종류가 설정한 마릿수로 등장합니다. 모든 보스를 처치해야 클리어할 수 있습니다.' : '보스 전투 없이 출구에 도착하면 클리어합니다.');
    const kinds = {EMPTY:'빈 방', SEARCH:'조사', TREASURE:'보물', TRAP:'함정'};
    const total = Object.keys(kinds).reduce((sum, key) => sum + Math.max(0, num('weights[' + key + ']')), 0);
    const firstWeight = field('weights[EMPTY]');
    firstWeight.setCustomValidity(total <= 0 ? '방 가중치 중 하나 이상을 1 이상으로 설정하세요.' : '');
    text('maze-weight-example', total ? '무작위 방 종류 비율: ' + Object.entries(kinds).map(([key, label]) => label + ' ' + percent(Math.max(0, num('weights[' + key + ']')) * 100 / total)).join(' · ') : '방 가중치의 합이 0입니다. 하나 이상의 방을 사용하세요.');
  }
  function snapshot() {
    return JSON.stringify([...new FormData(form)].map(([name, value]) => [name, value instanceof File ? (value.name ? [value.name, value.size, value.lastModified] : '') : value]));
  }
  let baseline = null, dirty = false, submitting = false;
  function refresh() {
    const pool = poolPreview();
    rewardsPreview();
    settingsPreview(pool);
    const start = num('dg_per_s'), end = num('dg_per_e');
    field('dg_per_e').setCustomValidity(end < start ? '발생 구간의 끝은 시작 이상이어야 합니다.' : '');
    text('dungeon-spawn-example', '갱신 때 추첨한 0~100 값이 ' + start + '~' + end + ' 안에 있으면 등장 후보가 됩니다. 후보에 포함될 확률은 ' + percent(Math.max(0, end - start + 1) * 100 / 101) + '입니다. 실제 발생 수는 공통 설정을 따릅니다.');
    error.hidden = true;
    if (baseline !== null) {
      dirty = snapshot() !== baseline;
      text('dungeon-save-state', dirty ? '저장하지 않은 변경사항이 있습니다.' : '입력한 설정을 한 번에 저장합니다.');
      form.classList.toggle('editor-is-dirty', dirty);
    }
  }
  for (const name of ['dead_end_percent', 'escape_percent']) {
    if (field(name)) { field(name).max = '100';field(name).required = true; }
  }
  form.addEventListener('input', event => { if (event.target.name) refresh(); });
  form.addEventListener('change', event => { if (event.target.name) refresh(); });
  const observer = new MutationObserver(refresh);
  observer.observe(rewardTable.tBodies[0], {childList:true});
  let invalidTarget = null;
  form.addEventListener('invalid', event => {
    if (invalidTarget) return;
    invalidTarget = event.target;
    const panel = event.target.closest(panelIds.map(id => '#' + id).join(','));
    if (panel) activate(panel.id);
    const details = event.target.closest('details');
    if (details) details.open = true;
    const eventDetail=event.target.closest('.maze-detail-row');
    if(eventDetail){eventDetail.hidden=false;eventDetail.previousElementSibling?.querySelector('[aria-expanded]')?.setAttribute('aria-expanded','true');}
    error.textContent = '입력 확인: ' + (event.target.validationMessage || '표시된 항목을 확인하세요.');error.hidden = false;
    setTimeout(() => { invalidTarget?.focus();invalidTarget = null; }, 0);
  }, true);
  form.addEventListener('submit', event => {
    if (event.defaultPrevented) return;
    submitting = true;
    text('dungeon-save-state', '전체 설정을 저장하고 있습니다.');
    const submit = form.querySelector('[type=submit]');
    submit.disabled = true;submit.value = '저장 중…';
  });
  window.addEventListener('beforeunload', event => {
    if (!dirty || submitting) return;
    event.preventDefault();event.returnValue = '';
  });
  window.addEventListener('pageshow', event => {
    if (!event.persisted) return;
    submitting = false;
    const submit = form.querySelector('[type=submit]');submit.disabled = false;submit.value = '저장';refresh();
  });
  refresh();baseline = snapshot();
})();
