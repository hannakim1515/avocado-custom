# 던전·전투 코드 지도

던전과 미궁은 같은 콘텐츠다. 모든 던전은 **대기실 → 방 탐험 → 몬스터 전투 → 클리어**로 진행한다. 입장 즉시 전투하는 구형 엔진은 폐기했다. MMB 레이드·실시간 레이드·1:1 배틀은 유지한다.

## 수정할 때 시작할 파일

| 작업 | 화면 / 요청 | 핵심 코드 |
| --- | --- | --- |
| 던전 목록·발생·맵 입장 | `dungeon/index.php`, `map/inc/info_dungeon.php` | `extend/dungeon.lib.php` |
| 던전 화면·상태 조회 | `dungeon/maze.php`, `dungeon/maze_state.php` | `dungeon/maze_client.page.php`, `dungeon/maze.js`, `lib/maze_client.lib.php` |
| 참가·출발·진행 상태 | 위 화면의 POST | `extend/dungeon_maze_session.lib.php`, `extend/dungeon_maze_actions.lib.php` |
| 전투·종료·아이템 복구 | 위 화면의 POST | `extend/dungeon_maze_combat.lib.php`, `extend/dungeon_maze_inventory.lib.php` |
| 보상 정의·확률 추첨 | 던전 편집의 보상 탭 | `lib/dungeon_reward.lib.php`, `maze_settle_member()` |
| 던전 기본·출현·탐험·보상 편집 | `adm/dungeon_form.php` | `adm/dungeon_form_update.php`, `adm/js/dungeon_form_editor.js`, `adm/dungeon_admin.lib.php`, `adm/dungeon_maze.admin.lib.php` |
| 공용 몬스터·HP 구간별 순서 | `adm/monster_form.php` | `adm/monster.view.php`, `lib/monster_shared.lib.php`, `lib/monster_adapters.lib.php` |
| 공용 스킬·상태이상 | `adm/monster_skill_form.php`, `adm/monster_status_form.php` | 해당 관리자 파일과 `lib/monster_shared.lib.php` |
| 전체 던전 행동 제한시간 | `adm/dungeon_maze_common.php` | `dungeon_maze_meta.action_timeout` |
| 발생 현황·로그·강제 종료 | `adm/dungeon_state_list.php`, `adm/dungeon_log.php` | `adm/dungeon_maze_session.view.php`, `adm/dungeon_combat_log.view.php` |
| MMB·실시간 레이드 몬스터 | K 레이드 화면 | `k_battle/extend/raid/0_default.php`, `lib/monster_adapters.lib.php` |
| 플레이어 광역 공격·레이드 범위 | 스킬 관리의 공격 범위 | `extend/unified_skill.lib.php`, `extend/dungeon_maze_combat.lib.php`, `k_battle/extend/raid/1_skill.php` |
| QA 데이터·서버 내보내기 | `docs/qa-server-test.md` | `dev/tools/seed_qa_server.php`, `dev/tools/qa_database.py`, `dev/tests/qa_server_data.php` |

`ground.php`·`applicate.php`는 같은 탐험 화면으로 보내는 이전 URL 안내만 남았다. `state.php`도 공용 탐험 상태 조회를 사용한다. `dungeon/proc`, `dungeon/inc`, `lib/dungeon_client.lib.php`의 구형 전투 구현은 제거했다. 예전 미궁·출현 편집 URL은 공용 던전 편집으로 연결한다.

## 정의는 한 곳에 저장한다

| 정보 | 유일한 저장 위치 | 읽는 방식 |
| --- | --- | --- |
| 던전 이름·설명·인원·지역·포인트 | `dungeon` | 던전·맵·관리자 공통 |
| 방 수·갈림길·출현 확률·이벤트 | `dungeon.dg_settings` | JSON에는 탐험 규칙만 저장 |
| 클리어 아이템·수량·확률 | `dungeon_item.di_count / di_percent` | 아이템마다 독립 추첨, 소수점 2자리, 0% 제외·100% 확정 |
| 출현 몬스터·가중치·보스 여부·동시 마릿수 | `dungeon_monster_pool` | `monster_id`로 공용 정의 참조, `min_count / max_count`로 개체 수 지정 |
| 몬스터 능력치·행동 | `monster_master.profile` | 모든 전투에서 같은 몬스터 정의 사용 |
| K 몬스터 조회 | `k_battle_monster` VIEW | 공용 정의를 읽는 뷰이며 별도 저장 없음 |
| 목록의 대표 몬스터 | `dungeon_monster_read` VIEW | 보스 우선, 없으면 첫 몬스터 |
| 캐릭터 스킬의 공격 범위 | `skill.sk_dungeon_target_mode` | 던전 직접 조회·레이드 실행 캐시로 컴파일. ALL은 해당 전투의 살아 있는 적 전체 |
| 부활·상태이상 치료 아이템 | `item.it_type / it_value` | 별도 미궁 아이템 효과 정의 없음 |

`dg_settings`에는 `monsters`, `rewards`, `title`, `description`, `max_players`, `points`를 저장하지 않는다. 이벤트의 전투 결과도 `monster_id`만 저장한다. 공용 정의를 읽은 값은 출발 시 실행 스냅샷에 고정한다.

**모든 몬스터 출현은 `dungeon_monster_pool`을 기준으로 한다.** 방 이벤트와 조사 선택지도 해당 던전의 사용 중인 출현 목록에서만 몬스터를 고른다. 이벤트는 등장 계기와 문구를 정의하며 별도 출현 목록을 만들지 않는다. 저장 시 목록 밖 몬스터 전투는 거절하고, 과거 설정·실행 스냅샷·이미 생성된 방에 남은 목록 밖 이벤트는 실행하지 않는다. 기존 방의 선택지 번호는 유지하여 필터링 뒤 다른 결과가 실행되지 않도록 한다. 진행 중인 탐험의 기준은 출발 스냅샷이다.

## 실행 기록의 역할

| 테이블 | 필요한 이유 |
| --- | --- |
| `dungeon_state` | 던전 발생과 탐험 세션의 ID·단계·위치·버전·출발 스냅샷을 한 행에서 관리 |
| `dungeon_maze_member` | 해당 던전 참가자의 준비·HP·정산 상태 |
| `dungeon_maze_room / battle / action` | 생성된 지도, 방 전투, 턴별 제출 행동 |
| `dungeon_maze_inventory` | 출발 시 분리한 아이템의 원본 행·ID 보관과 복구 |
| `dungeon_maze_reward` | 포인트 지급을 재시도해도 중복 지급하지 않는 정산 기록 |
| `dungeon_maze_vote / log` | 파티 투표와 탐험·대화 기록 |
| `dungeon_combat_event` | 던전·레이드의 행동자·대상·피해 등 전투 기록 |
| `monster_runtime` | 개체별 위협도·HP 단계·현재 순서·MMB 몬스터 턴 |

이 테이블들의 `maze` 이름은 기존 실행 기록의 명칭이다. 별도 미궁 정의나 진행 모드를 의미하지 않는다. 설정과 실행 스냅샷은 수명이 다르다. 실행 중인 전투는 관리자 편집으로 능력치·보상이 바뀌지 않도록 출발 시 정의를 고정한다.

여러 마리 전투는 기존 `dungeon_maze_battle`에 개체별 행을 저장한다. 첫 행이 전투 묶음의 ID이며 나머지 행의 `encounter_id`가 첫 행을 참조한다. 각 개체는 독립 HP·상태·행동 순서·위협도를 갖고 `monster_runtime`의 `maze:<battle_id>`를 사용한다. 참가자의 제출은 첫 행의 `battle_id`로 턴당 한 번 저장하고, `dungeon_maze_action.monster_id`에 선택한 개체를 저장한다. 전체 공격은 살아 있는 모든 개체, 무작위 공격은 그중 한 개체에 적용하며 자원은 한 번만 소모한다. 모든 개체를 처치해야 승리하고, 도주·전멸·관리자 종료는 전투의 모든 개체를 종료한다. 기존 단일 전투는 `encounter_id=0`, 출현 수는 1로 유지된다.

`maze_battle_group()`은 읽기 전용 조회에서도 필요한 함수이므로 `extend/dungeon_maze.lib.php`에 둔다. `dungeon/maze_state.php`는 전투 확장의 설치·갱신 부작용 없이 상태를 읽는다. 전투 상태 조회의 HTTP 응답과 버전별 갱신을 함께 검증한다.

치유 로그는 실제 회복량을 기록한다. 스킬의 HP·MP 등 비용과 HP 스탯의 사본은 상태 변화로 계산하지 않으며, 실제 강화·상태 효과가 적용된 경우에만 별도의 `STATUS`를 기록한다.

MMB 호환용 `k_battle_skill_mo`·`k_battle_pattern_mo`는 기존 K 패턴이 읽는다. 1:1의 `battle_log`도 실행 코드가 사용한다. 행 수가 0이라는 이유로 삭제하지 않는다.

## HP 구간별 행동

관리자가 HP 기준과 각 단계의 일반 공격·보유 스킬 순서를 지정한다. 기준 이하가 된 다음 몬스터 턴에 해당 단계의 첫 행동부터 반복하고, 회복해도 이전 단계로 돌아가지 않는다. 몬스터는 MP·재사용 대기 제한 없이 정해진 행동을 실행한다. 기존 실행 스냅샷에 남은 MP·대기 값도 실행을 막지 않는다. 참가자의 MP·스킬 재사용 대기는 유지한다.

던전 탐험·실시간·MMB에서 같은 스케줄러를 사용한다. MMB의 관리자 행동에서 **등록된 몬스터 패턴 실행**을 선택하면 공용 패턴을 한 턴 실행한다. 턴 커서는 기존 `monster_runtime.state.mmb_turn`에 저장하며 별도 테이블은 없다. 기존 수동 전체/무작위 공격도 유지한다.

HP 구간별 순서의 제목은 다음 단계 기준까지의 구간을 표시한다. 기준을 100·50·30%로 설정하면 첫 단계는 `50% < HP ≤ 100%`, 두 번째는 `30% < HP ≤ 50%`, 마지막은 `0% < HP ≤ 30%`다. 정확히 50%에서 두 번째, 30%에서 세 번째 단계로 전환하며 이전 단계 순서는 중단한다. 하한은 다음 단계 기준에서 계산하므로 별도로 저장하지 않는다.

턴 조건 스킬도 **HP 상한(이하)·하한(초과)**을 지정할 수 있다. `monster_master.profile.shared.skills`의 참조에 `hp_below`와 `hp_above`를 저장한다. 상한 100·하한 50은 정확히 50%가 되면 중단하고, 상한 50·하한 30은 정확히 50%부터 실행하다 30%에서 중단한다. 하한이 없는 기존 정의는 0%로 읽는다. 새 구간 설정은 몬스터가 실제 행동할 때의 HP를 다시 확인하므로 전투 예고 이후 HP가 내려가도 구간 밖 스킬은 실행하지 않는다. 턴 조건 방식은 회복하면 현재 HP에 맞는 구간이 다시 적용되고, HP 단계 방식은 한 번 넘어간 단계를 유지한다.

턴 조건의 **설정한 턴마다 반복**은 전체 전투 턴을 기준으로 한다. HP 조건과 턴 조건을 만족하는 스킬이 겹치면 **사용 턴 값이 큰 스킬 하나만** 실행한다. 같은 값이면 먼저 등록한 스킬이 우선이다. 1턴·상한 100%·하한 0%의 몸통박치기와 4턴·상한 35%·하한 0%의 재생을 등록하면, HP 35% 이하인 전체 전투의 4·8·12턴에는 재생만 실행하고 다른 턴에는 몸통박치기를 실행한다. HP 35%를 넘으면 해당 턴에도 몸통박치기를 실행한다. 주기 4·6이 12턴에 겹치면 주기 6이 우선이다. 턴 순서대로 반복(`CYCLE`)에서도 한 턴에 한 기술만 실행하고 같은 칸은 등록 순서로 선택한다. 진행 중 런타임의 과거 다중 행동 예고도 조회·실행 시 이 규칙으로 다시 계산한다. HP 구간별 순서는 단계마다 한 행동씩 실행하므로 35% 단계에 재생 한 칸만 두면 매턴 재생한다. 관리자가 저장한 설정 변경은 새로 시작하는 전투의 스냅샷에 반영한다.

## 던전 발생

`extend/dungeon.lib.php::set_reset_dungeon()`은 발생 구간을 만족하는 사용 중 던전이 연결된 지역만 선택한다. 빈 지역은 발생 수를 소비하지 않는다. 기존 미입장 발생 종료·신규 발생·갱신 시간을 한 트랜잭션으로 처리한다. 동시 요청은 발생 잠금으로 중복 생성하지 않는다.

진단은 `php dev/tools/inspect_dungeon_spawns.php`로 읽는다. 이전 오류로 이번 주기에 한 건도 생성되지 않은 경우 `php dev/tools/repair_missing_dungeon.php`로 확인하고 `--apply`로 재시도할 수 있다. 적용 전 전체 DB 백업을 만들며 현재 주기의 갱신 시각을 유지한다. 이미 생성된 건이 있으면 종료·클리어 상태여도 재생성하지 않는다.

맵 표시와 지역 입장 제한은 별도로 판단한다. `map/_common.php`의 `$show_dungeon_map`은 `cf_dungeon_open`을 읽고, `extend/dungeon.lib.php`의 `$use_dungeon_map`은 `cf_dungeon_open && cf_dungeon_map`을 읽는다. 지역 사용을 끄더라도 이미 지역에 열린 던전은 표시하고, 지역 없는 신규 발생은 모든 지역의 행동 목록과 전체 지도 입장 링크에서 찾을 수 있다. `get_map_open_dungeons()`는 열린 상태이고 사용 중인 정의만 읽는다. 한 지역에 진행 중 던전과 새 대기실이 함께 있으면 모두 표시하며, 이미 출발한 파티의 참가자는 이어하기, 나머지는 진행 중 안내를 본다.

상위 지역은 `ma_parent=0`과 `ma_parent=ma_id` 두 형식을 모두 지원한다. 전체 지도에는 하위 지역 발생을 상위 지역 표시로 전달하고, 상위 지역의 상세 화면에도 목록을 표시한다. 맵과 관리자 화면이 다르게 보이면 기능 사용값·발생 지역·`ds_state`·탐험 `phase`를 함께 확인한다. `S`라도 이미 출발한 탐험은 신규 참가할 수 없고, 클리어한 발생은 다음 정기 주기까지 다시 열리지 않는다.

맵과 던전 목록의 **입장하기**는 토큰을 포함한 POST `maze.php`의 `join`을 실행한 뒤 대기실로 이동한다. 캐릭터 소유·지역·인원·기존 참가 여부는 서버에서 검사한다. **미궁 이어하기**는 기존 참가자로 GET 조회한다. 과거 입장 URL의 GET은 참가를 생성하지 않고 오류 없이 입장 안내를 보여 준다.

## 관리자 편집 흐름

던전 편집은 **기본 설정·몬스터 출현·탐험 설정·보상 설정** 네 탭을 자유롭게 이동하고 한 번에 저장한다. 상단 요약과 단계별 이전·다음 버튼은 사용하지 않는다.

- 기본 설정: 이름·소개·최대 인원·난이도를 입력한다. 지역과 발생 추첨 구간은 접힌 상세 설정에서 편집한다.
- 몬스터 출현: 이 던전에 등록한 몬스터만 표시한다. **몬스터 추가**로 공용 목록을 열어 이름으로 찾아 추가하고, **제외**로 이 던전의 출현 설정에서 뺀다. 공용 원본은 유지한다. 가중치 1:3이면 같은 종류 후보 중 25%:75%로 선택된다. 일반 몬스터와 보스는 따로 계산한다. 종류를 선택한 뒤 **최소·최대 출현 수**(1~20마리)에서 무작위로 마릿수를 정한다. 2·2는 항상 2마리, 1·3은 1~3마리다. 보스에도 적용되며 해당 몬스터를 지정한 이벤트도 같은 출발 스냅샷의 출현 수를 사용한다. 출현 목록에 없는 몬스터는 이벤트에서도 등장하지 않는다.
- 탐험 설정: 최소 출발 인원·방 수·전투 출현 확률·보스 여부를 먼저 설정한다. 갈림길·방 가중치·함정 등은 고급 설정을 펼쳐 편집한다. 방 가중치와 전투 출현 확률은 서로 다른 추첨이다.
- 보상 설정: 참가자별 클리어 포인트와 아이템 수량·확률을 설정한다. 예를 들어 2개·30%는 아이템 2개를 30% 확률로 지급한다. 각 아이템은 독립 추첨하므로 확률의 합은 100%일 필요가 없다.

`adm/js/dungeon_form_editor.js`는 탭·몬스터 추가/제외·검색·적용 예시·입력 오류 안내·미저장 상태를 관리한다. 이벤트 편집은 `adm/js/dungeon_admin.js`, 몬스터 HP 순서와 실제 전환 체력 예시는 `adm/js/monster_phase_editor.js`가 관리한다. PC에서는 기존 관리자 표 양식을 사용하고, 휴대폰에서는 각 항목의 제목 아래에 입력 칸을 배치한다. 저장 버튼은 화면 아래에 유지한다. 설정·출현·보상 저장 위치와 트랜잭션 계약은 위의 공용 구조를 사용한다.

방 이벤트의 몬스터 선택창은 출현 탭의 추가·제외를 즉시 반영한다. 제외된 기존 선택은 안내와 함께 표시하되 다시 선택할 수 없다. 사용 중인 이벤트를 수정하거나 사용을 끄면 저장할 수 있다. 입력 오류가 있으면 해당 탭과 접힌 상세 항목을 펼친다.

설정과 실제 출현 경로는 `php dev/tools/inspect_dungeon_encounters.php`로 읽는다.

## 이전과 검증

현재 계약은 던전 **1.2.0**, 몬스터 **1.8.0**, 탐험 **1.7.0**이다. 이전 코드는 `lib/dungeon_unify_migration.lib.php`에 있다. 1.7 이전에 사용하던 출현 정원 필드는 동시 마릿수로 해석하지 않고 1·1로 이전하여 기존 한 마리 전투를 유지한다. 새 마릿수는 다음 출발부터 반영한다.

- 기존 탐험 설정·출현·보상이 공용 저장소로 이동한다. 활성 미궁 보상을 우선하고 구형 획득 구간은 실제 확률 `(끝-시작+1)/101`로 변환한다.
- `dungeon_maze_session`의 진행 데이터는 같은 `ds_id`의 `dungeon_state`로 이동한다. 참가자·보관 아이템·지도·정산 기록은 유지한다.
- 이전 검증 후 `dungeon_maze_config`, `dungeon_maze_session`, `dungeon_monster_config`, `dungeon_state_monster`, `dungeon_member`, `dungeon_log`를 삭제한다. 폐기된 즉시 전투의 기록·런타임도 정리한다.
- 설정 저장은 `dungeon`, `dungeon_item`, `dungeon_monster_pool`을 한 InnoDB 트랜잭션으로 묶는다.

주요 검증:

```text
php dev/tests/system_installer_integration.php
php dev/tests/dungeon_unify_storage_integration.php
php dev/tests/monster_pool_schema.php
php dev/tests/combat_integration.php
php dev/tests/monster_phase_integration.php
php dev/tests/monster_hp_range_core.php
php dev/tests/monster_turn_priority_core.php
php dev/tests/dungeon_spawn_integration.php
CODEX_DUNGEON_TEST_PORT=33381 php tests/dungeon_inventory_integration.php
node dev/tests/dungeon_exploration_unified_browser.cjs
node dev/tests/map_dungeon_browser.cjs
node dev/tests/dungeon_event_pool_browser.cjs
node dev/tests/admin_ux_browser.cjs
node dev/tests/admin_creation_ux_browser.cjs
node dev/tests/monster_phase_admin_browser.cjs
node dev/tests/monster_hp_range_browser.cjs
node dev/tests/monster_shared_browser.cjs
```

DB 테스트는 `dev/tools/system_test_db.ps1`의 격리 서버에서만 실행한다. 실제 DB 변경 전에는 전체 백업을 만들고, `php dev/tools/audit_game_storage.php`로 전후 현황을 읽는다. 이전 스키마 JSON은 과거 DB 이전 테스트를 위한 계약이며 현재 설치는 `install/system/modules.php`의 최신 버전만 사용한다.

현재 설치 목록과 실제 DB의 폐기 테이블·구형 컬럼·공용 조회 뷰·모듈 버전은 `php dev/tools/audit_system_install_storage.php`로 읽기 점검한다. 관리자 페이지를 통한 전 모듈 설치와 반복 실행은 새 격리 웹 fixture에서 `python dev/tests/system_installer_http.py --full-storage`로 검증한다. 기본 명령은 기존 브라우저 테스트를 위해 기본 확장만 설치한 상태로 끝난다. 2026-10-05 1차 마무리 내역은 `docs/dungeon-combat-phase1-check.md`에 있다.

동시 출현 업데이트는 관리자 시스템 설치에서 몬스터 → 던전 탐험 순서로 적용한다. CLI는 `php dev/tools/update_dungeon_encounters.php`로 읽기 점검하고 `--apply`로 전체 백업 후 적용한다. 이미 최신이면 쓰기 없이 종료한다.

`dungeon_maze_unified_browser.cjs`, `dungeon_pool_embedded_browser.cjs`, `maze_horizontal_browser.cjs`, `combat_finish_browser.cjs`는 현재 통합 탐험 테스트로 연결하는 이전 명령이다. 구형 즉시 전투·출현 수 제한 테스트와 일회용 수정 스크립트는 제거했다.
