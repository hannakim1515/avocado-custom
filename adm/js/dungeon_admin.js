(() => {
  'use strict';
  const options = JSON.parse(document.getElementById('dungeon-editor-options').textContent);
  const field = (key,label,type='text',values=null,when=null) => ({key,label,type,values,when});
  const kinds = {NONE:'변경 없음',TEXT:'문구 표시',ITEM:'아이템 획득',TRAP:'함정 피해',MONSTER:'몬스터 전투',HEAL:'체력 회복'};
  const resultFields = [field('type','결과','select',kinds),field('text','결과 문구'),field('target','대상','select',{ACTOR:'행동한 참가자',ALL:'전체 참가자',RANDOM:'무작위 참가자'},r=>['TRAP','HEAL'].includes(r.type)),field('value','피해 / 회복량','number',null,r=>['TRAP','HEAL'].includes(r.type)),field('it_id','아이템','select',options.items,r=>r.type==='ITEM'),field('count','개수','number',null,r=>r.type==='ITEM')];
  const schemas = {
    abilities:{defaults:{skill_id:0,turn:1,hp_below:100,hp_above:0},fields:[field('skill_id','공용 스킬','select',options.monster_skills),field('turn','사용 턴 (1이면 매 턴)','number'),field('hp_below','HP 상한 (%) · 이하','number'),field('hp_above','HP 하한 (%) · 초과','number')]},
    components:{defaults:{type:'DOT_HP',value:5},fields:[field('type','효과 종류','select',{STAT_MODIFIER:'스탯 변경',DOT_HP:'턴마다 HP 피해',ACTION_DISABLE:'행동 불가',HEAL_MODIFIER:'받는 치유량 변경'}),field('stat_id','대상 스탯','select',options.stats,r=>r.type==='STAT_MODIFIER'),field('mode','효과 방식','select',{flat:'고정값',percent:'퍼센트',final:'최종 배율'},r=>r.type==='STAT_MODIFIER'),field('value','효과 수치','number')],help:'스탯 효과는 스킬 제작 폼과 같은 고정값·퍼센트·최종 배율 방식을 사용합니다. 행동 불가는 수치 1, 치유량 변경은 퍼센트입니다.'},
    monsters:{defaults:{enabled:true,boss:false,weight:1,monster_id:0},fields:[field('monster_id','몬스터','select',options.monsters),field('enabled','사용','checkbox'),field('boss','보스','checkbox'),field('weight','출현 가중치','number')]},
    rewards:{defaults:{it_id:0,count:1,percent:100},fields:[field('it_id','아이템','select',options.items),field('count','개수','number'),field('percent','지급 확률 (%)','number')]},
    events:{defaults:{enabled:true,room_kind:'SEARCH',weight:1,type:'TEXT',text:''},fields:[field('enabled','사용','checkbox'),field('room_kind','방 종류','select',{EMPTY:'빈 방',SEARCH:'조사',TREASURE:'보물',TRAP:'함정'}),field('weight','발생 가중치','number'),...resultFields]},
    choices:{defaults:{label:'새 선택지',type:'NONE'},fields:[field('label','선택지 이름'),...resultFields]}
  };
  const el = (tag,text,cls) => { const n=document.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;return n; };
  const clone = value => JSON.parse(JSON.stringify(value));
  function eventMonsterCatalog() {
    const form=document.getElementById('fdungeonform');if(!form)return options.monsters;
    return Object.fromEntries([...form.querySelectorAll('[data-monster-row]')].filter(row=>row.dataset.enabled==='1').map(row=>[row.dataset.monsterId,row.dataset.monsterName]));
  }
  function refreshEventMonsters() {
    const inputs=[...document.querySelectorAll('[data-event-monster]')];inputs.forEach(input=>input.refreshEventMonster());
    const invalid=inputs.filter(input=>!input.disabled&&!input.validity.valid).length;
    const warning=document.getElementById('maze-event-monster-warning');
    if(warning){warning.hidden=!invalid;warning.textContent=invalid?`출현 목록에 없는 몬스터 전투 설정이 ${invalid}개 있습니다. 아래 이벤트의 몬스터를 변경하거나 사용을 끄세요.`:'';}
  }
  function bindEventMonster(input,row,active) {
    input.dataset.eventMonster='';
    input.refreshEventMonster=()=>{
      const catalog=eventMonsterCatalog(),current=String(row.monster_id||''),signature=JSON.stringify([catalog,current]);
      if(input.dataset.poolCatalog!==signature){
        input.replaceChildren();const empty=el('option','출현 목록에서 몬스터 선택');empty.value='';input.append(empty);
        Object.entries(catalog).forEach(([id,name])=>{const choice=el('option',name);choice.value=id;input.append(choice);});
        if(current&&!Object.hasOwn(catalog,current)){const previous=el('option','출현 목록에서 제외됨 · '+(options.monsters[current]||current));previous.value=current;previous.disabled=true;input.append(previous);}
        input.value=current;input.dataset.poolCatalog=signature;
      }
      input.disabled=!active();input.required=active();
      input.setCustomValidity(active()&&!Object.hasOwn(catalog,current)?'이벤트 몬스터를 몬스터 출현 목록에서 선택하거나 이벤트 사용을 끄세요.':'');
    };
    input.refreshEventMonster();
  }
  document.getElementById('fdungeonform')?.addEventListener('dungeon-pool-change',refreshEventMonsters);
  function compactRowsUI(box,kind,rows,changed,active=()=>true) {
    box.replaceChildren();
    const schema=schemas[kind],mainKeys={abilities:['skill_id','turn','hp_below','hp_above'],monsters:['monster_id','enabled','boss','weight'],rewards:['it_id','count','percent'],events:['enabled','room_kind','weight','type','text'],choices:['label','type','text']}[kind];
    const main=schema.fields.filter(f=>mainKeys.includes(f.key)),extra=schema.fields.filter(f=>!mainKeys.includes(f.key));
    const hasDetail=kind==='events'||kind==='choices',titles={abilities:'보유 스킬',monsters:'몬스터 풀',rewards:'클리어 보상',events:'방 이벤트',choices:'조사 선택지'};
    const wrap=el('div',undefined,'tbl_head01 tbl_wrap maze-editor-list maze-editor-'+kind),table=el('table'),head=el('thead'),body=el('tbody'),heading=el('tr');
    table.append(el('caption',titles[kind]),head,body);head.append(heading);wrap.append(table);box.append(wrap);
    if(kind==='abilities') {
      const columns=el('colgroup');['grid_1','','grid_2','grid_2','grid_2','grid_3'].forEach(cls=>columns.append(el('col',undefined,cls)));
      table.insertBefore(columns,head);
    }
    const headings=['번호',...main.map(f=>f.label),...(hasDetail?['상세 / 선택지']:[]),'관리'];
    headings.forEach(label=>{const th=el('th',label);th.scope='col';heading.append(th);});
    if(!rows.length){const tr=el('tr'),td=el('td','등록된 항목이 없습니다. 항목 추가로 설정하세요.','empty_table');td.colSpan=headings.length;tr.append(td);body.append(tr);}
    rows.forEach((row,index)=>{
      const tr=el('tr'),number=el('td',String(index+1));tr.append(number);body.append(tr);
      const updates=[];
      function inputFor(f,object,cell) {
        let input;
        if(f.type==='select') {
          input=el('select');const values={...(f.values||{})};
          if(!Object.keys(values).includes(String(object[f.key]??'')) && object[f.key])values[object[f.key]]='기존 설정 (현재 목록에 없음)';
          const none=el('option',f.key==='monster_id'?'몬스터 선택':f.key==='skill_id'?'스킬 선택':'선택 / 기존 설정 유지');none.value='';input.append(none);
          Object.entries(values).forEach(([value,label])=>{const option=el('option',label);option.value=value;input.append(option);});
          input.value=['stat_id','it_id','monster_id','skill_id'].includes(f.key)&&!Number(object[f.key])?'':String(object[f.key]??'');
        } else {
          input=el('input');input.type=f.type;input.className=f.type==='checkbox'?'':'frm_input';
          if(f.type==='checkbox')input.checked=!!object[f.key]&&object[f.key]!=='0';else input.value=object[f.key]??'';
          if(f.type==='number')input.step='any';
        }
        input.setAttribute('aria-label',f.label);cell.append(input);
        if(f.key==='monster_id'&&['events','choices'].includes(kind)){
          bindEventMonster(input,object,()=>active()&&object.type==='MONSTER'&&(kind==='choices'||(!!row.enabled&&row.enabled!=='0'&&!row.choices?.length)));
          updates.push(refreshEventMonsters);
        }
        if(kind==='abilities'){
          input.dataset.abilityField=f.key;
          if(f.type==='select')input.style.width='100%';
          else if(f.type==='number'){
            input.size=8;input.style.width='7em';input.step='1';input.required=true;
            input.min=f.key==='hp_above'?'0':'1';
            if(f.key==='hp_below')input.max='100';if(f.key==='hp_above')input.max='99';
          }
        }
        input.addEventListener(f.type==='select'||f.type==='checkbox'?'change':'input',()=>{
          object[f.key]=f.type==='checkbox'?input.checked:f.type==='number'||['it_id','monster_id','skill_id'].includes(f.key)?Number(input.value):input.value;
          if(f.key==='monster_id' && input.value){delete object.data;delete object.monster;}
          updates.forEach(update=>update());refreshEventMonsters();changed();
        });
        if(f.when){const update=()=>{cell.hidden=!f.when(object);};updates.push(update);update();}
        return input;
      }
      main.forEach(f=>{const td=el('td');td.dataset.label=f.label;tr.append(td);inputFor(f,row,td);});
      if(kind==='abilities'){
        const upper=tr.querySelector('[data-ability-field="hp_below"]'),lower=tr.querySelector('[data-ability-field="hp_above"]');
        const example=el('p',undefined,'frm_info monster-hp-range');lower.parentElement.append(example);
        const updateRange=()=>{
          const high=Number(row.hp_below??100),low=Number(row.hp_above??0);
          const invalid=!Number.isInteger(high)||!Number.isInteger(low)||low<0||high>100||low>=high;
          lower.setCustomValidity(invalid?'HP 하한은 0~99%의 정수이며 상한보다 작아야 합니다.':'');
          example.textContent=invalid?'하한을 상한보다 낮게 설정하세요.':`HP ${low}% 초과~${high}% 이하에서 사용`+(low?` · ${low}% 이하가 되면 중단`:'');
        };
        updates.push(updateRange);updateRange();
      }
      if(hasDetail){
        const detailCell=el('td'),toggle=el('button',kind==='events'?`상세 / 선택지 ${Array.isArray(row.choices)?row.choices.length:0}개`:'결과 상세','btn btn_02');
        toggle.type='button';toggle.setAttribute('aria-expanded','false');detailCell.append(toggle);tr.append(detailCell);
        const detailRow=el('tr',undefined,'maze-detail-row'),detail=el('td');detail.colSpan=headings.length;detailRow.hidden=true;detailRow.append(detail);body.append(detailRow);
        toggle.addEventListener('click',()=>{detailRow.hidden=!detailRow.hidden;toggle.setAttribute('aria-expanded',String(!detailRow.hidden));});
        const fields=el('div',undefined,'maze-detail-fields');detail.append(fields);
        extra.forEach(f=>{const label=el('label',f.label),cell=el('span');label.append(cell);fields.append(label);inputFor(f,row,cell);if(f.when){const update=()=>{label.hidden=!f.when(row);};updates.push(update);update();}});
        const monsterLabel=el('label','전투 몬스터'),monsterCell=el('span');monsterLabel.append(monsterCell);fields.append(monsterLabel);
        inputFor(field('monster_id','전투 몬스터','select',options.monsters),row,monsterCell);
        const showMonster=()=>{monsterLabel.hidden=row.type!=='MONSTER';};updates.push(showMonster);showMonster();
        if(kind==='events'){
          const choiceHeading=el('h4','조사 선택지','h2_frm'),nested=el('div');detail.append(choiceHeading,nested);
          const choices=Array.isArray(row.choices)?row.choices:[];
          const choicesActive=()=>active()&&!!row.enabled&&row.enabled!=='0';
          compactRowsUI(nested,'choices',choices,()=>{row.choices=choices;toggle.textContent=`상세 / 선택지 ${choices.length}개`;changed();},choicesActive);
          const add=el('button','선택지 추가','btn btn_02');add.type='button';add.addEventListener('click',()=>{row.choices=choices;choices.push(clone(schemas.choices.defaults));compactRowsUI(nested,'choices',choices,()=>{toggle.textContent=`상세 / 선택지 ${choices.length}개`;changed();},choicesActive);toggle.textContent=`상세 / 선택지 ${choices.length}개`;changed();});detail.append(add);
        }
      }
      const removeCell=el('td'),remove=el('button','삭제','btn btn_02');remove.type='button';remove.setAttribute('aria-label',`${index+1}번째 항목 삭제`);
      if(kind==='abilities') {
        const edit=el('button','수정','btn btn_03');edit.type='button';edit.dataset.abilityEdit='';edit.setAttribute('aria-label',`${index+1}번째 스킬 수정`);
        const inputs=[...tr.querySelectorAll('input,select')];
        const summaries=inputs.map(input=>{const text=el('span');input.before(text);return text;});
        const showValues=editing=>inputs.forEach((input,i)=>{
          input.disabled=!editing;input.hidden=!editing;summaries[i].hidden=editing;
          summaries[i].textContent=input.tagName==='SELECT'?(input.selectedOptions[0]?.textContent||'미선택'):input.value;
        });showValues(false);
        edit.addEventListener('click',()=>{
          const editing=edit.getAttribute('aria-pressed')!=='true';edit.setAttribute('aria-pressed',String(editing));
          edit.textContent=editing?'수정 완료':'수정';showValues(editing);
          if(editing)inputs[0]?.focus();
        });removeCell.append(edit,document.createTextNode(' '));
      }
      remove.addEventListener('click',()=>{rows.splice(index,1);compactRowsUI(box,kind,rows,changed,active);changed();});removeCell.append(remove);tr.append(removeCell);
    });
  }
  function rowsUI(box,kind,rows,changed) {
    if(['abilities','monsters','events','rewards','choices'].includes(kind)){compactRowsUI(box,kind,rows,changed);return;}
    const schema=schemas[kind];box.replaceChildren();
    if(!rows.length)box.append(el('p','등록된 항목이 없습니다. 항목 추가로 설정하세요.','frm_info'));
    rows.forEach((row,index)=>{
      const table=el('table'),body=el('tbody'),wrap=el('div',undefined,'tbl_frm01 tbl_wrap');table.append(el('caption',`${index+1}번째 항목`),body);wrap.append(table);box.append(wrap);
      const displays=[];
      let showAbilityConditions=kind==='abilities' && (Number(row.turn||1)!==1 || Number(row.hp_below??100)!==100);
      function controls(fields,object) {
        fields.forEach(f=>{
          const tr=el('tr'),th=el('th'),td=el('td'),label=el('label',f.label);th.scope='row';th.append(label);tr.append(th,td);body.append(tr);
          let input;
          if(f.type==='select') {
            input=el('select');const values={...(f.values||{})};if(!Object.keys(values).includes(String(object[f.key]??'')) && object[f.key])values[object[f.key]]='기존 설정 (현재 목록에 없음)';
            const none=el('option',f.key==='skill_id'?'스킬 선택':f.key==='monster_id'?'몬스터 선택':'선택 / 기존 설정 유지');none.value='';input.append(none);
            Object.entries(values).forEach(([value,text])=>{const opt=el('option',text);opt.value=value;input.append(opt);});input.value=['stat_id','it_id','monster_id','skill_id'].includes(f.key)&&!Number(object[f.key])?'':String(object[f.key]??'');
          } else { input=el('input');input.type=f.type;input.className=f.type==='checkbox'?'':'frm_input';if(f.type==='checkbox')input.checked=!!object[f.key]&&object[f.key]!=='0';else input.value=object[f.key]??'';if(f.type==='number'){input.step='any';input.style.width='8em';}else if(f.type==='text'){input.size=45;} }
          input.setAttribute('aria-label',f.label);label.append(input);td.append(input); // Move input into the value cell; retain a visible row label.
          const uid=`editor-${Math.random().toString(36).slice(2)}`;input.id=uid;label.htmlFor=uid;
          input.addEventListener(f.type==='select'||f.type==='checkbox'?'change':'input',()=>{if(kind!=='monsters' && f.key.startsWith('dg_'))row.monster=object;object[f.key]=f.type==='checkbox'?input.checked:f.type==='number'||['stat_id','it_id','monster_id','skill_id'].includes(f.key)?Number(input.value):input.value;displays.forEach(update=>update());changed();});
          const update=()=>{tr.hidden=(!!f.when&&!f.when(object)) || (kind==='abilities' && f.key!=='skill_id' && !showAbilityConditions);};displays.push(update);update();
        });
      }
      if(kind==='abilities') {
        controls(schema.fields.slice(0,1),row);
        const tr=el('tr'),td=el('td');td.colSpan=2;tr.append(td);body.append(tr);
        const toggle=el('button',showAbilityConditions?'발동 조건 접기':'발동 조건 설정','btn btn_02');toggle.type='button';toggle.setAttribute('aria-expanded',String(showAbilityConditions));
        toggle.onclick=()=>{showAbilityConditions=!showAbilityConditions;toggle.textContent=showAbilityConditions?'발동 조건 접기':'발동 조건 설정';toggle.setAttribute('aria-expanded',String(showAbilityConditions));displays.forEach(update=>update());};
        td.append(toggle);controls(schema.fields.slice(1),row);
      } else controls(schema.fields,row);
      if(kind==='monsters' || row.type==='MONSTER' || ['events','choices'].includes(kind)) {
        if(kind==='monsters') {
          if(!row.monster_id)box.append(el('p','몬스터 관리에서 만든 몬스터를 선택하세요. 기존 개별 설정은 고급 설정에 보존됩니다.','frm_info'));
        } else {
          const tr=el('tr'),th=el('th','전투 몬스터'),td=el('td');th.scope='row';tr.append(th,td);body.append(tr);
          const select=el('select');select.setAttribute('aria-label','전투 몬스터');const none=el('option','몬스터 선택 / 기존 설정 유지');none.value='';select.append(none);Object.entries(options.monsters).forEach(([id,name])=>{const opt=el('option',name);opt.value=id;select.append(opt);});
          select.value=String(row.monster_id||'');select.addEventListener('change',()=>{row.monster_id=Number(select.value);if(select.value)row.monster=clone(options.profiles[select.value]);changed();});td.append(select);const update=()=>{tr.hidden=row.type!=='MONSTER';};displays.push(update);update();
        }
      }
      if(kind==='events') {
        const tr=el('tr'),th=el('th','조사 선택지'),td=el('td'),nested=el('div');th.scope='row';tr.append(th,td);body.append(tr);
        const choices=Array.isArray(row.choices)?row.choices:[];rowsUI(nested,'choices',choices,()=>{row.choices=choices;changed();});td.append(nested);
        const add=el('button','선택지 추가','btn btn_02');add.type='button';add.onclick=()=>{row.choices=choices;choices.push(clone(schemas.choices.defaults));rowsUI(box,kind,rows,changed);changed();};td.append(add);
      }
      const tr=el('tr'),td=el('td');td.colSpan=2;tr.append(td);body.append(tr);const remove=el('button','항목 삭제','btn btn_02');remove.type='button';remove.onclick=()=>{rows.splice(index,1);rowsUI(box,kind,rows,changed);changed();};td.append(remove);
    });
  }
  document.querySelectorAll('.dungeon-json-editor').forEach(editor=>{
    const raw=editor.querySelector('[data-editor-value]'),box=editor.querySelector('.dungeon-editor-rows'),kind=editor.dataset.kind;
    let rows;
    const save=()=>{raw.value=JSON.stringify(rows,null,2);raw.dispatchEvent(new Event('input',{bubbles:true}));};
    let invalid=false;
    const load=()=>{try{rows=JSON.parse(raw.value);if(!Array.isArray(rows))throw Error();rowsUI(box,kind,rows,save);}catch{
      invalid=true;box.append(el('p','저장된 설정을 읽을 수 없습니다. 이 화면에서는 저장하지 말고 설정 데이터를 확인해 주세요.','frm_info'));
      editor.closest('form')?.addEventListener('submit',event=>event.preventDefault());
    }};
    load();raw.addEventListener('change',load);
    if(invalid){editor.querySelector('[data-add]').disabled=true;return;}
    editor.querySelector('[data-add]').onclick=()=>{rows.push(clone(schemas[kind].defaults));rowsUI(box,kind,rows,save);save();};
    if(kind==='abilities') {
      const rangeError=el('p',undefined,'monster-phase-error');rangeError.hidden=true;rangeError.setAttribute('role','alert');editor.prepend(rangeError);
      editor.closest('form')?.addEventListener('submit',event=>{
        if(document.getElementById('pattern_mode')?.value!=='turns')return;
        const index=rows.findIndex(row=>{
          const high=Number(row.hp_below??100),low=Number(row.hp_above??0),turn=Number(row.turn??1);
          return !Number.isInteger(high)||!Number.isInteger(low)||high<1||high>100||low<0||low>=high||!Number.isInteger(turn)||turn<1;
        });
        if(index<0){rangeError.hidden=true;return;}
        event.preventDefault();rangeError.textContent=`${index+1}번째 스킬의 HP 구간과 사용 턴을 확인하세요. 상한 1~100%, 하한 0~99%이며 하한은 상한보다 작아야 합니다.`;rangeError.hidden=false;
        document.querySelector('[data-monster-tab="monster-actions"]')?.click();
        const row=box.querySelectorAll('tbody > tr')[index],edit=row?.querySelector('[data-ability-edit]');
        if(edit?.getAttribute('aria-pressed')!=='true')edit?.click();
        row?.querySelector('[data-ability-field="hp_above"]')?.focus();
      });
      const add=editor.querySelector('[data-add]'),picker=el('select'),label=el('label','추가할 스킬 ');
      picker.setAttribute('aria-label','추가할 스킬');
      picker.style.width='60%';
      const panel=el('div',undefined,'local_sch01 local_sch'),confirm=el('button','보유 스킬 추가','btn btn_02');
      panel.hidden=true;confirm.type='button';panel.append(label,document.createTextNode(' '),confirm);add.parentElement.after(panel);
      Object.entries(options.monster_skills||{}).forEach(([id,name])=>{const option=el('option',name);option.value=id;picker.append(option);});
      label.append(picker);add.textContent='스킬 추가';add.setAttribute('aria-expanded','false');
      add.disabled=!picker.options.length;
      if(!picker.options.length)editor.prepend(el('p','등록된 공용 스킬이 없습니다. 몬스터 스킬 목록에서 스킬을 먼저 추가하세요.','frm_info'));
      add.onclick=()=>{panel.hidden=!panel.hidden;add.setAttribute('aria-expanded',String(!panel.hidden));if(!panel.hidden)picker.focus();};
      confirm.onclick=()=>{
        const id=Number(picker.value);
        if(rows.some(row=>Number(row.skill_id||row.id)===id)){alert('이미 보유한 스킬입니다. 해당 행에서 발동 조건을 수정하세요.');return;}
        rows.push({...clone(schemas[kind].defaults),skill_id:id});rowsUI(box,kind,rows,save);save();panel.hidden=true;add.setAttribute('aria-expanded','false');
      };
    }
    if(schemas[kind].help)editor.prepend(el('p',schemas[kind].help,'frm_info'));
  });
  if(document.getElementById('maze-basic')){
    const form=document.getElementById('maze-basic').closest('form');
    const names=['min_players','rooms_min','rooms_max','branches_min','branches_max'];
    const inputs=Object.fromEntries(names.map(name=>[name,form.elements.namedItem(name)]));
    inputs.min_players.min=1;
    for(const name of ['rooms_min','rooms_max']){inputs[name].min=3;inputs[name].max=500;}
    names.forEach(name=>{inputs[name].required=true;});
    const validateLayout=()=>{
      names.forEach(name=>inputs[name].setCustomValidity(''));
      const roomsMin=Number(inputs.rooms_min.value),roomsMax=Number(inputs.rooms_max.value);
      const branchesMin=Number(inputs.branches_min.value),branchesMax=Number(inputs.branches_max.value);
      const boss=form.elements.namedItem('boss').checked;
      const limit=Math.max(0,Math.floor((roomsMin-(boss?2:1))/2));
      document.getElementById('maze-layout-hint').textContent=`방 수는 3~500개입니다. 현재 최소 방 수 ${roomsMin}개${boss?' · 보스 사용':''} 기준으로 갈림길은 최대 ${limit}개까지 설정할 수 있습니다. 이미 출발한 세션의 지도는 유지됩니다.`;
      if(roomsMax<roomsMin)inputs.rooms_max.setCustomValidity('최대 방 수는 최소 방 수 이상이어야 합니다.');
      if(branchesMin>branchesMax)inputs.branches_min.setCustomValidity('최소 갈림길 수는 최대 갈림길 수 이하여야 합니다.');
      if(branchesMax>limit)inputs.branches_max.setCustomValidity(`최소 방 수 ${roomsMin}개${boss?'와 보스 방':''} 기준으로 갈림길은 최대 ${limit}개입니다. 최소 방 수를 늘리거나 최대 갈림길 수를 줄여 주세요.`);
    };
    names.forEach(name=>inputs[name].addEventListener('input',validateLayout));
    form.elements.namedItem('boss').addEventListener('change',validateLayout);
    form.addEventListener('invalid',event=>{
      const details=event.target.closest('details');if(details)details.open=true;
    },true);
    validateLayout();
  }
})();
