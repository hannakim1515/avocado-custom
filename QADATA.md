# QADATA — 아보카도 통합 QA 데이터 SQL 작성 지침

## 0. 이 문서의 용도

이 문서를 LLM에게 그대로 제공하고 다음과 같이 요청한다.

> 이 QADATA 지침과 현재 운영 DB의 사전 점검 결과를 기준으로, 한 번에 실행할 수 있는 QA 데이터 seed SQL을 작성해 줘. 실제 테이블 prefix를 적용하고, 모든 참조 ID는 실행 중 조회하거나 캡처하며, 검증 SQL과 안전한 재실행 규칙도 포함해 줘.

대상은 **이 저장소를 새로 설치한 뒤 최고관리자 계정만 존재하는 사이트**다. 생성할 데이터는 캐릭터, 기본 스탯, 전투 연동 코드, 스킬, 아이템과 인벤토리, 장비, 맵과 아르바이트, NPC 비주얼 이벤트와 조건 분기, K 실시간 레이드, 몬스터, 기존 던전 정의, 신규 미궁 설정을 서로 연결한 QA 세트다.

이 문서는 데이터 명세다. 공식 설치 SQL이나 migration을 대체하지 않는다. 특히 미궁의 `inventory` InnoDB 전환과 신규 미궁 테이블 생성은 이 문서에서 자동 실행하지 않는다.

---

## 1. LLM이 반드시 지켜야 하는 출력 계약

### 1.1 결과물

하나의 UTF-8 SQL 스크립트를 작성한다. SQL 안의 주석은 한국어로 작성하고 아래 순서를 지킨다.

1. 실행 전 변수와 전제 조건
2. 읽기 전용 schema/engine 사전 점검
3. 관리자 계정 확인
4. 설정값 갱신
5. 정의 데이터 생성
6. 관계 데이터 생성
7. 즉시 테스트 가능한 인스턴스 생성
8. 검증 쿼리
9. 수동 후속 작업 안내
10. QA 데이터 정리 순서 안내

### 1.2 실제 prefix 사용

- 기본 설치 예시는 `avo_`지만 실제 `G5_TABLE_PREFIX`가 다를 수 있다.
- SQL을 작성할 때 사용자가 알려 준 실제 prefix를 모든 테이블명에 직접 반영한다.
- MySQL 사용자 변수로 table identifier를 대체하려 하지 않는다. `@prefix`는 `FROM @prefix_character`처럼 사용할 수 없다.
- prefix를 알 수 없으면 SQL을 추측해서 완성하지 말고, `data/dbconfig.php`의 비밀번호를 요청하지도 않는다. 사용자에게 `G5_TABLE_PREFIX` 값 또는 `SHOW TABLES` 결과만 요청한다.

### 1.3 데이터 표식

- 사람이 보는 이름은 `[QA]` 또는 `[QA NPC]`로 시작한다.
- 내부 식별 문자열은 `QADATA`를 사용한다.
- 사용할 수 있는 메모/여분 필드는 다음처럼 표시한다.
  - 아이템: `it_5='QADATA:<ROLE>'`
  - 인벤토리: `in_5='QADATA:SEED'`
  - 스킬: `sk_cate='QADATA'`
  - K 스탯: `sc_10='QADATA'`
  - 맵/던전 설명: 본문 첫 부분에 `[QADATA]`
- 기존 운영 데이터에 같은 이름이 있더라도 `[QA]` 표식이 없는 행은 수정하거나 삭제하지 않는다.

### 1.4 ID 처리

- AUTO_INCREMENT 값을 하드코딩하지 않는다.
- INSERT 직후 `LAST_INSERT_ID()`를 변수에 저장하거나, 고유한 QA 표식으로 다시 조회한다.
- `item.it_id`처럼 AUTO_INCREMENT가 아닌 PK는 `MAX(it_id)+1`만 무조건 사용하지 않는다. 먼저 `it_5` 또는 정확한 QA 이름으로 기존 ID를 찾고, 없을 때만 현재 최대값 다음 값을 배정한다.
- 관계 행을 넣기 전에 참조 ID가 1건인지 확인한다.
- `INSERT INTO ... VALUES (...)`에는 반드시 명시적인 컬럼 목록을 쓴다.
- `REPLACE`, `TRUNCATE`, 범위 없는 `DELETE`, `DROP TABLE`을 사용하지 않는다.

### 1.5 재실행 안전성

- 같은 SQL을 두 번 실행해도 QA 정의와 보상이 중복되지 않아야 한다.
- 이름만 보고 전역 데이터를 삭제한 뒤 재생성하지 않는다. 다음 방식 중 하나를 사용한다.
  1. QA 표식으로 기존 부모 ID를 조회하고 UPDATE 또는 누락 자식만 INSERT
  2. 해당 QA 부모 ID에 딸린 자식만 삭제 후 재생성
- 진행 중인 `[QA]` 미궁 세션 또는 레이드 유닛이 있으면 정의를 재작성하지 말고 검증 결과로 중단 사유를 보여 준다.
- 포인트 지급은 `po_rel_table='@QADATA'`, `po_rel_id='INITIAL_MONEY'`, `po_rel_action='SEED'`의 결정적 관계키로 1회만 만든다. `uniqid()`에 해당하는 매번 달라지는 키를 만들지 않는다.

### 1.6 transaction에 대한 정확한 설명

- 저장소 기본 설치본에는 MyISAM 테이블이 많다. `START TRANSACTION`을 넣었다고 해서 MyISAM 변경까지 rollback되는 것처럼 설명하지 않는다.
- 신규 미궁 테이블과 전환 완료된 inventory는 InnoDB transaction을 사용할 수 있지만 character, item, skill, point 등은 실제 engine 점검 결과에 따라 비원자적일 수 있다.
- QA seed는 사이트를 점검 모드로 두고 다른 쓰기 요청이 없는 상태에서 실행한다.
- 각 큰 단계 뒤에 검증 쿼리를 두고, 오류가 나면 다음 단계로 진행하지 않는 형태로 작성한다.
- `CREATE PROCEDURE` 권한이 있으면 임시 assertion procedure와 `SIGNAL SQLSTATE '45000'`을 써도 된다. 권한이 없으면 mutation SQL 앞에 별도의 사전 점검 구역을 두고 결과가 맞을 때만 다음 구역을 실행하라고 명시한다.

### 1.7 금지 사항

- `install/gnuboard5.sql`을 다시 실행하지 않는다. 이 파일에는 DROP이 있다.
- `k_battle/extend/install/database_plugin.sql`과 `database_realtime.sql`을 데이터가 있는 DB에 다시 실행하지 않는다. 둘 다 DROP/ALTER가 포함될 수 있다.
- `install/dungeon/001_inventory_engine.manual.sql`의 opt-in 값을 seed SQL이 바꾸거나 자동 실행하지 않는다.
- `avo_member`, `avo_point`, quest/shop 핵심 테이블의 engine을 변경하지 않는다.
- 미궁 runtime 테이블에 방, 전투, 행동, 보관 인벤토리, 정산 행을 미리 만들지 않는다. 앱이 입장/출발/진행 중 생성하게 한다.
- 레이드 buff/log/skill의 runtime snapshot을 미리 만들지 않는다. 캐릭터 입장과 레이드 시작 코드가 생성하게 한다.
- 이미지 파일을 SQL로 만들었다고 주장하지 않는다. SQL에는 URL 또는 상대 경로만 저장할 수 있다.

---

## 2. 실행 전 공식 bootstrap과 migration

### 2.1 기본 확장 schema bootstrap

이 프로젝트는 기본 설치 SQL만으로 끝나지 않는다. 여러 PHP extension과 관리자 화면이 최초 로드 때 테이블/컬럼을 만든다. QA SQL 실행 전에 최고관리자로 아래 기능을 한 번씩 열어 schema가 준비되게 한다.

- 커뮤니티/캐릭터 설정
- 기본 스탯 및 전투 연동 코드 관리
- 스킬 관리
- 맵 관리와 맵 이벤트 관리
- NPC 관리
- 던전 관리
- K 전투 플러그인 설정
- 통합 전투 설정 `adm/990_unified_skill_map.php`
- 실시간 레이드 설정

운영 환경에서는 페이지 로드의 동적 ALTER에 의존하지 말고 현재 DB의 `SHOW CREATE TABLE` 결과와 저장소 코드를 비교해 공식 설치 절차로 준비한다.

### 2.2 미궁 수동 migration

아래 순서는 별도 운영 작업이다. QA seed SQL에 합치지 않는다.

1. `php install/dungeon/inventory_preflight.php`
2. inventory 백업과 복원 시험, 쓰기 중단
3. `install/dungeon/001_inventory_engine.manual.sql`
   - 이미 InnoDB이면 ALTER하지 않는다.
   - MyISAM이면 검토 후 수동 opt-in한다.
4. `install/dungeon/002_inventory_boundary.manual.sql`
5. `install/dungeon/003_labyrinth.manual.sql`
6. `install/dungeon/004_inventory_index.manual.sql`
7. `adm/dungeon_maze.php`에서 readiness가 통과하는지 확인한다.

미궁 출발 조건은 다음을 모두 요구한다.

- MariaDB 10.2.4 이상 또는 MySQL 8.0 이상
- `inventory` InnoDB
- inventory의 단일 AUTO_INCREMENT PK `in_id`
- inventory owner 조회용 `ch_id` 선두 index
- `inventory_guard`, `inventory_journal` InnoDB
- 모든 `dungeon_maze_*` 테이블, 필수 컬럼, unique/index, meta schema version 준비

준비되지 않았으면 QA 미궁 설정과 기존 `dungeon_state` 정의까지는 넣을 수 있지만 **미궁 입장/출발 테스트가 차단되는 것이 정상**이다.

---

## 3. SQL 첫 부분에 넣을 사전 점검

### 3.1 서버와 테이블

다음을 출력하고 하나라도 없으면 data INSERT를 실행하지 않는다.

```sql
SELECT VERSION(), DATABASE();
SHOW TABLE STATUS;
```

필수 테이블 그룹은 다음과 같다. 실제 prefix를 붙인다.

**기본/경제**

- `config`, `member`, `point`
- `character`, `character_class`, `character_side`
- `status`, `status_character`, `status_extra`
- `item`, `inventory`

**스킬/통합 전투**

- `skill`, `skill_level`, `skill_has`
- `unified_combat_stat_map`, `unified_combat_config`
- `k_battle_plugin_config`, `k_battle_stat_func`
- `k_battle_skill_info`, `k_battle_skill`, `k_battle_skill_ch`

**맵/NPC**

- `newmap_config`, `newmap_action`, `newmap_event`, `newmap_work`
- `newmap_npc_place`, `newmap_npc_script`, `newmap_npc_choice`, `newmap_npc_encounter`
- `npc_setting`, `npc_item`, `npc_log`

**장비/레이드**

- `k_battle_equip_ch`, `k_battle_equip_ug`
- `k_battle_monster`, `k_battle_skill_mo`, `k_battle_pattern_mo`
- `k_battle_realtime`, `k_battle_realtime_unit`, `k_battle_realtime_buff`, `k_battle_realtime_log`, `k_battle_realtime_skill`
- `k_raid_list`

**던전/미궁**

- `dungeon`, `dungeon_item`, `dungeon_state`, `dungeon_member`, `dungeon_log`
- `inventory_guard`, `inventory_journal`
- `dungeon_maze_meta`, `dungeon_maze_config`, `dungeon_maze_session`, `dungeon_maze_member`, `dungeon_maze_room`, `dungeon_maze_battle`, `dungeon_maze_action`, `dungeon_maze_inventory`, `dungeon_maze_vote`, `dungeon_maze_log`, `dungeon_maze_reward`, `dungeon_maze_status`, `dungeon_maze_item_effect`

### 3.2 동적 컬럼

반드시 `SHOW COLUMNS`/`SHOW INDEX`로 다음을 확인한다.

- `member.ch_id`
- `character.ma_id`, `character.ch_skill_slot`
- `status.st_use_hp`, `status.st_type1`부터 `st_type10`
- `skill.sk_effect_type`
- `item.it_use_battle_able`, `item.ug_limit`, `item.eq_type`
- `k_battle_skill.unified_a_sk_id`
- `k_battle_skill_ch.unified_a_sh_id`
- `k_battle_plugin_config.ver_realtime`와 `realtime` (실시간 레이드 설치/활성 상태)
- `k_battle_realtime_skill`의 `unified_a_sk_id`, `unified_value`, `unified_mp`, `unified_turn`, `unified_cool`, `unified_def_code`, `unified_def_type`, `unified_def_enermy`, `unified_mod_type`, `unified_effect_type`

미궁 readiness는 row 존재만으로 판단하지 않는다. `dungeon_maze_meta(singleton=1, schema_version=1)`과 모든 index까지 검사한다.

### 3.3 관리자 계정

다음 조건을 모두 확인한다.

- `config.cf_admin`이 비어 있지 않다.
- 그 ID의 member가 정확히 1명 있다.
- `mb_level=10`이다.
- QA SQL 실행 전 일반 회원은 없다는 가정과 실제 count가 맞는다.

SQL 변수 이름은 예를 들어 다음처럼 고정한다.

```sql
SET @qa_admin_mb_id := (SELECT cf_admin FROM <prefix>config LIMIT 1);
SET @qa_admin_count := (
  SELECT COUNT(*) FROM <prefix>member
  WHERE mb_id=@qa_admin_mb_id AND mb_level=10
);
```

관리자 비밀번호, 이메일, 닉네임은 수정하지 않는다.

---

## 4. 추가해야 할 테스트 설정값

아래 설정은 요청한 기능이 화면에 나타나고 서로 연결되기 위해 필요하다. 기존 값이 더 큰 경우 불필요하게 낮추지 않는다.

### 4.1 사이트/경제

| 설정 | QA 값 | 이유 |
|---|---:|---|
| `config.cf_open` | `1` | 사이트 공개 |
| `config.cf_character_count` | 최소 `5` | 관리자 1명이 QA 캐릭터 3명과 NPC 2명을 관리 |
| `config.cf_status_point` | 최소 `300` | 예시 스탯 합계 수용 |
| `config.cf_money` | `골드` | 보상/판매 문구 확인 |
| `config.cf_money_pice` | `G` | 화폐 단위 확인 |
| `config.cf_exp_name` | `경험치` | 레이드 보상 문구 확인 |
| `config.cf_exp_pice` | `EXP` | 경험치 단위 확인 |
| `config.cf_use_point` | `1` | 돈 조건 및 보상 테스트 |

`cf_item_category`에는 기존 값을 보존하면서 누락된 항목만 `||` 구분자로 추가한다.

- `소모품`
- `재료`
- `퀘스트`
- `장비(K)`
- `스킬획득`
- `스킬레벨업`
- `스킬슬롯추가`

### 4.2 스탯/스킬

| 설정 | QA 값 |
|---|---|
| `config.cf_status_select_type` | `체력||공격||방어||회복||민첩||운` |
| `config.cf_skill_count` | `4` |
| `config.cf_skill_count_max` | `8` |

`cf_status_select_type`의 순서는 `status.st_typeN` 의미와 연결되므로 seed 후 임의로 재정렬하지 않는다.

### 4.3 맵/던전

| 설정 | QA 값 |
|---|---:|
| `config.cf_use_map` | `1` |
| `config.cf_use_map_all` | `0` |
| `config.cf_dungeon_open` | `1` |
| `config.cf_dungeon_map` | `1` |
| `config.cf_dungeon_count` | `1` |
| `config.cf_dungeon_time` | `24` |
| `config.cf_dungeon_enter` | `20` |
| `config.cf_dungeon_reset` | 실행 시각의 정시, `YYYY-MM-DD HH:00:00` |

`cf_dungeon_reset`은 비어 있거나 `0000-00-00 00:00:00`으로 두지 않는다.

### 4.4 통합/K 전투

`k_battle_plugin_config`의 `cf_id=1`을 보장하고 다음을 설정한다.

| 컬럼 | QA 값 |
|---|---|
| `hp` | QA 기본 스탯 `체력`의 `st_id` |
| `mp` | QA 기본 스탯 `정신`의 `st_id` |
| `speed` | `민첩` 타입에 연결한 K 표시 스탯 `sc_id` |
| `stat_list` | 아래 통합 mapping의 K `sc_id`를 map_id 순서대로 `|` 연결 |
| `hp_name` | `HP` |
| `mp_name` | `MP` |
| `skill_max` | `8` |
| `equip_type` | `무기|방어구|장신구` |
| `equip_max` | `0|1|1|2` |
| `limit_atk/heal/item/skill/equip` | `0` |
| `skill_img` | `1` |
| `realtime` | `1` |

`equip_max`는 슬롯 이름 목록이 아니다. 순서대로 `공용`, `무기`, `방어구`, `장신구`의 허용 개수다.

`ver_realtime` 컬럼이 없으면 실시간 레이드가 설치되지 않은 상태다. QA seed가 컬럼을 임의 추가하지 말고 `adm/980_k_data_install.php`의 공식 실시간 레이드 설치를 먼저 완료하게 한다.

`unified_combat_config` 첫 행은 다음 값으로 upsert한다.

- `basic_atk_code='공격력'`
- `basic_heal_code='회복력'`
- `basic_guard_code='방어'`
- `speed_status_type='민첩'`

### 4.5 미궁 meta

- `dungeon_maze_meta.singleton=1`
- `schema_version=1` 유지
- `action_timeout=60`

60초는 서버 deadline 동작을 시험하기 위한 값이다. WebSocket, daemon, Redis, 짧은 polling을 추가하지 않는다.

---

## 5. 생성할 QA 데이터의 정확한 목록

## 5.1 소속과 클래스

각 1개를 만든다.

- `character_side`: `[QA] 조사단`, auth 0
- `character_class`: `[QA] 모험가`, auth 0

생성한 ID를 모든 QA 플레이어 캐릭터에 연결한다.

## 5.2 캐릭터 5명

### 플레이어 캐릭터

모두 `mb_id=@qa_admin_mb_id`, `ch_state='승인'`, `ch_type=''`, QA 시작 맵, `ch_skill_slot=4`로 만든다.

| 역할 | 이름 | 체력 | 힘 | 방어 | 정신 | 민첩 | 행운 |
|---|---|---:|---:|---:|---:|---:|---:|
| 균형/주 테스트 | `[QA] 아린` | 120 | 18 | 12 | 14 | 15 | 10 |
| 탱커 | `[QA] 바론` | 180 | 12 | 22 | 8 | 8 | 6 |
| 지원 | `[QA] 세라` | 100 | 8 | 10 | 24 | 12 | 14 |

`member.ch_id`는 `[QA] 아린`의 `ch_id`로 설정한다. SQL 재실행 때 이미 사용자가 다른 비 QA 캐릭터를 선택한 상태라면 덮어쓰지 말고 경고를 출력한다.

### NPC 캐릭터

| 이름 | 용도 |
|---|---|
| `[QA NPC] 리브` | 스탯/아이템/돈/호감도 선택지 이벤트 |
| `[QA NPC] 모아` | 대사 반복 규칙과 맵별 등장 확인 |

- `ch_type='npc'`
- `ch_state='승인'`
- 이미지 경로 기본값은 `/img/shop/npc.png` 또는 `/img/system/캐릭터전신.png`를 사용한다.
- 배포 경로가 서브디렉터리라면 절대 URL을 추측하지 말고 빈 문자열을 사용하거나 사용자가 제공한 사이트 URL을 붙인다.
- SQL은 파일 업로드를 하지 않는다.

## 5.3 기본 스탯 6개

`status`는 캐릭터 상태값이 아니라 스탯 정의 테이블이다. 동적 컬럼을 포함한 실제 column list로 삽입한다.

| 순서 | 이름 | min | max | HP | 연결 type |
|---:|---|---:|---:|---:|---|
| 1 | `체력` | 1 | 999 | 1 | `st_type1=1` |
| 2 | `힘` | 0 | 999 | 0 | `st_type2=1` |
| 3 | `방어` | 0 | 999 | 0 | `st_type3=1` |
| 4 | `정신` | 0 | 999 | 0 | `st_type4=1` |
| 5 | `민첩` | 0 | 999 | 0 | `st_type5=1` |
| 6 | `행운` | 0 | 999 | 0 | `st_type6=1` |

- `st_use_max=1`, `st_order`는 표의 순서다.
- QA HP는 정확히 한 행만 `st_use_hp=1`이어야 한다.
- `status_character`에는 플레이어 3명 × 스탯 6개 = 18행을 만든다.
- `sc_max`는 위 캐릭터 표의 값이다.
- `sc_value=0`이 현재치 full 상태다. 현재치는 `sc_max-sc_value`로 계산된다.
- NPC 비주얼 이벤트의 `stat` 조건은 현재치가 아니라 `sc_max`를 검사한다.

## 5.4 전투 연동 코드

`status_extra.ex_name`은 기능 코드로 쓰이므로 아래 이름을 정확히 사용한다.

| 이름 | 기본 설정 목적 |
|---|---|
| `공격력` | 기본 1~3 + `공격` 타입 × 1 |
| `방어력` | `방어` 타입 × 0.5 |
| `회복력` | 기본 2~4 + `회복` 타입 × 1 |
| `방어` | `방어` 타입 × 1, 기본 방어 행동의 감소율 |
| `자동회피` | `민첩` 타입으로 낮은 확률을 만들기 위한 코드 |
| `공통대미지` | 기본 0, 확장 효과 확인용 |

모든 NOT NULL 컬럼을 명시한다. 배율 컬럼은 숫자 문자열을 쓰며 사용하지 않는 연동은 0 또는 빈 문자열로 둔다. `방어력`은 미궁 코드에서 이름이 고정되어 있으므로 다른 이름으로 바꾸지 않는다.

## 5.5 K 표시 스탯과 통합 mapping

K 스탯 6개를 만든다.

- `[QA] 체력 슬롯`
- `[QA] 공격 슬롯`
- `[QA] 방어 슬롯`
- `[QA] 회복 슬롯`
- `[QA] 민첩 슬롯`
- `[QA] 운 슬롯`

각 행은 `sc_category='stat'`, `sc_10='QADATA'`로 표시한다. 통합 mapping이 실제 값을 공급하므로 수식은 안전한 단순식으로 둔다.

- `sc_value=<대응 A st_id>`
- `sc_type='stat'`
- `sc_1='round'`
- `sc_2='0'`
- `sc_3='9999'`
- 나머지 `sc_4`~`sc_9`는 빈 문자열

`unified_combat_stat_map`은 다음 순서로 1:1 연결한다.

1. `체력` → `[QA] 체력 슬롯`
2. `공격` → `[QA] 공격 슬롯`
3. `방어` → `[QA] 방어 슬롯`
4. `회복` → `[QA] 회복 슬롯`
5. `민첩` → `[QA] 민첩 슬롯`
6. `운` → `[QA] 운 슬롯`

`status_type`과 `k_sc_id`는 각각 유일해야 한다. 기존 같은 QA mapping만 갱신하고 비 QA mapping은 삭제하지 않는다.

## 5.6 A 원본 스킬 7개

A의 `skill`, `skill_level`, `skill_has`가 원본이다. `sh_use=1`인 행만 미궁 출발 때 snapshot된다.

| 스킬 | type/function | 대상 | 유지/쿨 | 핵심 값 |
|---|---|---|---|---|
| `[QA] 강격` | 액티브/공격 | 적 | 0/1 | 공격력 + 레벨값, 적 체력 감소 |
| `[QA] 치유` | 액티브/스탯회복 | 아군 | 0/1 | 회복력 + 레벨값, 체력 회복 |
| `[QA] 철벽` | 액티브/방어 | 자신 | 1/2 | 1턴 피해 감소 |
| `[QA] 잔상` | 액티브/회피 | 자신 | 1/2 | 다음 유효 공격 회피 |
| `[QA] 도발` | 액티브/도발 | 자신 | 2/2 | 단일 공격 유도 |
| `[QA] 신속 강화` | 액티브/스탯강화 | 아군 | 2/2 | 민첩 고정값 증가 |
| `[QA] 전투 호흡` | 패시브/스탯강화 | 자신 | 0/0 | 힘 고정값 증가 |

레벨은 각 스킬에 1, 2 두 개를 만든다. 최소 예시값은 다음과 같다.

| 스킬 | Lv1 set/use | Lv2 set/use |
|---|---|---|
| 강격 | 6 / 정신 4 | 10 / 정신 6 |
| 치유 | 10 / 정신 5 | 18 / 정신 7 |
| 철벽 | 25 / 정신 3 | 40 / 정신 5 |
| 잔상 | 1 / 정신 4 | 1 / 정신 6 |
| 도발 | 50 / 정신 3 | 75 / 정신 5 |
| 신속 강화 | 5 / 정신 4 | 8 / 정신 6 |
| 전투 호흡 | 3 / 0 | 5 / 0 |

세부 컬럼 규칙:

- 공통: `sk_cate='QADATA'`, `sk_effect_type='flat'`
- 강격: `sk_status_code='공격력'`, `sk_value_type='+'`, `sk_mod_enermy='체력'`, `sk_mod_type='-'`
- 치유: `sk_status_code='회복력'`, `sk_value_type='+'`, `sk_mod_st_id=<체력 st_id>`, `sk_mod_type='+'`
- 철벽: `sk_status_code='방어'`, `sk_value_type='+'`, `sk_def_code='방어'`, `sk_def_type='+'`
- 신속 강화: `sk_mod_st_id=<민첩 st_id>`, `sk_mod_type='+'`
- 전투 호흡: `sk_mod_st_id=<힘 st_id>`, `sk_mod_type='+'`
- 액티브 스킬의 `sk_use_st_id=<정신 st_id>`
- 패시브는 `sk_use_st_id=0`

보유/장착 배치:

- 아린: 강격, 치유, 잔상, 신속 강화
- 바론: 강격, 철벽, 도발, 전투 호흡
- 세라: 치유, 잔상, 신속 강화, 전투 호흡

모두 `sh_level=1`, `sh_limit=0`, `sh_use=1`, `sh_datetime=NOW()`로 시작한다. `sh_level`과 실제 `skill_level.sl_level`이 반드시 일치해야 한다.

### 레이드용 K 캐시

Raw SQL로 A 스킬을 넣으면 PHP의 `unified_skill_compile_definition()`이 자동 실행되지 않는다. 둘 중 하나를 분명히 선택한다.

**권장 방식**

- QA SQL은 A 원본까지만 만들고, import 직후 최고관리자가 통합 전투 설정에서 `레이드 실행 정의 갱신`을 1회 누른다.
- 이후 레이드 입장 시 `unified_skill_sync_character()`가 K 보유 캐시와 전투 snapshot을 만든다.

**SQL만으로 즉시 레이드까지 열어야 하는 방식**

- 실제 동적 컬럼을 확인한 뒤 현재 `extend/unified_skill.lib.php`의 컴파일 규칙을 그대로 따라 `k_battle_skill`과 `k_battle_skill_ch` 캐시를 만든다.
- `k_battle_skill.unified_a_sk_id=<A sk_id>`
- `k_battle_skill_ch.unified_a_sh_id=<A sh_id>`
- 공격/회복/도발/버프의 `si_id`는 `k_battle_skill_info.si_code`가 각각 `atk/heal/aggr/buff`인 행을 조회한다.
- 회피/방어는 `si_code='unified_evade'/'unified_guard'` 정의를 누락 시 1회 만든다.
- 대상 변환은 자신=`self/single`, 아군=`ally/single`, 아군전체=`ally/all`, 적=`enemy/single`이다.
- K 캐시는 원본이 아니다. QA SQL이 K 값을 A로 역동기화해서는 안 된다.

## 5.7 아이템 정의

다음 10종을 만든다. 모든 ID는 변수로 보관한다.

| QA key | 이름 | 타입/핵심 설정 |
|---|---|---|
| HEAL | `[QA] 소형 회복약` | `스탯회복`, 체력 +25, 인벤 사용/전투 사용 가능, 소모품 |
| MP | `[QA] 정신 회복약` | `스탯회복`, 정신 +10, 인벤 사용/전투 사용 가능 |
| REVIVE | `[QA] 부활 깃털` | 전투 사용 가능, 미궁 REVIVE 30% |
| CLEANSE | `[QA] 해독제` | 전투 사용 가능, 미궁 CLEANSE |
| KEY | `[QA] 녹슨 열쇠` | NPC item 보유 분기용, 전투 사용 불가 |
| MAT_A | `[QA] 붉은 가루` | 제작/다중소비용 재료 |
| MAT_B | `[QA] 푸른 가루` | 제작/다중소비용 재료 |
| SELL | `[QA] 판매용 보석` | `it_use_sell=1`, `it_sell=100` |
| SWORD | `[QA] 훈련용 검` | `it_type='장비(K)'`, `eq_type='무기'`, 영구, 전투 격리 대상 아님 |
| ARMOR | `[QA] 훈련용 갑옷` | `it_type='장비(K)'`, `eq_type='방어구'`, 영구, 전투 격리 대상 아님 |

미궁 탐험/클리어 보상용 `[QA] 미궁 결정`을 별도 11번째 아이템으로 만들어도 된다. 이 경우 `it_5='QADATA:MAZE_REWARD'`로 표시한다.

`REVIVE`, `CLEANSE`는 미궁 effect registry가 효과를 결정하므로 일반 `it_type`을 임의의 미지원 소비 타입으로 만들지 않는다. 둘 다 `it_use_battle_able=1`이어야 출발 시 dungeon inventory에 격리된다.

## 5.8 일반 inventory 구성

각 아이템은 이 프로젝트에서 수량 컬럼 1개로 쌓지 않고 **inventory row 1개가 1개 아이템**이다. 개수만큼 행을 만든다.

아린:

- 소형 회복약 4
- 정신 회복약 2
- 부활 깃털 1
- 해독제 1
- 녹슨 열쇠 1
- 붉은 가루 3
- 푸른 가루 3
- 판매용 보석 2
- 훈련용 검 1
- 훈련용 갑옷 1

바론:

- 소형 회복약 2
- 부활 깃털 1
- 판매용 보석 1

세라:

- 소형 회복약 2
- 정신 회복약 3
- 해독제 1

공통:

- `ch_id`, `ch_name`을 함께 저장한다.
- `in_sdatetime=NOW()`, `in_edatetime`은 실제 schema의 허용 기본값을 따른다.
- `in_use=''`, `in_5='QADATA:SEED'`
- 장비 외 일반 아이템은 `k_battle_equip_ch`에 넣지 않는다.
- 외부에서 새로 얻는 아이템 보존과 미궁 격리 race를 확인할 수 있도록 회복약/재료/판매품은 중복 row를 충분히 둔다.

## 5.9 장비와 강화

`k_battle_equip_ug`에 `[QA] 기본` 강화 단계 1개를 만든다.

- 성공률 100
- 증가 최소/최대 0
- 비용/재료 0
- 색상은 읽기 쉬운 기본값

아린의 검/갑옷 inventory `in_id`를 참조해 `k_battle_equip_ch` 2행을 만든다.

| 장비 | `eq_use` | 예시 보정 |
|---|---|---|
| 훈련용 검 | `무기` | 힘 위치에 해당하는 `st_N=3` |
| 훈련용 갑옷 | 빈 문자열 | 방어 위치에 해당하는 `st_N=4` |

여기서 `st_N`의 N은 A `status`의 `st_order, st_id` 정렬 순서다. 이 명세에서는 힘이 2번째, 방어가 3번째다.

- 장착 검은 미궁 격리 대상에서 제외되는지 확인한다.
- 해제 상태 갑옷도 `it_type='장비(K)'`이므로 미궁 격리 대상에서 제외되는지 확인한다.
- 장착 효과는 기존 character/stat 계산을 그대로 사용한다.

## 5.10 맵 2개

### `[QA] 중앙 광장`

- `ma_parent=0`
- `ma_start=1`
- `ma_use=1`
- `ma_use_dungeon=1`
- `ma_npc_chance=100`
- 내용에 `[QADATA] 시작 지역` 표식

### `[QA] 안개 숲`

- parent는 중앙 광장
- `ma_start=0`
- `ma_use=1`
- `ma_use_dungeon=1`
- `ma_npc_chance=100`

`ma_move`의 문자열 형식을 추측하지 않는다. 현재 관리자 저장 형식을 확인해 유효한 이동 연결을 넣거나, parent 관계와 캐릭터 시작 위치만 만들고 이동 연결은 관리자 UI에서 저장하는 후속 단계로 명시한다.

## 5.11 맵 조사와 아르바이트

`newmap_action.action_type`은 현재 지원되는 정확한 값 `parttime`을 사용한다.

중앙 광장:

1. `[QA] 즉시 배달`
   - `is_timed=0`, `duration_minutes=0`
2. `[QA] 1분 순찰`
   - `is_timed=1`, `duration_minutes=1`
3. 둘 다 `available_time_use=0`, `action_use=1`

안개 숲:

1. `[QA] 약초 채집`
   - `is_timed=1`, `duration_minutes=1`

`newmap_event`은 1~100 inclusive roll 구간을 빈틈없이 채운다.

**중앙 광장 조사 (`me_type='search'`, `action_id=0`)**

- 1~50: 녹슨 열쇠 1개 획득
- 51~85: 50 골드 획득
- 86~100: 안개 숲 이동

**즉시 배달 (`me_type='parttime'`, 정확한 action_id)**

- 1~70: 100 골드
- 71~100: 소형 회복약 1개

**1분 순찰**

- 1~80: 120 골드
- 81~100: 판매용 보석 1개

**약초 채집**

- 1~50: 붉은 가루 1개
- 51~100: 푸른 가루 1개

모든 행은 `me_use=1`, `me_replay_cnt=0`, `me_now_cnt=0`으로 둔다. `newmap_work`, map log는 미리 만들지 않는다.

## 5.12 NPC 설정과 비주얼 이벤트

### NPC 기본 설정

`npc_setting.ns_id`는 이 코드에서 NPC character의 `ch_id`와 같은 값으로 JOIN된다. 각 NPC에 대해 `ns_id=<NPC ch_id>`를 명시 삽입/갱신한다. 충돌 행이 있다면 다른 ID를 임의로 쓰지 말고 중단한다.

리브의 호감도 단계 예시:

- 0: `낯선 사람`, 0점
- 1: `아는 사이`, 10점
- 2: `친구`, 30점

모아는 0, 5, 20점 단계를 둔다. 나머지 NOT NULL 대사/색상/비용 컬럼도 실제 schema에 맞게 채운다.

`npc_item`에는 리브가 받을 수 있는 `[QA] 판매용 보석` 선물 정의를 1개 만들고 호감도 +5, 일 최대 2회로 설정한다.

### 맵 배치

- 리브 → 중앙 광장, weight 10, use 1
- 모아 → 안개 숲, weight 10, use 1

각 `newmap_npc_place.mn_id`를 script의 FK처럼 사용한다.

### 리브 script와 선택지

script는 `script_type='dialogue'`, use 1로 만들고 placeholder `{이름}`, `{NPC이름}`, `{장소명}`을 대사에 포함한다.

1. `[QA] 첫 조우`
   - repeat `once`
   - 선택지 5개
2. `[QA] 일일 인사`
   - repeat `daily`
3. `[QA] 재방문 대사`
   - repeat `cooldown`, cooldown 60초
   - 첫 조우 script ID를 `script_require_script_id`로 요구

첫 조우 선택지:

| 선택지 | 조건 | 성공 | 실패 |
|---|---|---|---|
| 힘으로 문을 연다 | `stat`, target `힘`, `>= 15` | 30골드, 호감도 +2 | 호감도 -1 |
| 열쇠를 보여 준다 | `item`, target `<녹슨 열쇠 it_id>`, `>= 1` | 회복약 1, 호감도 +3 | 실패 문구 |
| 비용을 낸다 | `money`, target 빈 값, `>= 100` | 호감도 +1 | 호감도 -1 |
| 친분을 증명한다 | `favor`, target `<리브 ch_id>`, `>= 10` | 판매용 보석 1 | 실패 문구 |
| 그냥 인사한다 | 조건 없음 | 호감도 +1 | 없음 |

condition type과 target 의미:

- `stat`: `choice_check_target`은 정확한 `status.st_name`; 비교값은 `status_character.sc_max`
- `favor`: target은 NPC character ID이며 `npc_log.nl_value` 합계
- `money`: target은 NULL 또는 빈 문자열, 실제 값은 member `mb_point`
- `item`: target은 `item.it_id`, 실제 값은 inventory row count
- operator는 `>=`, `>`, `<=`, `<`, `=`, `!=`만 사용

`newmap_npc_encounter`와 `npc_log`는 기본적으로 runtime에서 만들게 한다. favor 성공 분기를 즉시 확인하고 싶다면 리브/아린 조합에 `[QADATA] 초기 호감도` log +10을 **정확히 1행만** 선택적으로 만든다. 이 선택을 SQL 주석으로 명시한다.

## 5.13 초기 포인트

돈 조건과 판매 보상을 시험하려면 관리자에게 QA 포인트 50,000을 1회 지급한다.

- `point` 행의 relation triple은 `@QADATA / INITIAL_MONEY / SEED`
- 같은 triple이 있으면 다시 지급하지 않는다.
- point INSERT가 실제로 1행 생긴 경우에만 member `mb_point`를 50,000 증가시킨다.
- `po_mb_point`는 지급 직전 member 잔액 + 50,000으로 기록한다.
- `po_expired=0`, `po_expire_date='9999-12-31'`
- seed 중 다른 point 쓰기가 없는 점검 모드에서 실행한다.

MyISAM 때문에 point INSERT 뒤 member UPDATE 전에 프로세스가 죽는 극단 상황은 완전 원자적이지 않다. 검증 구역에서 relation row와 member 합계를 비교한다. 이 이유로 member/point engine을 바꾸지 않는다.

## 5.14 실시간 레이드와 몬스터

### K 몬스터 2종

`k_battle_monster`의 `st_1..st_10`은 `status`를 `st_order,st_id`로 정렬한 순서에 대응한다.

| 이름 | HP | MP | 힘 | 방어 | 정신 | 민첩 | 행운 | pattern |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| `[QA] 훈련 슬라임` | 80 | 10 | 10 | 5 | 5 | 7 | 1 | 1 |
| `[QA] 시계탑 골렘` | 350 | 30 | 22 | 18 | 8 | 6 | 3 | 1 |

- `raid_type='realtime'`
- `mo_default_act=1` 또는 패턴 fallback이 안전한 현재 코드값
- `mo_thumb`는 기존 이미지 경로 또는 빈 문자열

### 몬스터 스킬/보유/패턴

최소 2개를 만든다.

- `[QA] 몸통박치기`: `si_code='atk'`, 공격 K slot을 `sc_id`로 사용, 적 단일
- `[QA] 광역 진동`: `si_code='atk'`, 적 전체, 2턴 패턴

`k_battle_skill_mo`에 각 몬스터와 스킬 관계를 만든다. `k_battle_pattern_mo.pt_skill`은 `skill_mo.cs_id`가 아니라 **K 스킬의 `sk_id` CSV**다.

- 슬라임 turn 1: 몸통박치기
- 골렘 turn 1: 몸통박치기
- 골렘 turn 2: 광역 진동
- `pt_skill_cnt=1`

공격 스킬은 `bonus_calc='p'`, `default_calc=''`, `sc_id=<공격 K slot>`으로 두면 몬스터 공격 slot + `sk_value`가 피해 기초가 된다. 실제 현재 코드의 허용값이 다르면 관리자 form의 저장값을 우선한다.

### 레이드 정의

`k_battle_realtime`에 다음 1행을 만든다.

- `ra_id='qa_realtime_raid'`
- `ra_title='[QA] 시계탑 훈련 레이드'`
- `ra_state=0` 준비중
- `ra_limit=3`, `ra_limit_now=0`
- `ra_reload='turn'`
- `ra_reload_time=5`
- `ra_turn_type='speed'`
- `ra_mo_auto='auto'`
- `ra_type='pve'`
- `ra_turn=1`, `ra_count=0`, `now_turn=0`
- `ra_system='normal'`
- `ra_time_limit=60`, `ra_time_start=0`
- 보상: 골드 500, 경험치 100, 아이템은 미궁 결정 ID 또는 빈 값, 칭호 0
- BGM/이미지는 빈 문자열, volume 30, type `single`

`k_raid_list`에 `[QA] 레이드 입장` 목록을 만들고 `raid_type='realtime'`, `ra_ids='qa_realtime_raid'`, use 1로 둔다.

준비 상태 레이드에 골렘 monster unit 1개를 넣는다.

- `unit_type='mo'`, `unit_id=<골렘 mo_id>`, `ra_id='qa_realtime_raid'`
- hp/mp current=max
- `st_N`은 monster 정의의 통합 표시 slot 결과를 복사
- stun/aggr/turn flags 0

캐릭터 unit은 미리 넣지 않는다. 아린을 현재 캐릭터로 선택한 뒤 레이드 목록의 참가 버튼을 눌러 `insert_k_battle_unit()`과 A→K 스킬 snapshot을 실제로 시험한다. 레이드 시작은 최고관리자 UI에서 실행한다.

## 5.15 기존 던전 정의와 출현 인스턴스

`dungeon`에 `[QA] 안개 미궁` 1개를 만든다.

- `dg_count=3`
- `ma_id=<안개 숲 ID>`
- `ma_ids='||<안개 숲 ID>||'`
- 출현 구간 `dg_per_s=0`, `dg_per_e=100`
- `dg_point=500`
- 권장 스탯은 힘 또는 체력 ID, 값은 10, 방식은 `+`
- 몬스터 `[QA] 미궁 파수꾼`, HP 180
- 방어 5
- 기본 공격: 매 1턴, 대상 1명, 피해 8~14
- 광역 공격: 3턴마다 5~9
- 조건 공격 1: 2턴마다 민첩 10 이상/이하 중 실제 UI 의미에 맞춰 6~10
- 강점/약점 code는 존재하는 `공격력`/`방어력`을 사용하거나 빈 값
- `dg_use=1`

`dungeon_item` 보상:

- 미궁 결정 1개, 0~100
- 소형 회복약 1개, 0~49

즉시 입장할 수 있도록 `dungeon_state`에 열린 인스턴스 1개를 만든다.

- 같은 QA dungeon의 종료되지 않은 state가 이미 있으면 재사용
- `ds_ma_id=<안개 숲 ID>`
- `ds_state='S'`
- `ds_hp=180`, hurt/weak 값 0
- `ds_datetime=NOW()`와 프로젝트가 기대하는 문자열 형식

`ds_id`는 기존 map 출현 방식과 미궁 session이 공유하는 식별자다. 별도 QA maze session 행을 미리 넣지 않는다.

## 5.16 신규 미궁 설정

`dungeon_maze_config.dg_id=<QA dungeon ID>`에 유효한 JSON을 저장한다. JSON key는 다음을 모두 포함한다.

```json
{
  "min_players": 1,
  "max_players": 3,
  "rooms_min": 9,
  "rooms_max": 12,
  "branches_min": 1,
  "branches_max": 3,
  "weights": {"EMPTY": 2, "SEARCH": 4, "TREASURE": 3, "TRAP": 1},
  "dead_end_percent": 20,
  "encounter_percent": 35,
  "escape_percent": 60,
  "trap_damage": 8,
  "boss": true,
  "points": 500,
  "monsters": [],
  "events": [],
  "rewards": []
}
```

보스 사용 시 `rooms_min >= 2 * branches_max + 2`여야 한다. 위 값 9 >= 8은 유효하다. `weights` key 순서는 정확히 `EMPTY, SEARCH, TREASURE, TRAP`으로 만든다.

`escape_percent`는 일반 몬스터 전투에만 적용한다. 모든 보스 encounter는 도주 불가이며 별도의 보스 도주 설정은 만들지 않는다. 일반 도주 성공/실패를 확정적으로 시험할 별도 QA 설정은 각각 `escape_percent=100`, `0`으로 만든다. 보스 전투에서는 도주 UI가 없고, 직접 `escape_vote=1`을 제출해도 서버가 거부해야 한다. 이전에 저장된 도주 표가 있어도 보스 resolve는 도주하지 않는다.

보스가 있는 미궁은 미처치 상태의 출구 이동 및 직접 클리어 처리가 차단된다. 보스 HP가 0이 되어 `BOSS_RESULT`로 전환된 뒤에는 채팅과 생존자의 `escape_final`만 허용되고, 탈출 후 `CLEAR`로 정산한다. 생존자/전투불능 참가자를 함께 두어 전원 정산은 1회, 클리어 포인트·아이템 보상은 생존자에게만 1회인지 확인한다. 보스 없는 별도 QA 미궁(`boss=false`)도 준비하여 기존 `출구 → CLEAR`를 확인한다. 이 상태들은 SQL로 정산 완료를 선입력하지 말고 실제 행동으로 검증한다.

### monster pool

최소 3개 object를 넣는다.

1. 일반 `[QA] 미궁 파수꾼`, enabled, boss false, weight 10
2. 일반 `[QA] 독안개 정령`, enabled, boss false, weight 5, `status_id=<중독 status ID>`
3. 보스 `[QA] 심층 수호자`, enabled, boss true, weight 1, HP 500

각 object 형태:

```json
{
  "enabled": true,
  "weight": 10,
  "boss": false,
  "data": {
    "dg_mon_name": "[QA] 미궁 파수꾼",
    "dg_mon_img": "",
    "dg_mon_hp": 180,
    "dg_mon_descript": "[QADATA] 일반 조우",
    "dg_defence": 5,
    "dg_d_attack_turn": 1,
    "dg_d_attack_count": 1,
    "dg_d_attack_min": 8,
    "dg_d_attack_max": 14,
    "dg_w_attack_turn": 3,
    "dg_w_attack_min": 5,
    "dg_w_attack_max": 9,
    "dg_s1_attack_turn": 0,
    "dg_s1_attack_st_id": 0,
    "dg_s1_attack_st_value": 0,
    "dg_s1_attack_type": "이상",
    "dg_s1_attack_min": 0,
    "dg_s1_attack_max": 0,
    "dg_s2_attack_turn": 0,
    "dg_s2_attack_st_id": 0,
    "dg_s2_attack_st_value": 0,
    "dg_s2_attack_type": "이상",
    "dg_s2_attack_min": 0,
    "dg_s2_attack_max": 0,
    "dg_strong_code": "",
    "dg_strong_value": 1,
    "dg_weak_code": "",
    "dg_weak_value": 1,
    "dg_weak_effect_count": 0,
    "dg_weak_effect_turn": 0
  }
}
```

`maze_monster_definition()`이 검사하는 `d/w/s1/s2`의 turn/count/min/max/st_id/st_value와 type을 빠뜨리지 않는다.

### room event pool

다음 결과 type을 모두 한 번 이상 포함한다.

1. SEARCH + choices
   - `둘러본다` → TEXT
   - `약병을 챙긴다` → ITEM, 회복약 1
2. TREASURE → ITEM, 미궁 결정 1
3. TRAP → TRAP, ALL, damage 8, 중독 status
4. SEARCH → HEAL, ACTOR, 20
5. EMPTY → MONSTER, 독안개 정령
6. EMPTY → NONE 또는 TEXT

공통 object 예시:

```json
{
  "enabled": true,
  "room_kind": "SEARCH",
  "weight": 10,
  "prompt": "낡은 선반을 조사한다.",
  "choices": [
    {"label": "둘러본다", "type": "TEXT", "text": "벽의 표식을 발견했다."},
    {"label": "약병을 챙긴다", "type": "ITEM", "it_id": 0, "count": 1, "text": "회복약을 얻었다."}
  ]
}
```

- `it_id=0`은 예시 placeholder다. 실제 SQL은 회복약 ID를 넣는다.
- type은 `NONE`, `TEXT`, `ITEM`, `TRAP`, `MONSTER`, `HEAL`만 사용한다.
- target은 `ACTOR`, `ALL`, `RANDOM`만 사용한다.
- 선택지는 한 단계만 허용한다. choice 안에 다시 choices를 넣지 않는다.
- event `room_kind`는 weights의 key여야 한다.

### clear rewards

참가자별 독립 확률로 다음을 넣는다.

- 미궁 결정 1개, 100%
- 소형 회복약 2개, 30%

각 행은 `it_id`, `count` 1~100, `percent` 0~100을 가진다.

## 5.17 미궁 상태이상과 아이템 효과

`dungeon_maze_status`에 다음 3개를 만든다.

1. `[QA] 중독`, duration 3
   - `[ {"type":"DOT_HP","value":5} ]`
2. `[QA] 둔화`, duration 2
   - `[ {"type":"STAT_MODIFIER","stat_id":<민첩>,"mode":"percent","value":-30} ]`
3. `[QA] 기절`, duration 1
   - `[ {"type":"ACTION_DISABLE","value":1} ]`

선택적으로 회복 감소 상태를 하나 더 만든다.

```json
[{"type":"HEAL_MODIFIER","value":-25}]
```

상태 이름, duration, enabled, components가 모두 필요하다. 같은 상태 재적용은 중첩이 아니라 지속시간 갱신이다.

`dungeon_maze_item_effect`:

- 부활 깃털 → `kind='REVIVE'`, `status_id=0`, `value=30`
- 해독제 → `kind='CLEANSE'`, `status_id=<중독 ID>`, `value=1`

---

## 6. 반드시 지킬 INSERT 순서

1. 관리자/engine/schema 사전 점검
2. config 설정과 초기 point 1회 지급
3. side/class
4. 맵 정의
5. 플레이어/NPC character, member.ch_id
6. 기본 status 정의와 status_character
7. status_extra 전투 코드
8. K 표시 stat, unified map, unified config, K plugin config
9. A skill, skill_level, skill_has
10. item 정의
11. inventory rows
12. upgrade/equipment rows
13. map action/event
14. npc_setting/item/place/script/choice
15. K monster/monster skill/ownership/pattern
16. realtime raid/list/monster unit
17. dungeon/dungeon_item/dungeon_state
18. maze status/item effect/config/meta timeout
19. 검증 SELECT

앞 단계의 ID를 이름으로 재조회하기보다 이미 캡처한 변수로 다음 단계에 전달한다. 재조회가 필요하면 exact QA marker와 owner 조건을 같이 사용한다.

---

## 7. runtime 테이블에 미리 넣지 않을 데이터

다음은 사용자 행동이 실제 코드를 통과하는지 시험하기 위해 비워 둔다.

- `newmap_work`, map log
- `newmap_npc_encounter`
- 일반 `npc_log`의 이벤트 결과 행
- `dungeon_member`, `dungeon_log`
- `dungeon_maze_session/member/room/battle/action/inventory/vote/log/reward`
- `inventory_guard`, `inventory_journal`
- `k_battle_realtime_buff/log/skill`
- realtime character unit

예외는 준비 상태 레이드의 monster unit 1행과 선택적인 초기 NPC favor 1행뿐이다.

---

## 8. SQL 끝에 포함할 검증 쿼리

검증 결과는 예상 count와 실제 count를 함께 보여 준다.

### 8.1 기본 관계

- 최고관리자 1명, 현재 캐릭터가 아린인지
- QA 플레이어 3명, NPC 2명
- 플레이어 모두 `승인`, 같은 admin owner, 시작 맵 지정
- 스탯 정의 6개, HP 지정 정확히 1개
- 각 플레이어의 status_character 6개씩
- A 스킬 7개, 각 level 2개, 보유/장착 12행
- 모든 `skill_has.sh_level`에 대응 level 존재

### 8.2 아이템/장비

- 모든 QA inventory가 올바른 owner를 가짐
- equipment `in_id`가 실제 inventory와 같은 `it_id`를 가리킴
- 검 장착, 갑옷 해제
- 장비 item은 `it_use_battle_able=0`
- 미궁 사용 item은 battleable=1이며 `스탯회복` 또는 item_effect registry에 존재
- `inventory` engine과 `(ch_id,in_id)` 선두 index readiness 출력

### 8.3 맵/NPC

- 시작 맵 정확히 1개
- 각 parttime event의 `action_id`와 map이 일치
- 각 action별 확률 범위가 1~100을 덮는지
- NPC placement의 NPC가 실제 `ch_type='npc'`
- `npc_setting.ns_id = character.ch_id`
- 모든 choice의 script, item, favor target이 존재

### 8.4 통합 전투/레이드

- status type 6개와 unified map 6개가 1:1인지
- plugin hp/mp/speed가 존재하는 ID인지
- `stat_list` 순서와 map_id 순서가 같은지
- A skill을 K cache까지 만들었다면 `unified_a_sk_id`/`unified_a_sh_id` 누락이 없는지
- raid state 0, raid list에 ID 포함
- raid monster unit 1개와 origin monster 존재
- monster pattern의 모든 K sk_id가 monster ownership과 일치

### 8.5 던전/미궁

- QA dungeon 1개, open state 1개, map 일치
- maze config JSON이 `JSON_VALID(settings)=1`
- `min_players=1`, `max_players=dg_count`
- boss monster 1개 이상, rooms/branches 제약 통과
- reward/item/status 참조가 모두 존재
- 미궁 meta version 1과 timeout 60
- 모든 maze table engine InnoDB
- `inventory_guard/journal` InnoDB
- migration/readiness가 미완료면 명확한 `NOT READY` 결과

### 8.6 orphan 검사

각 관계별 `LEFT JOIN ... WHERE parent.id IS NULL` count가 0인지 보여 준다.

- status_character → status, character
- skill_level → skill
- skill_has → skill, character
- inventory → item, character
- equip → inventory, item
- map event → map/action/item
- NPC place/script/choice → parent
- monster skill/pattern → monster/skill
- raid unit → raid/monster
- dungeon item/config/state → dungeon/item/map
- maze item effect/status IDs → item/status

---

## 9. 실제 QA 실행 시나리오

SQL 생성자는 마지막 주석에 아래 순서를 출력한다.

### 9.1 캐릭터/스탯/스킬

1. 아린이 현재 캐릭터인지 확인
2. 프로필에서 스탯 6개와 full current 확인
3. 장착 스킬 4개 확인
4. 검 장착 효과를 확인하고 갑옷 장착/해제

### 9.2 맵/아르바이트/NPC

1. 중앙 광장 조사로 3개 결과 구간 확인
2. 즉시 배달 완료와 1분 순찰 시작/완료 확인
3. NPC 등장률 100%에서 리브 이벤트 확인
4. 힘 조건 성공, 없는 아이템 조건 실패, 열쇠 보유 성공, 돈 성공, favor 성공/실패 확인
5. once/daily/cooldown/required script 반복 제한 확인

### 9.3 레이드

1. 통합 스킬 캐시 compile 실행
2. 아린으로 `[QA] 레이드 입장`에서 참가
3. 관리자 화면에서 골렘과 character unit 확인
4. 레이드 시작 후 기본 공격, 치유, 스킬, 장비 stat, monster 자동 패턴 확인
5. 종료 후 state/reset 확인

### 9.4 미궁

1. `adm/dungeon_maze.php` readiness 통과 확인
2. 아린을 안개 숲으로 이동
3. 열린 `ds_id`에 입장
4. WAITING에서 inventory가 그대로인지 확인
5. READY 후 출발
6. 출발 transaction 뒤 battleable 미장착 소비품만 일반 inventory에서 빠졌는지 확인
7. 장착 검과 해제 갑옷이 일반 inventory에 남았는지 확인
8. 방 이동, 조사 choice, treasure, trap/status, heal, monster battle 확인
9. 같은 battle turn submit 중복, 새로고침, timeout resolve 확인
10. 미궁 획득 item을 같은 미궁에서 사용
11. 클리어/실패/자진 퇴장/관리자 강퇴/강제 종료에서 같은 settlement 경로 확인
12. participant별 settled 1회, 원래 미사용품 복원, 사용품 미복원, 획득 미사용품 신규 추가 확인

### 9.5 inventory race 수동/자동 테스트

동시에 요청할 수 있는 도구가 있을 때 다음을 확인한다.

- 동일 판매품 동시 판매: 하나만 성공, 포인트 1회
- 동일 회복약 동시 사용: 하나만 성공, 효과 1회
- 판매 vs 미궁 출발: row를 먼저 확보한 쪽만 성공
- 일반 사용 vs 미궁 출발: row를 먼저 확보한 쪽만 성공
- 재료 여러 개 소비 vs 미궁 출발: 전부 성공 또는 전부 실패, 일부 소비 없음
- 전송 vs 미궁 출발: 하나만 성공

---

## 10. QA 데이터 정리 규칙

정리 SQL을 작성할 경우 별도 구역으로 두고 기본 실행에서는 주석 처리한다.

1. 활성 QA 미궁/레이드가 없는지 먼저 확인
2. runtime 정산과 보관 inventory가 모두 완료됐는지 확인
3. child에서 parent 순서로 QA marker가 있는 행만 삭제
4. equipment → inventory → item 순서 준수
5. skill_has/cache/level → skill 순서 준수
6. NPC choice/script/place/item/setting → NPC character 순서 준수
7. dungeon state/config/item → dungeon 순서 준수
8. map event/action → map 순서 준수
9. member.ch_id가 QA character면 0 또는 안전한 기존 character로 바꾼 뒤 character 삭제

초기 50,000 포인트는 point 행을 단순 삭제하고 member 잔액을 그대로 두지 않는다. 기존 `insert_point()`와 같은 결정적 반대 relation을 사용해 회수하거나, point 합계와 member 잔액을 함께 검증한 전용 cleanup을 작성한다. 다른 point 사용 이력이 있으면 자동 회수하지 않고 운영자가 확인하게 한다.

진행 중 미궁의 `dungeon_maze_inventory.original_row`, settlement/reward journal, `inventory_journal`을 정리 목적으로 삭제하지 않는다.

---

## 11. 완료 판정

LLM이 만든 SQL은 다음을 모두 만족할 때만 완료다.

- 관리자 계정 정보를 훼손하지 않는다.
- 실제 prefix와 현재 schema를 사용한다.
- 요청한 데이터가 단순 목록이 아니라 서로 참조 가능한 한 세트다.
- 아린 한 캐릭터만으로 맵, NPC, 레이드 입장, 1인 미궁을 바로 시험할 수 있다.
- 바론/세라로 역할별 stat/skill/equipment 차이를 시험할 수 있다.
- NPC 조건 분기의 성공과 실패를 모두 만들 수 있다.
- 레이드의 monster, skill, pattern, list, 준비중 room이 연결된다.
- 미궁의 이동, 조사, 선택, 아이템, 함정, 상태, 회복, 전투, 보스, 정산을 모두 시험할 정의가 있다.
- engine/migration이 준비되지 않은 환경에서 미궁 출발을 우회하지 않는다.
- runtime/history 데이터를 불필요하게 미리 만들지 않는다.
- 재실행 시 QA 정의, 포인트, reward가 중복되지 않는다.
- 마지막 검증 쿼리에서 orphan이 0이고 예상 count가 모두 맞는다.

이 조건 중 하나라도 실제 schema와 충돌하면 임의로 컬럼을 생략하거나 값을 추측하지 말고, 충돌한 `SHOW CREATE TABLE`과 저장소 경로를 명시해 SQL 생성을 중단한다.
