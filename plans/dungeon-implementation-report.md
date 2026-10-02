# 미궁 구현·검토 보고서

작성: 2026-10-02. 기준: 첨부 명세 0~150 및 후속 승인(별도 보관, inventory만 수동 engine migration, 실제 외부 소비 경계 최소 수정).

## 적용 상태

코드와 수동 migration을 작성했다. 운영 DB에는 적용하지 않았다. 운영 DB의 실제 engine/schema/버전, 추가 trigger 및 별도 플러그인은 미확인이다. PHP 7.4.33 / 격리한 MariaDB 10.4.27에서 백엔드 통합 검증을 수행했다. 사용자의 지시에 따라 E2E/UI 자동 테스트는 실행하지 않았다.

배포 전 `dungeon-deployment.md` 순서를 따라야 한다. migration 없이 코드를 먼저 활성화하면 수정된 소비 경로도 안전하게 실패하므로, 쓰기 중단 상태에서 함께 배포해야 한다.

## 구현 위치

| 파일 | 역할 |
|---|---|
| extend/dungeon_maze.lib.php | schema readiness, SQL 공통 함수, 생성기, 참가 권한, deadline, 기존 출현 상태 연계 |
| extend/dungeon_maze_session.lib.php | 설정 검증, 입장/READY/출발, 만료, 공통 종료, 관리자 처리 |
| extend/dungeon_maze_actions.lib.php | 상태별 행동 권한, 이동/이벤트/채팅, 퇴장/강퇴 투표 |
| extend/dungeon_maze_combat.lib.php | 행동 제출/수정, turn resolve, 스킬·몬스터·상태·부활 |
| extend/dungeon_maze_inventory.lib.php | 원본 격리, 내부 획득품, 참가자별 정산, 포인트 저널 |
| extend/dungeon_formula.lib.php | 기존 get_status_dungeon의 계산식만 분리한 공통 계산 |
| extend/inventory_boundary.lib.php | 소유권/행 잠금/다중 소비/이동/복구 저널 |
| dungeon/maze.php | 대기실·탐험·전투·지도·채팅·결과 화면, 단일 POST adapter |
| adm/dungeon_maze.php | 던전 설정, 전역 제한시간, 상태/아이템 효과, 세션 조회·강퇴·종료 |
| adm/inventory_journal.php | 미완료 소비 결과 확인·보정 |
| install/dungeon/* | 읽기 전용 점검 CLI와 4개 수동 migration |
| tests/dungeon_inventory_integration.php | 운영 DB 접근을 거부하는 격리 통합 테스트 |

기존 map/출현 및 ds_id를 유지한다. 기존 참가 기록이 있는 인스턴스는 기존 전투 경로를 유지한다. 새 미궁은 기존 `dungeon/proc/*`를 호출하지 않으며 기존 proc에서도 새 미궁 ds_id를 차단한다. 던전 설정 삭제 후에도 세션 snapshot으로 진행·기록을 보존한다.

기존 연동 변경: `extend/dungeon.lib.php`, `dungeon/applicate.php`, `ground.php`, `index.php`, `proc/_common.php`, `map/inc/btn_gate_app.php`, `map/map_actions.php`, `mypage/log/dungeon.php`, 관리자 메뉴 500/730, `adm/dungeon_state_list_update.php`. 기존 인스턴스 삭제 화면에서는 새 미궁을 삭제하지 않고 공통 종료 화면으로 안내한다.

## 요구사항 대조 — 검토 1

| 명세 범위 | 구현·확인 |
|---|---|
| 0~17 입장/대기/강퇴 | 기존 map 출현 유지, 서버 인원·READY 확인, WAITING 재입장, 출발 후 신규/이탈자 재입장 금지, 전투불능도 투표, 전원 동의·인원 변경 재계산 |
| 18~33 생성/탐험 | 출발 transaction 안에서 한 번 생성, 연결된 트리, 방·갈림길 범위, 막다른 길 가중치, 방문 지도, 방/version 검사, 채팅 분리 |
| 34~45 이벤트/조우 | 클릭 시 조사·보물·함정, 한 단계 선택지 개수 제한 없음, 생존자별 아이템, ACTOR/ALL/RANDOM, 고정 몬스터 배치, 재전투 방지 |
| 46~71 전투/부활 | 계산식 분리 재사용, 제출/수정과 resolve 분리, 서버 deadline, 무제한 전원 제출 자동 resolve, 제한시간 스킵, dm_id 순서, 도주 우선, 부활자 다음 턴부터 행동 |
| 72~89 보관/상태 | 전체 원본 행 보존, 원본/내부 획득 구별, 외부 신규 획득 유지, 공통 1회 정산, 상태 4종 조합·갱신·치료 |
| 90~99 클리어/실패 | 생존자만 독립 보상 추첨, 결정적 포인트 키, 전원 전투불능/마지막 생존자 이탈 실패, 보스 사망 즉시 BOSS_RESULT |
| 100~112 관리자/기록 | 던전·몬스터·이벤트·상태 관리, 전체 미궁 조회, 강제 퇴장/성공·실패 종료, 참가/전투/채팅/보상 기록 유지, 마이페이지 상세 연결 |
| 113~125 DB/보안/UI | 전용 InnoDB, 공통 검증, 원본 소유권, 직접 호출 방어, CSRF, manual migration, UI 자동 테스트 미실행 |
| 127~146 DoD/기존 설정 | 출발 시 설정·스킬·스탯·상태·보상 snapshot, 전역 제한시간 다음 턴 적용, 출현 갱신/비활성화로 시작한 미궁 종료하지 않음 |

관리자 고급 몬스터/이벤트/보상은 JSON 편집 UI로 제공한다. 예시는 배포 문서 참고. 명세 107의 선택 사항인 관리자 테스트 전용 spawn은 추가하지 않았다. 기존 출현 정책을 이용한다.

## 상태 전이 — 검토 2

- WAITING → 최종 인원/READY/출현 검사 → 소유자 guard → 스탯/아이템 snapshot 및 방 생성 → EXPLORE. 중간 SQL 실패는 모든 신규 데이터와 원본 삭제 rollback.
- EXPLORE → 이동/이벤트 → BATTLE 또는 출구 CLEAR. 동일 version 재요청은 거절.
- BATTLE → 제출 upsert → resolve → 다음 턴/BATTLE, EXPLORE, BOSS_RESULT, FAILED.
- BOSS_RESULT에서는 채팅과 생존자의 탈출만 허용. 아이템·부활·탐험·플레이어 퇴장/투표는 차단. 보스 사망 턴의 후속 지속 피해도 적용하지 않음.
- CLEAR/FAILED/CLOSED는 플레이어 행동 불가. 참가자 settled 상태와 원본 보관 정보 유지.
- WAITING 만료는 최대 100개씩 지연 정리. 시작된 세션은 만료 대상 아님.
- 관리자 종료는 같은 정산 함수 사용. 반복 종료는 추가 복원/보상 없음.

일반 도주 규칙은 보스 encounter에도 동일하게 적용한다(첨부 57~61에는 보스 예외가 없음). 보스를 처치한 경우에는 최종 보스 규칙을 적용한다.

## 동시성·권한 — 검토 3

잠금 순서: 세션 행 → 해당 파티의 ch_id 오름차순 owner guard → in_id 오름차순 inventory 행. 외부 소비는 owner guard → inventory 행. 사이트 전체/global/table lock은 추가하지 않았다.

- 외부 소비 먼저: 원본 소비 확정 후 효과 실행. 아직 효과 처리 중이면 durable guard가 해당 캐릭터 출발을 차단. 완료 후 출발은 소비된 행을 포함하지 않음.
- 출발 먼저: 원본이 보관으로 이동하므로 뒤늦은 외부 소비의 잠금 조회 실패. 효과·포인트·제작 완료 처리로 진행하지 않음.
- 다중 소비: 모두 확보/소유권 확인 후 삭제. 일부 누락·중복 ID·타인 ID면 아무것도 소비하지 않음.
- 전송: 송신/수신 guard 유지 후 소유권 변경. 출발과 동시에 동일 원본 사용 불가.
- 모든 미궁 변경은 ds_id 행 잠금으로 직렬화. 행동 키 `(battle_id,turn_no,dm_id)`, 참가 키 `(ds_id,ch_id)`, 보상 키 `(ds_id,dm_id,reward_type)`.
- 원본 복원은 INSERT만 사용. ID 충돌에 REPLACE/IGNORE하지 않고 정산 전체 rollback, 원본 보관 유지.
- 신규 POST는 별도의 세션 CSRF token/hash_equals 사용. 로그인 회원→현재 소유 캐릭터→ACTIVE 참가자→아이템 ds/dm/ch 소유자를 서버 검증. 화면 입력만으로 권한을 인정하지 않음.
- item effect와 소비가 모두 InnoDB인 일반 회복은 한 transaction. MyISAM 효과는 원본/의도를 저널에 먼저 남긴 뒤 소비 확정, 효과 완료 후 DONE. 미완료는 REVIEW와 owner guard를 남겨 중복 실행 방지.
- 포인트는 `insert_point(...,'@maze',dm_id,'clear:'+ds_id)` 사용. uniqid 없음. 새로고침/중복 요청은 동일 relation key. MyISAM의 포인트 기록/회원 합계 사이 프로세스 강제 종료까지 완전 ACID라고 주장하지 않음.

## 외부 소비 경로 분류 — 검토 4

| 분류 | 실제 경로 | 반영 |
|---|---|---|
| 수정 필요: 사용/판매/전송/추가권 | inventory/use_item.php, sell_item.php, inventory_update.php, item_form_update.php | 소비/보유 확보 후 효과, 실제 소유권 검증 |
| 수정 필요: 확장 아이템 | inventory/extend/status_extra.inventory.php, skill.inventory.php, room.inventory.php, k_battle_plugin.php | 공통 경계 |
| 수정 필요: 기존 전투 아이템 | dungeon/proc/item_use.php, k_battle/ajax/action.inc.php, k_battle/extend/raid/1_skill.php | 효과 전 소비 |
| 수정 필요: 강화/커스텀 | k_battle/ajax/equip.php, k_battle/extend/raid/0_default.php | 재료 소비 + 대상 장비 보유 행 잠금 |
| 수정 필요: 상점 재료/퀘스트 | shop/_ajax.shop_update.php, extend/quest.lib.php | 재료 일괄 확보, 원본 quest 이동도 저널 보존 |
| 수정 필요: 게시판/제작 | skin/board/{mmb,mmb_produce,mmb_battle,k_quest,k_mmbraid}/write_update.inc.php, mmb_battle/write_update.inc.php | 사용/다중 재료 경계 |
| 수정 필요: 기타 실제 소비 | npc/proc/present.php, room/room_add_update.php, skill/skill.upgrade.php, mypage/character/character_form_update.php | 기존 효과는 유지, 확보 경계 연결 |
| 수정 필요: 관리자/캐릭터 삭제 | adm/inventory_list_delete.php, item_list_update.php, 980_k_equip_list_update.php, character_delete.php, character_list_update.php, mypage/character/character_delete.php | 삭제 범위 확보, 활성 보관분/참가자 삭제 차단 |
| 수정 불필요: 장착 표시/장착 변경만 | 장비 테이블만 갱신하는 경로 | 장비 타입 및 장비 참조 원본 모두 격리 제외. 장비 스탯 계산 유지 |
| 수정 불필요: 읽기/신규 획득 | inventory 목록·검색·설명, 단순 구매/신규 보상 insert_inventory | 소비 경계 추가 안 함 |
| 수정 불필요: 메타데이터 | adm/993_quest_config_update.php의 qu_id 해제 | owner/소비가 아닌 기존 메타데이터 초기화. 진행 중 보관 원본에는 소급하지 않음 |
| 실행 경로 아님 | inventory/extend/_sample.config.php, skill/skill.del.php 주석 | 기존 샘플/주석 유지 |

`extend/item.lib.php`의 범용 delete_inventory는 이번에 확보된 원본의 중복 삭제를 건너뛰는 보조 처리만 추가했다. 신규/외부 플러그인은 이 helper만 호출하면 안전해지는 것이 아니며, 효과 **전** 공통 소비 경계를 사용해야 한다. `lib/common.lib.php`는 소비 저널이 활성인 요청의 SQL 실패만 예외로 전달해 성공으로 오인하지 않게 변경했다.

### 별도 보안 기록

기존 `inventory/use_item.php`, `sell_item.php`, `dungeon/proc/item_use.php`의 in_id 소유권 누락은 승인된 소비 경계에서 보완했다. 그러나 프로젝트 전체의 오래된 SQL 문자열 구성·CSRF·확장 플러그인 보안까지 해결했다고 보장하지 않는다. 특히 기존 `check_token()`이 검증을 수행하지 않는 구조는 남아 있다. 이번 신규 미궁/저널 관리 화면은 별도 token 검증을 사용한다. 기존 범용 delete_inventory 또는 장비 전용 endpoint의 독립 권한 문제는 전체 보안 리팩터링 범위에 포함하지 않았다.

## 성능 검토

- WebSocket/daemon/queue/Redis/짧은 polling 추가 없음. 브라우저 초 표시만 로컬 timer, deadline 도달 시 해당 화면에서 POST 한 번.
- 방/몬스터는 출발 때만 생성. refresh에서 전체 미궁 재계산 없음. 일반 화면에는 방문 방만 조회, 전체 미궁은 관리자 조회만.
- readiness는 요청 내 캐시. 신규 미궁 schema는 information_schema 배치 조회. 부분 engine/schema/필수 index 누락 시 활성화 안 됨.
- 생성 방/격리 아이템/복원 아이템은 최대 100행 단위 INSERT. 방 수는 3~500 설정 범위. 보관은 전체 inventory 복제가 아니라 사용 가능 미장착 행만.
- 던전 목록의 새 미궁 상태·참가자는 일괄 조회하여 세션별 추가 N+1을 제거했다.
- 아이템 정의/장비 참조/효과는 출발 시 배치 조회. 클리어 아이템 정의는 출발 snapshot. 동일 이벤트의 반복 효과 조회는 요청 캐시.
- 상태·스킬은 참가 snapshot 사용. 기존 unified_stat 계산은 출발 시 수행하며 매 턴마다 character 전체를 재계산하지 않음.
- 플레이어 로그 50개 cursor, 이력 20개 cursor, 관리자 최근 세션 50개/로그 100개. 종료 후 자동 요청 없음.
- 신규 party/room/battle/escrow/log/history/daily/reward 접근 index 제공. 기존 inventory ch_id 선두 index는 수동 004에 분리.
- 잠금은 같은 세션/캐릭터에 한정. 실제 재고가 매우 많으면 출발·정산 transaction 시간과 보관 크기는 그 재고에 비례한다. 운영 데이터량에 대한 처리 시간/QPS 수치는 측정하지 않았다.
- 의미 없는 클릭 로그는 저장하지 않음. 별도 archive 중복 테이블 없음. 원본 보관/정산 기록은 장애 복구·이력 목적의 필요한 보존 데이터.

## 검증 결과 및 한계

PHP 7.4.33 문법 검사 56개 파일, 격리 MariaDB 통합 테스트 54개 PASS를 확인했다. 수정 후 반복 실행했으며 기존 계산식 원문과 분리한 산식의 동일성도 대조했다. 최종 테스트 로그는 `dungeon-test-results.txt` 참조. 테스트에는 실제 두 DB 연결의 소비 경쟁, 다중 소비 실패, 출발 실패 주입 rollback, 정산 충돌/중복, deadline/행동 수정, 도주·부활·보스 freeze, 관리자 중복 종료, engine/unique/index 누락 차단과 250회 랜덤 생성 불변식 검증이 포함된다.

이는 실제 운영의 모든 plugin/트리거/스키마 및 UI를 검증했다는 뜻이 아니다. 실제 운영 preflight, 백업 복구 확인, 수동 사용자 시나리오는 배포 전 필요하다. 미완료 MyISAM 효과는 자동으로 전부 환불하지 않으며 관리자가 실제 적용 결과를 확인해야 한다.
