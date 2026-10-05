# 던전·전투 정리 1차 검증

검증일: 2026-10-05

## DB 스키마 설치 페이지

현재 `adm/system_install.php`는 `install/system/modules.php`에 등록된 최신 계약으로 설치한다. 새 설치에서는 최신 구조를 직접 만들고, 기존 DB에서는 필요한 이전 단계와 최신 구조 보완을 실행한다. 조회와 새로고침에서는 스키마를 변경하지 않는다. 이전 설치 페이지의 POST는 현재 페이지로 이동하며 구형 설치 SQL을 실행하지 않는다.

폐기한 저장소가 새 설치의 중간 단계, 전체 설치 완료, 반복 실행, 기존 K 버전 업데이트에서 재생성되지 않는 것을 확인했다.

| 폐기한 저장소 | 현재 저장 위치 |
| --- | --- |
| `dungeon_maze_config` | `dungeon.dg_settings` |
| `dungeon_maze_session` | `dungeon_state` |
| `dungeon_monster_config`, `monster_dungeon_profile` | `monster_master.profile` |
| `dungeon_state_monster`, `dungeon_member`, `dungeon_log` | 방 탐험의 전투·참가자·진행 기록 |
| `dungeon_skill_target` | `skill.sk_dungeon_target_mode` |
| `dungeon_maze_item_effect` | `item.it_type / it_value` |
| K 몬스터의 별도 저장 테이블 | `monster_master`와 `k_battle_monster` 조회 뷰 |

실제 DB에서도 위 폐기 테이블과 K 이전 임시 테이블이 없고, `k_battle_monster`·`dungeon_monster_read`는 뷰다. `dungeon`의 구형 몬스터 능력치, `dungeon_item`의 구형 보상 확률 구간, `dungeon_state`의 구형 전투 HP 컬럼도 없다.

현재 모듈 버전은 던전 1.2.0, 몬스터 1.8.0, 탐험/정산 1.7.0, K 전투 2.1.0이다. 다른 등록 모듈도 최신 상태다. 이번 검증에서 실제 DB에는 읽기 점검만 수행했다.

## 유지하는 저장소

- 던전 기본 정보·탐험 규칙, 출현 목록, 아이템 보상은 각각 `dungeon`, `dungeon_monster_pool`, `dungeon_item`을 사용한다.
- 몬스터 능력치·행동은 공용 `monster_master.profile`을 사용한다.
- 탐험 참가자·지도·개체별 전투·제출 행동·보관 아이템·보상 정산·투표·로그는 실제 플레이와 복구에 필요한 실행 기록이다. 출발 스냅샷은 진행 중 정의가 바뀌지 않도록 보관한다.
- `dungeon_maze_status`는 공용 상태이상 관리 화면과 전투·아이템 효과가 참조한다.
- MMB·실시간 레이드·1:1 배틀에 필요한 설정과 실행 테이블은 유지한다. 행 수가 0인 테이블도 기능에서 사용하면 유지한다.
- 설치 파일의 과거 버전 계약은 기존 DB 이전과 회귀 검증에 사용한다. 현재 페이지의 신규 생성 목록은 최신 계약이다.

## 통과한 검증

| 검증 | 확인한 내용 |
| --- | --- |
| 설치 통합 테스트 | 새 설치의 각 모듈과 전체 반복 실행에서 폐기 저장소 부재, 공용 뷰 유지, 버전·실패 재시도·잠금 검증 |
| 실제 관리자 HTTP 요청 | 인증·토큰·GET 조회, 전 모듈 POST 설치, 전체 설치 후 반복 실행, 구형 확장/테마의 자동 생성 차단 |
| 던전 저장소 이전 | 설정·보상·출현 통합, 진행 세션·참가자·보관 원본 ID 유지, 중복 테이블 삭제, 반복 이전 |
| K 몬스터 저장소 이전 | MMB·실시간 컬럼 유지, 공용 Master 참조, 충돌 시 원본 복구 |
| 실제 DB 읽기 점검 | 폐기 테이블·구형 컬럼 부재, 뷰 유형, 최신 모듈 상태 |

검증 코드는 다음 위치에 있다.

```text
php dev/tests/system_installer_integration.php
php dev/tests/dungeon_unify_storage_integration.php
php dev/tests/monster_raid_storage_integration.php
python dev/tests/system_installer_http.py --full-storage
php dev/tools/audit_system_install_storage.php
```

DB 변경 테스트는 포트 33381의 격리 MariaDB에서 실행했다. HTTP 테스트는 현재 작업 파일로 만든 새 격리 사이트와 포트 38081을 사용했다. HTTP 명령은 새 `prepare_installer_site.php` fixture를 준비한 후 실행한다. 실제 DB 읽기 점검 결과는 `artifacts/system-install-storage-audit-20261005.json`에 저장했다.

## 앞선 기능 검증

방 탐험으로의 진행 통일, 관리자 네 탭의 일괄 저장, 선택한 몬스터만 표시, 최소·최대 동시 출현 수, 맵에서 바로 대기실 참가, 치유 로그 중복 제거를 확인했다. HP 패턴은 몬스터 MP·대기시간 제한 없이 실행한다. 몬스터 이벤트는 출발 시 출현 목록에 등록된 몬스터만 실행하며, 과거에 저장된 목록 밖 이벤트도 차단한다. 실시간 조회의 `maze_battle_group()` 누락을 수정했고, 잘못 열린 첫 턴 파수꾼 전투는 백업 후 참가자·보관 아이템을 유지하여 복구했다.

1차 범위의 구현과 위 검증을 완료했다. 다음 수정은 `docs/dungeon-combat-map.md`의 코드 지도를 기준으로 찾는다.
