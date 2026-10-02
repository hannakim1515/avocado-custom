# 미궁 배포 및 운영 절차

## 적용 전

1. 운영 inventory 쓰기를 잠시 중단한다. 코드와 DB 준비를 함께 배포한다. inventory/장비/quest 보관/기존 던전 및 새 테이블을 백업하고 복원 가능함을 확인한다.
2. `php install/dungeon/inventory_preflight.php` 실행. 이 CLI는 common.php를 로드하지 않고 실제 DB의 schema/engine/index/AUTO_INCREMENT/FK/trigger/버전만 읽는다. 비밀번호/아이템 행을 출력하지 않는다.
3. 저장소 기본 inventory는 MyISAM이지만 운영도 같다고 가정하지 않는다. in_id 단일 AUTO_INCREMENT PK, 모든 개별 컬럼, ch_id index, equipment 참조, quest 원본 ID 보관, 운영 추가 FK/trigger를 확인한다. 알 수 없는 참조/trigger가 있으면 적용을 멈추고 검토한다.
4. 동일 원본 ID 복원에는 재시작 후 AUTO_INCREMENT가 보존되는 DB가 필요하다. readiness는 MariaDB 10.2.4+ 또는 MySQL 8.0+를 요구한다. 구형 환경에서 서버를 자동 upgrade하거나 다른 ID로 복원하지 않는다. 근거: [MariaDB 문서](https://mariadb.com/docs/server/server-usage/storage-engines/innodb/auto_increment-handling-in-innodb), [MySQL 문서](https://dev.mysql.com/doc/refman/8.0/en/innodb-auto-increment-handling.html).
5. 운영 prefix가 avo_가 아니면 제공 SQL의 prefix를 실제 값으로 바꾼 사본을 사용한다.

## 수동 적용 순서

- `001_inventory_engine.manual.sql`: 기본값은 점검 전용(allow=0). 이미 InnoDB이면 ALTER 안 함. MyISAM이면 백업·쓰기 중단 후 opt-in=1로 수동 적용. member/point/quest 등 다른 기존 엔진을 바꾸지 않는다. FULLTEXT/trigger/알 수 없는 engine이면 별도 검토.
- `002_inventory_boundary.manual.sql`: guard/journal 두 InnoDB 테이블.
- `003_labyrinth.manual.sql`: 13개 신규 maze 테이블, 필수 index 및 meta version. 기존 데이터 migration/삭제 없음.
- `004_inventory_index.manual.sql`: inventory의 ch_id 선두 index가 없는 경우에만 opt-in으로 추가. 기존 engine은 변경하지 않음.
- 관리자 `adm/dungeon_maze.php`와 `adm/inventory_journal.php`에서 readiness 확인. engine/컬럼/unique/조회 index/meta가 빠지면 출발 차단.

SQL은 앱에서 자동 실행하지 않는다. CREATE IF NOT EXISTS는 잘못 만들어진 기존 테이블을 고쳐 주지 않는다. 부분 적용이면 SHOW CREATE TABLE과 제공 DDL을 비교해 누락만 수동 보정한다. 에러를 무시하거나 원본 inventory를 DROP/REPLACE하지 않는다.

외부 소비 경로도 001/002/004 준비가 필요하다. 준비되지 않으면 효과를 지급하지 않고 오류 응답한다. 운영 반영 후 수동 체크리스트를 통과하면 쓰기를 재개한다.

## 관리자 설정 예시

기본 dungeon의 최대 인원 dg_count는 기존 설정을 유지한다. 기본 단일 몬스터를 새 몬스터 목록의 기본값으로 이용한다. 미궁 화면에서 최소 인원/방 수/갈림길/방 종류 가중치/조우율/도주율/보스/포인트를 지정한다. 기존 보상 구간 0~100(양 끝 포함)은 독립 확률 percent로 환산해 기본값을 제공한다.

고급 설정은 JSON 배열이다. 입력 값은 서버 검증 후 저장하며 다음 출발부터 적용한다.

### 몬스터

기존 dungeon 필드가 기본값으로 채워지므로 다른 몬스터만 덮어쓸 수 있다.

```json
[{"enabled":true,"weight":10,"boss":false,"data":{"dg_mon_name":"미궁의 파수꾼","dg_mon_hp":100,"dg_d_attack_turn":1,"dg_d_attack_count":1,"dg_d_attack_min":5,"dg_d_attack_max":10}},
 {"enabled":true,"weight":1,"boss":true,"data":{"dg_mon_name":"문지기","dg_mon_hp":300}}]
```

이미지 dg_mon_img, 기존 방어/강점/약점/패턴 필드도 data에 지정 가능. status_id를 넣으면 명중 후 해당 공용 상태를 적용한다. 보스 ON이면 활성·가중치 양수인 보스 정의가 필요하다.

### 조사·보물·함정 이벤트

room_kind: EMPTY/SEARCH/TREASURE/TRAP. 결과 type: NONE/TEXT/ITEM/TRAP/MONSTER/HEAL.

```json
[{"enabled":true,"room_kind":"SEARCH","weight":10,"prompt":"상자를 살펴본다.","choices":[
  {"label":"연다","type":"ITEM","it_id":1,"count":1,"text":"작은 약병을 발견했다."},
  {"label":"지나친다","type":"TEXT","text":"발걸음을 돌렸다."}]},
 {"enabled":true,"room_kind":"TRAP","weight":1,"type":"TRAP","target":"ALL","value":10,"status_id":1,"text":"독 안개가 퍼졌다."}]
```

it_id/status_id는 실제 등록된 값으로 교체한다. TRAP value=0이면 피해 없이 상태만 적용할 수 있다. 방 타입 가중치와 이벤트 weight를 조합해 조사/함정 확률을 구성한다. 선택지는 한 단계이고 개수 제한은 두지 않았다. 내부 획득품은 각 생존자에게 지급하며 현재 미궁에서 바로 사용 가능하다.

### 클리어 보상

```json
[{"it_id":1,"count":2,"percent":30},{"it_id":2,"count":1,"percent":100}]
```

각 항목을 각 생존자에게 독립 판정한다. 실패/퇴장은 클리어 보상 없이 남은 보관품만 정산한다.

### 상태 구성

```json
[{"type":"STAT_MODIFIER","stat_id":2,"mode":"percent","value":-20},
 {"type":"DOT_HP","value":5},
 {"type":"HEAL_MODIFIER","value":-25}]
```

STAT_MODIFIER mode는 flat/percent/final. ACTION_DISABLE는 value=1. 같은 상태 재적용은 지속 턴 갱신, 별도 중첩 없음. 지속시간은 전투 턴 종료에서 감소한다. 부활/치료 아이템은 별도 효과 form에 등록하고 기본 아이템의 전투 사용 가능도 켠다.

## 장애 처리

### 새 미궁 출발/정산 실패

InnoDB transaction 실패는 rollback한다. 원본을 임의 INSERT IGNORE/REPLACE하거나 settled만 수동으로 켜지 않는다. 원본 ID 충돌이면 기존 inventory 행과 보관 원본을 비교해 충돌 원인을 먼저 해결한다. 보상 아이템 정의가 삭제된 경우에도 정산을 중단하므로 해당 정의를 복구하고 재시도한다. 진행 중인 미궁에서 참조하는 아이템 정의 삭제는 피한다. 보관 테이블/저널을 삭제하지 않는다.

### 외부 소비 REVIEW

`adm/inventory_journal.php`에서 원본·의도와 실제 포인트/회복/제작/퀘스트 결과를 비교한다.

- 효과가 전혀 적용되지 않았음이 확인됨: 원본 복원. 동일 원본 ID 충돌 시 실패하고 기록 유지.
- 효과가 적용됨: 결과를 확인하고 완료 처리.
- 일부만 적용됨: 기존 효과를 직접 보정한 후 완료. 전부 복원해 중복 효과를 만들지 않는다.
- 영구 아이템/이동/quest 반환처럼 원본을 삭제하지 않은 작업은 자동 전체 복원 금지. 실제 owner/quest 보관을 확인 후 완료한다.

일반 예외/종료는 PROCESSING→REVIEW. 프로세스 강제 종료/DB 단절은 PROCESSING이 남을 수 있다. 실제 요청이 끝났고 같은 작업이 더 이상 실행 중이 아님을 운영자가 확인한 후에만 해당 journal_id 한 건을 REVIEW로 바꿔 위 절차를 사용한다. 경과 시간만으로 자동 환불하지 않는다. 처리 대기 guard를 먼저 지우면 같은 소비를 재실행할 수 있으므로 금지한다.

### 포인트 PENDING

관리자 미궁 화면의 ‘대기 포인트 지급 재시도’를 사용한다. 동일 relation key로 재호출한다. MyISAM point 기록이 남고 member 합계가 갱신되기 전에 프로세스가 종료된 경우는 자동 재지급으로 합계를 보정하지 못한다. 기존 point/member 대조로 합계를 확인한다. 이를 위해 member 전체 engine 전환을 하지 않는다.

### 코드 rollback

활성 세션/보관 원본/REVIEW가 있는 채로 신규 테이블을 삭제하거나 이전 소비 endpoint로 되돌리지 않는다. 쓰기를 중단하고 공통 종료·정산/저널 복구를 완료한 뒤 백업과 함께 되돌린다. 자동 down migration은 제공하지 않는다.
