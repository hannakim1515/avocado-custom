(() => {
  'use strict';
  const form = document.getElementById('fmonsterform');
  const mode = document.getElementById('pattern_mode');
  const value = document.getElementById('monster-phases-value');
  const list = document.getElementById('monster-phase-list');
  const addPhase = document.getElementById('monster-phase-add');
  if (!form || !mode || !value || !list || !addPhase) return;
  const skills = JSON.parse(document.getElementById('dungeon-editor-options').textContent).monster_skills || {};
  const phaseSection = document.getElementById('monster-phases');
  const oldSkills = document.getElementById('monster-abilities');
  const oldPattern = document.querySelector('.legacy-pattern-rule');
  const legacyNote = document.getElementById('monster-legacy-note');
  const tabs = [...form.querySelectorAll('[data-monster-tab]')];
  tabs.forEach((tab,index)=>{tab.id='monster-tab-'+index;tab.setAttribute('role','tab');tab.setAttribute('aria-controls',tab.dataset.monsterTab);const panel=document.getElementById(tab.dataset.monsterTab);panel.setAttribute('role','tabpanel');panel.setAttribute('aria-labelledby',tab.id);});
  const activate = id => {
    const selected = tabs.find(tab => tab.dataset.monsterTab === id) || tabs[0];
    tabs.forEach(tab => {
      const on = tab === selected;
      tab.setAttribute('aria-selected', String(on));tab.tabIndex = on ? 0 : -1;
      document.getElementById(tab.dataset.monsterTab).hidden = !on;
    });
  };
  tabs.forEach(tab => {
    tab.addEventListener('click', () => { activate(tab.dataset.monsterTab);history.replaceState(null, '', '#' + tab.dataset.monsterTab); });
    tab.addEventListener('keydown', event => {if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;event.preventDefault();let index=tabs.indexOf(tab);if(event.key==='Home')index=0;else if(event.key==='End')index=tabs.length-1;else index=(index+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;const next=tabs[index];activate(next.dataset.monsterTab);next.focus();history.replaceState(null,'','#'+next.dataset.monsterTab);});
  });
  form.addEventListener('invalid', event => {const panel=event.target.closest('#monster-basic,#monster-actions,#monster-stats,#monster-pattern');if(panel)activate(panel.id);}, true);
  const error = document.createElement('p');
  error.className = 'monster-phase-error';
  error.setAttribute('role', 'alert');
  error.tabIndex = -1;
  error.hidden = true;
  phaseSection.insertBefore(error, list);
  let phases;
  try { phases = JSON.parse(value.value); } catch (_) { phases = null; }
  if (!Array.isArray(phases) || !phases.length) phases = [{below: 100, actions: [0]}];
  const make = (tag, text, className) => {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
  };
  const button = (text, label, onClick) => {
    const node = make('button', text, 'btn btn_02');
    node.type = 'button';
    node.setAttribute('aria-label', label);
    node.addEventListener('click', onClick);
    return node;
  };
  const sync = () => {
    value.value = JSON.stringify(phases);error.hidden = true;
    const hp = Math.max(1, Number(form.elements.namedItem('hp').value) || 1);
    list.querySelectorAll('.monster-phase-example').forEach((node,index) => {
      const phase = phases[index];
      const next = phases[index + 1];
      const range = next ? `${phase.below}% 이하~${next.below}% 초과` : `${phase.below}% 이하~0% 초과`;
      const heading = node.closest('.monster-phase-card').querySelector('h3');
      heading.textContent = `${index + 1}단계 · HP ${range}`;
      const actions = phase.actions.map(id => id === 0 ? '일반 공격' : (skills[id] || '삭제된 스킬')).join(' → ');
      node.textContent = (index === 0 ? '전투 시작부터: ' : `최대 HP ${hp.toLocaleString()} 기준, HP ${Math.floor(hp * phase.below / 100).toLocaleString()} 이하에서: `) + actions + ' → 처음부터 반복' + (next ? ` · HP ${Math.floor(hp * next.below / 100).toLocaleString()} (${next.below}%) 이하가 되면 이 순서를 중단하고 ${index + 2}단계로 전환` : ' · 마지막 단계의 순서를 반복');
    });
  };
  const refreshMode = () => {
    const phased = mode.value === 'phases';
    phaseSection.hidden = !phased;
    oldSkills.hidden = mode.value !== 'turns';
    if (oldPattern) oldPattern.hidden = mode.value !== 'turns';
    if (legacyNote) legacyNote.hidden = mode.value !== 'legacy_k';
  };
  const render = () => {
    list.replaceChildren();
    phases.forEach((phase, phaseIndex) => {
      const card = make('section', undefined, 'monster-phase-card');
      const heading = make('h3', phaseIndex === 0 ? '1단계 · 전투 시작' : `${phaseIndex + 1}단계 · HP ${phase.below}% 이하`, 'h2_frm');
      card.append(heading);
      const threshold = make('div', undefined, 'monster-phase-threshold');
      if (phaseIndex) {
        const label = make('label', '이 단계가 시작되는 HP (%) ');
        const input = make('input');
        input.type = 'number';input.min = '1';input.max = '99';input.step = '1';input.className = 'frm_input';
        input.value = phase.below;input.id = `phase-below-${phaseIndex}`;label.htmlFor = input.id;
        input.addEventListener('input', () => { phase.below = Number(input.value);heading.textContent = `${phaseIndex + 1}단계 · HP ${input.value}% 이하`;sync(); });
        label.append(input);threshold.append(label);
        threshold.append(button('단계 삭제', `${phaseIndex + 1}단계 삭제`, () => {phases.splice(phaseIndex, 1);render();}));
      } else threshold.append(make('p', 'HP 100%부터 시작합니다. 아래 단계의 기준까지 이 순서를 반복합니다.', 'frm_info'));
      card.append(threshold);
      const table = make('table');
      const caption = make('caption', `${phaseIndex + 1}단계 행동 순서`);
      const head = make('thead');const trHead = make('tr');
      ['순서', '사용할 행동', '순서 변경 및 삭제'].forEach(label => {const th = make('th', label);th.scope = 'col';trHead.append(th);});
      head.append(trHead);table.append(caption, head);
      const body = make('tbody');table.append(body);
      (phase.actions || []).forEach((skillId, actionIndex) => {
        const row = make('tr');const number = make('td', String(actionIndex + 1));const action = make('td');const controls = make('td');
        number.dataset.label='순서';action.dataset.label='사용할 행동';controls.dataset.label='순서 변경 및 삭제';
        const select = make('select');select.className = 'frm_input';
        select.setAttribute('aria-label', `${phaseIndex + 1}단계 ${actionIndex + 1}번째 행동`);
        const basic = make('option', '일반 공격');basic.value = '0';select.append(basic);
        Object.entries(skills).forEach(([id, name]) => {const option = make('option', name);option.value = id;select.append(option);});
        if (skillId && !Object.prototype.hasOwnProperty.call(skills, String(skillId))) {const missing = make('option', '삭제된 스킬 · 다시 선택 필요');missing.value = String(skillId);select.append(missing);}
        select.value = String(skillId);select.addEventListener('change', () => {phase.actions[actionIndex] = Number(select.value);sync();});
        action.append(select);
        const up = button('위로', `${phaseIndex + 1}단계 ${actionIndex + 1}번째 행동 위로`, () => {if (actionIndex < 1) return;[phase.actions[actionIndex - 1], phase.actions[actionIndex]] = [phase.actions[actionIndex], phase.actions[actionIndex - 1]];render();});
        up.disabled = actionIndex === 0;
        const down = button('아래로', `${phaseIndex + 1}단계 ${actionIndex + 1}번째 행동 아래로`, () => {if (actionIndex >= phase.actions.length - 1) return;[phase.actions[actionIndex + 1], phase.actions[actionIndex]] = [phase.actions[actionIndex], phase.actions[actionIndex + 1]];render();});
        down.disabled = actionIndex === phase.actions.length - 1;
        const remove = button('삭제', `${phaseIndex + 1}단계 ${actionIndex + 1}번째 행동 삭제`, () => {if (phase.actions.length === 1) return;phase.actions.splice(actionIndex, 1);render();});
        remove.disabled = phase.actions.length === 1;
        controls.append(up, down, remove);row.append(number, action, controls);body.append(row);
      });
      const wrap = make('div', undefined, 'tbl_head01 tbl_wrap monster-phase-table');wrap.append(table);card.append(wrap);
      card.append(make('p', undefined, 'frm_info monster-phase-example'));
      card.append(button('행동 추가', `${phaseIndex + 1}단계에 행동 추가`, () => {if (phase.actions.length >= 16) return;phase.actions.push(0);render();}));
      list.append(card);
    });
    addPhase.disabled = phases.length >= 100 || Number(phases[phases.length - 1].below) <= 1;
    sync();
  };
  addPhase.addEventListener('click', () => {if (phases.length >= 100) return;const previous=Number(phases[phases.length - 1].below);phases.push({below: previous > 11 ? previous - 10 : previous - 1, actions: [0]});render();});
  mode.addEventListener('change', refreshMode);
  form.elements.namedItem('hp').addEventListener('input', sync);
  form.addEventListener('submit', event => {
    if (mode.value !== 'phases') return;
    let previous = 101;
    for (let i = 0; i < phases.length; i++) {
      const phase = phases[i];
      if (!Number.isInteger(phase.below) || phase.below < 1 || phase.below >= previous || (i === 0 && phase.below !== 100)) {
        event.preventDefault();activate('monster-actions');error.textContent = i === 0 ? '첫 단계는 HP 100%에서 시작해야 합니다.' : `${i + 1}단계 HP 기준은 이전 단계보다 낮은 1~99%여야 합니다.`;error.hidden = false;
        (list.querySelector(`#phase-below-${i}`)||error).focus();return;
      }
      if (!phase.actions.length || phase.actions.some(id => id !== 0 && !Object.prototype.hasOwnProperty.call(skills, String(id)))) {
        event.preventDefault();activate('monster-actions');error.textContent = `${i + 1}단계의 행동을 다시 선택하세요.`;error.hidden = false;
        list.querySelector('select')?.focus();return;
      }
      previous = phase.below;
    }
    sync();
  });
  render();refreshMode();activate(location.hash.slice(1));
})();
