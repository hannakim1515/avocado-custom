(() => {
  'use strict';
  const boot = JSON.parse(document.getElementById('maze-boot').textContent);
  let state = boot.state, busy = false, polling = null, timer, known = '', logId = 0, chatId = 0, eventId = 0;
  let mode = 'main', selectedSkill = 0, selectedItem = 0, target = state.actor, selectedEnemy = 0;
  let pendingAction = null, pollController = null;
  let submission = null, resolveQueued = false;
  const $ = id => document.getElementById(id);
  $('maze-leave')?.addEventListener('click', async () => {
    if (!confirm('미궁 참여를 종료하고 퇴장하시겠습니까?')) return;
    if (await act('leave')) location.href = $('maze-leave').previousElementSibling.href;
  });
  const ended = () => ['CLEAR', 'FAILED', 'CLOSED'].includes(state.phase);
  const node = (tag, text, cls) => { const el = document.createElement(tag); if (text !== undefined) el.textContent = text; if (cls) el.className = cls; return el; };
  function button(text, fn, disabled = false, cls = '') { const el = node('button', text, cls); el.type = 'button'; el.disabled = disabled || busy; el.addEventListener('click', fn); return el; }
  function actionButton(text, action, fn, disabled = false, reference = 0) {
    const b = button(text, fn, disabled);
    const selected = pendingAction || state.battle?.mine;
    const pressed = selected?.action === action && Number(selected.reference_id || 0) === Number(reference) && !Number(selected.escape_vote || 0);
    b.dataset.action = action; b.setAttribute('aria-pressed', String(pressed)); return b;
  }
  function error(message) { $('maze-error').textContent = message; $('maze-error').hidden = !message; }
  function meter(label, value, max) { const el = node('div', undefined, 'maze-meter'), title = node('label', label); title.append(node('span', `${value} / ${max}`)); const bar = node('progress'); bar.max = Math.max(1, max); bar.value = value; bar.setAttribute('aria-label', label); el.append(title, bar); return el; }
  function imageURL(value) { try { const u = new URL(value, location.href); return ['http:', 'https:'].includes(u.protocol) ? u.href : ''; } catch { return ''; } }
  function token(person, enemy) {
    const dead = person.hp <= 0, el = enemy ? button('', () => { selectedEnemy = person.id; render(); }, dead) : node('div');
    el.className = `maze-token${dead ? ' dead' : ''}${person.boss ? ' boss' : ''}${enemy && selectedEnemy === person.id ? ' selected' : ''}`;
    if (enemy) { el.dataset.targetable = String(!dead); el.setAttribute('aria-pressed', String(selectedEnemy === person.id)); }
    const url = person.image && imageURL(person.image);
    if (url) { const img = node('img'); img.src = url; img.alt = ''; img.addEventListener('error', () => img.replaceWith(node('div', '◇', 'token-placeholder')), { once: true }); el.append(img); }
    else el.append(node('div', person.name.slice(0, 1), 'token-placeholder'));
    el.append(node('strong', person.name), node('small', person.boss ? 'BOSS' : (dead ? '전투불능' : '')));
    if (enemy) {
      el.append(meter('HP', person.hp, person.max_hp));
      const forecast = person.forecast;
      if (!dead && forecast?.turn && selectedEnemy === person.id) {
        el.classList.add('has-forecast');
        const cues = node('div', undefined, 'monster-forecast');
        cues.append(node('strong', `다음 행동 · ${forecast.turn}턴`));
        if (!forecast.actions.length) cues.append(node('small', forecast.idle));
        forecast.actions.forEach(action => {
          cues.append(node('small', action.cue), node('small', `${action.name} · ${action.scope}${action.condition ? ' · ' + action.condition : ''}`));
        });
        if (forecast.target) cues.append(node('small', `현재 위협 1위: ${forecast.target}`));
        cues.append(node('small', '방어·회피 스킬로 대비하세요. 도발로 단일 공격 대상을 바꿀 수 있습니다.'));
        el.append(cues);
      } else if (!dead && forecast?.actions?.length) {
        forecast.actions.forEach(action => { const cue = node('small', `${action.name} · ${action.scope}`, 'monster-cue-summary'); cue.title = action.cue; el.append(cue); });
      }
    }
    return el;
  }
  function messages(rows, container, chat) {
    const box = $(container), atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
    rows.forEach(row => {
      const id = Number(row.log_id); if (id <= (chat ? chatId : logId)) return;
      if (chat) chatId = id; else logId = id;
      const entry = node('article', undefined, 'maze-message'), header = node('header');
      header.append(node('strong', chat ? (row.name || '파티') : row.kind), node('time', row.created_at));
      entry.append(header, node('p', row.message)); box.append(entry);
      while (box.children.length > 160) box.firstChild.remove();
    });
    if (atBottom) box.scrollTop = box.scrollHeight;
  }
  function tab(chat) {
    $('maze-chat').hidden = !chat; $('maze-log').hidden = chat;
    $('tab-chat').setAttribute('aria-selected', String(chat)); $('tab-log').setAttribute('aria-selected', String(!chat));
    $('maze-chat-form').hidden = !chat || !state.active || ['CLEAR', 'FAILED', 'CLOSED'].includes(state.phase);
    const box = $(chat ? 'maze-chat' : 'maze-log'); box.scrollTop = box.scrollHeight;
  }
  function combatMessages(rows) {
    rows.forEach(row => {
      if (Number(row.event_id) <= eventId) return;
      eventId = Number(row.event_id);
      const entry = node('article', undefined, 'maze-message'), header = node('header');
      header.append(node('strong', `${row.actor} → ${row.target}`), node('time', row.created_at));
      entry.append(header, node('p', `${row.action} · ${row.result === 'HEAL' ? '+' : ''}${row.amount} ${row.result} · 턴 ${row.turn_no}`));
      $('maze-log').append(entry);
      while ($('maze-log').children.length > 160) $('maze-log').firstChild.remove();
    });
  }
  $('tab-chat').onclick = () => tab(true); $('tab-log').onclick = () => tab(false);
  $('maze-chat-form').addEventListener('submit', async e => { e.preventDefault(); const input = $('chat-message'); if (await act('chat', { message: input.value })) input.value = ''; });
  async function act(op, data = {}) {
    if (busy) return false;
    if (op === 'submit') pendingAction = data;
    busy = true; error(''); clearTimeout(timer); render();
    if (polling) { pollController?.abort(); await polling; }
    const body = new URLSearchParams({ ajax: '1', token: boot.token, ds_id: boot.ds_id, operation: op, last_log_id: logId, last_chat_id: chatId, version: state.version, room_id: state.room, battle_id: state.battle?.id || 0, turn_no: state.battle?.turn || 0, ...data });
    let success = false, updated = false;
    try {
      const endpoint = 'maze.php';
      const response = await fetch(endpoint, { method: 'POST', body, credentials: 'same-origin' });
      const result = await response.json(); if (!response.ok || result.error) throw new Error(result.error || '행동 처리에 실패했습니다.'); success = true;
      if (result.state) { applyState(result.state); updated = true; }
    } catch (e) { error(e.message); }
    finally {
      pendingAction = null; busy = false; known = '';
      if (!updated) await sync(true);
      render(); clearTimeout(timer); if (!document.hidden && !ended()) timer = setTimeout(sync, 2500);
    }
    return success;
  }
  async function sync(force = false) {
    if (polling) return polling;
    if ((!force && busy) || document.hidden || (!force && ended())) return;
    clearTimeout(timer);
    polling = (async () => {
      const controller = new AbortController(); pollController = controller; const timeout = setTimeout(() => controller.abort(), 15000);
      try {
        const query = new URLSearchParams({ ds_id: boot.ds_id, last_log_id: logId, last_chat_id: chatId, last_event_id: eventId, known_version: known });
        const response = await fetch(`maze_state.php?${query}`, { cache: 'no-store', credentials: 'same-origin', signal: controller.signal });
        const result = await response.json(); if (!response.ok || result.error) throw new Error(result.error || '연결 실패');
        applyState(result); if (result.phase) render();
        $('maze-sync').textContent = '실시간 연결';
      } catch (e) { if (e.name !== 'AbortError') { $('maze-sync').textContent = '연결 재시도 중'; if (force) error(e.message); } }
      finally { clearTimeout(timeout); polling = null; pollController = null; if (!document.hidden && !busy && !ended()) timer = setTimeout(sync, 2500); }
    })();
    return polling;
  }
  function applyState(result) {
    known = result.known_version || '';
    messages(result.chat || [], 'maze-chat', true); messages(result.logs || [], 'maze-log', false);
    combatMessages(result.events || []);
    if (result.phase) {
      if (result.phase !== state.phase) { mode = 'main'; selectedSkill = 0; }
      state = result;
    }
    $('maze-sync').textContent = '실시간 연결';
  }
  function targetSelect(includeDead = false) {
    const label = node('label', '대상'), select = node('select'); select.setAttribute('aria-label', '파티 대상');
    const eligible = state.party.filter(p => p.state === 'ACTIVE' && (p.hp > 0 || includeDead));
    if (!eligible.some(p => p.id === target)) target = eligible[0]?.id || 0;
    eligible.forEach(p => { const opt = node('option', p.name + (p.hp <= 0 ? ' · 전투불능' : '')); opt.value = p.id; opt.selected = p.id === target; select.append(opt); });
    select.onchange = () => { target = Number(select.value); }; label.append(select); return label;
  }
  function submit(action, reference = 0, extra = {}) {
    submission = act('submit', { action, reference_id: reference, target_id: target, monster_id: selectedEnemy, ...extra });
    return submission;
  }
  function allSubmitted() {
    return state.party.filter(p => p.state === 'ACTIVE' && p.hp > 0).every(p => state.battle.submitted.includes(p.id) || (p.id === state.actor && pendingAction));
  }
  async function finishTurn() {
    if (resolveQueued) return;
    const battle = state.battle.id, turn = state.battle.turn;
    resolveQueued = true; render();
    try {
      if (pendingAction && (!submission || !await submission)) return;
      if (state.phase !== 'BATTLE' || state.battle.id !== battle || state.battle.turn !== turn) return;
      await act('resolve');
    } finally { resolveQueued = false; render(); }
  }
  function renderControls(mine, living) {
    const box = $('maze-controls'); box.replaceChildren();
    if (!state.active) { box.append(node('p', '참여가 종료되었습니다. 기록에서 진행 결과를 확인할 수 있습니다.')); return; }
    if (mode === 'party') {
      box.append(node('p', '파티 관리'));
      state.party.filter(p => p.id !== state.actor && p.state === 'ACTIVE').forEach(p => {
        box.append(button(`${p.name} 퇴장 찬성`, () => act('vote', { target_id: p.id })), button(`${p.name} 찬성 취소`, () => act('vote', { target_id: p.id, cancel: 1 })));
      }); return;
    }
    if (state.phase === 'WAITING') {
      const party = state.party.filter(p => p.state === 'ACTIVE'), ready = party.length >= state.min && party.every(p => p.ready);
      box.append(node('p', `현재 ${party.length} / ${state.max}명 · 최소 ${state.min}명 · ${ready ? '출발 가능' : '파티의 준비를 기다리고 있습니다'}`), button(mine.ready ? '준비 취소' : 'READY', () => act('ready', { ready: mine.ready ? 0 : 1 })), button('출발 →', () => act('start'), !ready, 'primary')); return;
    }
    if (state.phase === 'BOSS_RESULT') { box.append(node('p', '보스를 처치했습니다. 탈출하면 클리어 보상을 정산합니다.'), button('최종 탈출 →', () => act('escape_final'), !living, 'primary')); return; }
    if (!['EXPLORE', 'BATTLE'].includes(state.phase)) { box.append(node('p', `${state.phase === 'CLEAR' ? '공략 완료' : '종료'} · 전투 기록에서 정산 결과를 확인하세요.`)); return; }
    if (!living) { box.append(node('p', '전투불능 상태입니다. 파티의 행동을 기다려 주세요.')); return; }
    if (mode === 'skills') {
      if (!state.skills.length) box.append(node('p', '출발 시 장착한 스킬이 없습니다.'));
      state.skills.forEach(skill => { const b = button('', () => { selectedSkill = skill.id; render(); }, !!skill.reason || state.phase !== 'BATTLE', 'skill-choice'); b.append(node('strong', skill.name), node('small', `${skill.resource} ${skill.cost} · ${skill.reason || (state.phase === 'BATTLE' ? '사용 가능' : '전투 중 사용')}`)); b.setAttribute('aria-pressed', String(selectedSkill === skill.id)); box.append(b); });
      const skill = state.skills.find(s => s.id === selectedSkill);
      if (skill && !skill.reason && state.phase === 'BATTLE') { if (!['적', '자신', '아군전체', '타인전체'].includes(skill.target)) box.append(targetSelect()); box.append(actionButton(`${skill.name} 제출`, 'SKILL', () => submit('SKILL', skill.id), false, skill.id)); }
    } else if (mode === 'items') {
      if (!state.items.length) { box.append(node('p', '사용할 수 있는 보관 아이템이 없습니다.')); return; }
      const label = node('label', '아이템'), select = node('select');
      if (!state.items.some(i => i.id === selectedItem)) selectedItem = state.items[0].id;
      state.items.forEach(i => { const opt = node('option', i.name); opt.value = i.id; opt.selected = selectedItem === i.id; select.append(opt); });
      select.onchange = () => { selectedItem = Number(select.value); }; label.append(select);
      box.append(label); box.append(targetSelect(true));
      box.append(actionButton('아이템 사용', 'ITEM', () => state.phase === 'BATTLE' ? submit('ITEM', selectedItem) : act('item', { inventory_id: selectedItem, target_id: target }), false, selectedItem));
    } else if (state.phase === 'BATTLE') {
      box.append(actionButton('일반 공격', 'ATTACK', () => submit('ATTACK'), !selectedEnemy), actionButton('방어', 'GUARD', () => submit('GUARD')), targetSelect(), actionButton('회복', 'HEAL', () => submit('HEAL')));
      const guard = Object.values(mine.effects || {}).flatMap(effect => effect.components || []).filter(effect => effect.type === 'GUARD').reduce((sum, effect) => sum + Number(effect.value || 0), 0);
      if (guard > 0) box.append(node('small', `방어 적용 중 · 받는 피해 ${Math.min(90, Math.max(0, guard))}% 감소 (완전 차단 아님)`));
      else if (state.battle?.mine?.action === 'GUARD') box.append(node('small', '방어 선택 · 턴 처리 시 방어 행동 코드의 비율만큼 피해를 줄입니다. 작은 피해는 반올림 후 같을 수 있습니다.'));
      box.append(actionButton('대기', 'SKIP', () => submit('SKIP')));
      if (!state.monsters.some(m => m.boss)) box.append(button('도주 찬성', () => submit('SKIP', 0, { escape_vote: 1 })));
      else box.append(node('p', '보스 전투 · 도주할 수 없습니다.'));
    } else box.append(node('p', '중앙에서 이동 방향이나 조사할 대상을 선택하세요.'));
    if (state.phase === 'BATTLE') {
      const all = allSubmitted();
      const expired = state.battle.deadline && Date.now() >= state.battle.deadline;
      const turnStatus = node('p', `${state.battle.submitted.includes(state.actor) ? '행동 제출 완료 · 처리 전까지 수정할 수 있습니다' : '행동을 선택해 주세요'}${state.battle.deadline ? ` · 제한시간 ${Math.max(0, Math.ceil((state.battle.deadline - Date.now()) / 1000))}초` : ''}`);
      turnStatus.id = 'maze-turn-status'; box.append(turnStatus);
      const resolve = button(state.battle.deadline && !expired ? '남은 시간 스킵 · 턴 완료' : '턴 완료', finishTurn);
      resolve.disabled = resolveQueued || (busy && !pendingAction) || (!all && !expired);
      resolve.id = 'maze-turn-resolve'; box.append(resolve);
    }
  }
  function render() {
    const leave = $('maze-leave');
    if (leave) {
      leave.hidden = !state.active || ended();
      leave.disabled = busy || state.phase === 'BOSS_RESULT' || (state.phase === 'BATTLE' && state.monsters.some(m => m.boss && m.hp > 0));
      leave.title = leave.disabled && !busy ? '보스 전투와 처치 직후에는 최종 탈출로 진행해 주세요.' : '참여를 종료하고 보관 아이템을 정산합니다.';
    }
    const mine = state.party.find(p => p.id === state.actor) || {}, living = mine.hp > 0;
    if (!state.monsters.some(m => m.id === selectedEnemy && m.hp > 0)) selectedEnemy = state.monsters.find(m => m.hp > 0)?.id || 0;
    $('maze-title').textContent = state.title; $('scene-title').textContent = state.title;
    $('scene-description').textContent = state.phase === 'WAITING' ? state.description : (state.prompt || '');
    for (const [id, title, value] of [['maze-phase', 'PHASE', { WAITING: '대기 중', EXPLORE: '탐색', INVESTIGATE: '조사', BATTLE: '전투', BOSS_RESULT: '보스 처치', CLEAR: '클리어', FAILED: '종료' }[state.view_phase || state.phase] || state.phase], ['maze-turn', 'TURN', state.battle?.turn || '—'], ['maze-room', 'ROOM', state.room || '—']]) { $(id).replaceChildren(node('small', title), document.createTextNode(String(value))); }
    $('party-count').textContent = `${state.party.filter(p => p.state === 'ACTIVE').length} / ${state.max}`;
    $('maze-party').replaceChildren(...state.party.map(p => { const el = node('article', undefined, `party-member${p.hp <= 0 && state.phase !== 'WAITING' ? ' dead' : ''}`); el.append(node('h3', p.name), node('small', p.state !== 'ACTIVE' ? p.state : state.phase === 'WAITING' ? (p.ready ? '✓ READY' : '준비 중') : (p.hp > 0 ? (state.battle?.submitted.includes(p.id) ? '제출 완료' : '행동 가능') : '전투불능'))); const ranking = state.monsters.find(m => m.id === selectedEnemy)?.forecast?.ranking || []; const rank = ranking.findIndex(r => r.id === p.id); if (state.phase === 'BATTLE' && rank >= 0) el.append(node('small', `위협 ${rank + 1}위 · ${Math.round(ranking[rank].value)}`, 'party-enmity')); if (p.max_hp) el.append(meter('HP', p.hp, p.max_hp)); p.resources.forEach(r => el.append(meter(r.name, r.value, r.max))); return el; }));
    const bg = state.background && imageURL(state.background); $('maze-scene').classList.toggle('has-background', !!bg); $('maze-scene').style.backgroundImage = bg ? `url(${JSON.stringify(bg)})` : '';
    $('maze-choices').replaceChildren(...(state.phase === 'EXPLORE' ? state.choices.map(c => button(c.label, () => act(c.op, c.data), !living || !state.active)) : []));
    if (!state.monsters.some(m => m.id === selectedEnemy && m.hp > 0)) selectedEnemy = state.monsters.find(m => m.hp > 0)?.id || 0;
    $('maze-enemies').replaceChildren(...(state.phase === 'BATTLE' ? state.monsters.map(m => token(m, true)) : []));
    $('maze-tokens').replaceChildren(...(state.phase !== 'WAITING' ? state.party.filter(p => p.state === 'ACTIVE').map(p => token(p, false)) : []));
    $('maze-result').textContent = state.result;
    const map = node('ol'); state.map.forEach(r => map.append(node('li', `${r.room_id === state.room ? '현재 · ' : ''}방 ${r.room_id}${r.is_boss == 1 ? ' · BOSS' : ''}`))); $('maze-map').replaceChildren(map);
    const tabs = state.phase === 'WAITING' ? [['main', '출발 준비'], ['party', '파티 관리']] : [['main', state.phase === 'BATTLE' ? '기본 행동' : '탐색'], ['skills', '스킬'], ['items', '아이템']];
    if (state.phase !== 'WAITING') tabs.push(['party', '파티 관리']);
    $('maze-nav').replaceChildren(...tabs.map(([key, label]) => button(label, () => { mode = key; render(); }, false, mode === key ? 'active' : '')));
    renderControls(mine, living); tab($('tab-chat').getAttribute('aria-selected') === 'true');
  }
  setInterval(() => {
    if (document.hidden || state.phase !== 'BATTLE') return;
    const status = $('maze-turn-status'), resolve = $('maze-turn-resolve');
    const remaining = state.battle.deadline ? Math.max(0, Math.ceil((state.battle.deadline - Date.now()) / 1000)) : null;
    const all = allSubmitted();
    if (status) status.textContent = `${state.battle.submitted.includes(state.actor) ? '행동 제출 완료 · 처리 전까지 수정할 수 있습니다' : '행동을 선택해 주세요'}${remaining === null ? '' : ` · 제한시간 ${remaining}초`}`;
    if (resolve) { resolve.disabled = resolveQueued || (busy && !pendingAction) || (!all && remaining !== 0); resolve.textContent = remaining !== null && remaining > 0 ? '남은 시간 스킵 · 턴 완료' : '턴 완료'; }
  }, 1000);
  document.addEventListener('visibilitychange', () => { clearTimeout(timer); if (!document.hidden && !ended()) sync(true); });
  messages(state.chat, 'maze-chat', true); messages(state.logs, 'maze-log', false); combatMessages(state.events || []); render(); sync();
})();
