# 미궁 인벤토리 사전 점검 및 승인 후 반영 — 2026-10-02

## 현재 상태

미궁 기능과 승인된 외부 소비 경계를 구현했다. 운영 DB에 ALTER/CREATE/UPDATE/DELETE를 실행하지 않았다.
격리된 임시 MariaDB에서만 migration과 통합 테스트를 수행했다. 최종 범위·검증·배포 절차는 `dungeon-implementation-report.md` 참고.
로컬 설정 DB 연결은 실패했으므로 현재 운영 schema/engine/version은 미확인이다.
이 문서는 저장소 코드의 증거와 필요한 설계 결정을 기록한다.

## 승인된 범위

- 기존 map 출현과 dungeon_state.ds_id 유지.
- 신규 미궁 상태는 InnoDB에 저장. 기존 member/point 등 핵심 테이블 전환 금지.
- inventory 엔진은 실제 확인 후 필요한 경우에만 수동 InnoDB migration.
- WAITING은 아이템 이동/잠금 없음. 출발 확정 transaction에서만 격리.
- 던전 사용 가능하고 미장착인 원본 행을 전체 보존. 장비 테이블에 참조되는 행은 제외.
- 외부 신규 획득분은 일반 inventory에 남김. 모든 종료 경로에 공통 1회 정산.
- 포인트는 결정적인 relation key 및 지급 저널. 기존 insert_point()의 uniqid 방식 사용 금지.

## 저장소 inventory 구조

install/gnuboard5.sql:1024의 기본 컬럼:

```
in_id (INT AUTO_INCREMENT, PRIMARY KEY)
it_id, it_name, it_rel
ch_id, ch_name
se_ch_id, se_ch_name, re_ch_id, re_ch_name
in_sdatetime, in_edatetime, in_memo, in_use
in_1, in_2, in_3, in_4, in_5
```

기본 인덱스는 PRIMARY(in_id)와 중복 비고유 KEY in_id(in_id).
기본 설치에는 FULLTEXT/FK가 없다. 기본 엔진은 MyISAM이다.
adm/993_quest_config.php는 qu_id 컬럼을 추가하며 quest 보관 테이블을 CREATE TABLE LIKE로 만든다.
운영의 추가 컬럼/인덱스/trigger는 코드만으로 확인할 수 없다. 따라서 고정 it_id/count 스냅샷으로는 부족하다.

## in_id 참조와 격리 조건

- k_battle/extend/install/database_plugin.sql의 k_battle_equip_ch.in_id: 별도 행에
  eq_use, ug_id, st_1..st_10, eq_memo 등 장비 개별 상태를 보관한다.
  eq_use가 비어 있어도 참조 행이 있으면 격리에서 제외해야 한다.
- extend/unified_stat.lib.php, k_battle 장비 조회/강화/커스텀 경로가 inventory와 위 테이블을 조인한다.
- extend/quest.lib.php는 inventory 전체 컬럼을 동적으로 조회하여 별도 quest 보관 테이블로
  INSERT SELECT/DELETE하고 원본 ID를 포함해 되돌린다. 이 이동 경로도 동시성 범위에 포함된다.
- 기존 전투/게시판 로그와 폼은 in_id를 사용한다. 같은 ID 복원은 복원 시 PK 충돌을 검사해야 한다.
- AUTO_INCREMENT의 삭제된 최댓값 재사용 여부는 실제 DB 제품/버전/재시작 동작 확인이 필요하다.
  복원 충돌 시 INSERT IGNORE/REPLACE로 진행하면 안 된다. 보관 원본을 남기고 정산을 실패시켜야 한다.

현재 던전 UI의 사용 조건은 inventory.in_use = '' AND item.it_use_battle_able = 1이다.
새 조건은 로그인 소유 캐릭터 검증 및 장비 참조 행 제외를 추가해야 한다.

## Engine 의존 코드 조사

inventory에 대한 LOCK TABLE(S), INSERT DELAYED, DISABLE/ENABLE KEYS,
REPAIR TABLE 의존 경로는 검색한 프로젝트 PHP/SQL에서 발견하지 못했다.
lib/common.lib.php:get_uniqid()의 LOCK TABLE은 uniqid_table 대상이며 inventory 대상이 아니다.
실제 환경의 trigger/FK/별도 애플리케이션 쓰기는 별도 점검이 필요하다.

## 확인된 출발 경계의 동시성 충돌

inventory/sell_item.php:

1. in_id로 inventory를 일반 SELECT.
2. insert_point()로 판매 보상 지급.
3. delete_inventory() 실행. 실제 삭제 행 수를 검증하지 않음.

동시 실행 사례:

```
판매 요청: 원본 X를 읽음
미궁 출발: X를 잠그고 보관 테이블로 복사 → inventory에서 삭제 → COMMIT
판매 요청: 판매 포인트 지급 → X DELETE (0행) → 성공 응답
```

원본 X의 던전 사용 권리와 외부 판매 보상이 동시에 남는다.
출발의 SELECT FOR UPDATE는 다른 연결의 일반 SELECT를 강제로 잠금 읽기로 바꾸지 않는다.
판매가 먼저 X를 읽은 요청도 되돌릴 수 없다. inventory만 InnoDB로 바꿔도 해결되지 않는다.
inventory/use_item.php는 회복/뽑기 효과를 먼저 적용하므로 같은 문제가 생긴다.
quest 제출은 수량 확인 후 DELETE의 실제 수량을 검증하지 않고 보상을 계속한다.
이 문제는 잘못된 소유권 요청 없이 같은 소유자의 두 요청만으로 발생할 수 있다.

## 변경이 필요한 실제 경로

원안을 보존하려면 격리 대상과 겹치는 원본을 외부에서 처리하는 요청이
아이템을 읽고 효과를 적용하기 **전**부터 같은 행의 소유권/존재 및 소비를 확정해야 한다.
delete_inventory()의 마지막 DELETE에 검사 한 줄만 넣는 것으로는 이미 지급된 효과를 취소할 수 없다.

| 영역 | 경로 | 경계 |
|---|---|---|
| 사용/판매 | inventory/use_item.php, inventory/sell_item.php | 효과/포인트 지급 전 |
| 전송/아이템 생성 | inventory/inventory_update.php, inventory/item_form_update.php | 장비 해제/소유권 변경/새 아이템 생성 전 |
| 상점 교환 | shop/_ajax.shop_update.php | 요구 재료 확보 후 상품/포인트 처리 |
| 퀘스트 제출/보관 | extend/quest.lib.php | 수량 소비 및 보관 이동 확정 전 |
| NPC 선물 | npc/proc/present.php | 원본 확보 후 호감도 등 효과 처리 |
| 기존 던전/레이드 | dungeon/proc/item_use.php, k_battle/ajax/action.inc.php, k_battle/extend/raid/1_skill.php | 회복 등 효과 적용 전 |
| 게시판 사용/제작 | skin/board/mmb*, skin/board/k_quest, skin/board/k_mmbraid, mmb_battle/write_update.inc.php | 선택 아이템/재료 일괄 확보 전 |
| 강화/커스텀 재료 | k_battle/extend/raid/0_default.php, k_battle/ajax/equip.php | 재료 확보 전; 장비 자체는 격리 제외 |
| 기타 소모 | room/room_add_update.php, skill/skill.upgrade.php, mypage/character/character_form_update.php | 해당 타입이 격리 후보와 겹치는지 검증 |

관리자 아이템/캐릭터 삭제는 별도 관리 동작이며 활성 보관분의 삭제 정책도 필요하다.
일반 화면 조회와 단순 상점 신규 구매에 예약 검사를 추가할 필요는 없다.

## 최초 보안 발견과 승인 후 반영

- inventory/use_item.php:14 — 요청자와 원본 소유자 관계를 검사하지 않고 행의 캐릭터에 효과 적용 가능.
- inventory/sell_item.php:10 — in_id만 조회하고 요청자에게 판매 포인트를 지급한 뒤 해당 행 삭제.
- dungeon/proc/item_use.php:7 — 현재 던전 참가자는 검사하지만 사용 아이템 소유자 관계는 검사하지 않음.
위 세 소비 경로는 후속 승인에 따라 공통 소비 함수의 소유권 검증으로 보완했다.
신규 미궁 endpoint는 로그인 회원 → 소유 캐릭터 → 참가자 → 보관 행 소유자를 검증한다.
전체 레거시 보안 감사는 수행 범위가 아니며, 남은 범용 helper/CSRF 제한은 최종 보고서에 기록했다.

## 제공한 점검 도구와 migration

```
php install/dungeon/inventory_preflight.php --help
php install/dungeon/inventory_preflight.php
```

common.php를 로드하지 않으므로 확장 라이브러리의 자동 DDL을 실행하지 않는다.
schema/engine/index/FK/trigger 이름/서버 버전을 읽기만 한다. 행의 실제 아이템 데이터는 출력하지 않는다.
접속 실패는 종료 코드 1, 설정 오류는 2. 출력 성공은 미궁 readiness 통과를 의미하지 않는다.

001_inventory_engine.manual.sql은 기본값이 점검 전용이다. 현재 engine이 InnoDB이면 ALTER하지 않는다.
백업/복구 확인과 쓰기 중단 후 사용자가 opt-in했을 때만 MyISAM을 변환한다.
FULLTEXT/trigger/알 수 없는 engine은 자동 추측하지 않고 차단한다. 실행하지 않았다.

## 후속 승인 반영

사용자가 실제 소비·소유권 변경 경계의 최소 수정을 승인했다. 공통 owner guard와
`in_id ASC FOR UPDATE`, 소비 저널을 연결했다. WAITING은 행을 격리하지 않고 출발만 격리한다.
장비 타입 `장비(K)` 및 장비 테이블 참조가 있는 모든 행을 제외한다.
신규 획득·조회·목록 화면에는 예약 검사를 추가하지 않았다.

원본 ID 복원은 persistent AUTO_INCREMENT가 있는 MariaDB 10.2.4+ / MySQL 8.0+ 환경에서만
새 미궁 readiness를 통과한다. 실제 운영 버전은 아직 확인하지 못했다. 구형 DB에서는 자동 우회하지 않는다.
원본 ID 충돌은 전체 정산 rollback 및 보관 원본 유지로 처리한다.
