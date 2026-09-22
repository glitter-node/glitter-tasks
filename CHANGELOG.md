# Changelog

## [0.13.0] - 2026-09-22

### Added

- G7 전역 Activity Log에 성공한 Task operation을 연결하는 7개 activity listener를 추가했습니다.
- Task ID, actor, operation, occurred_at 및 안전하게 제한된 scalar properties를 기록합니다.
- hook 기반 best-effort 기록이며 큐 지연·실패·재시도에 따른 누락 또는 중복이 가능하고, durable audit/history나 exactly-once를 제공하지 않습니다.

### Deferred

- Tasks 소유 history/outbox, notifications, Workflow integration, Task history API/UI는 제공하지 않습니다.

## [0.12.0] - 2026-09-22

### Added

- TaskService의 성공한 생성, 상세 수정, 할당·해제 및 lifecycle operation을 위한 7개 Task action hook을 추가했습니다.
- hook은 성공한 영속화 이후 commit 경계를 존중하고, event ID·actor·UTC 시각과 scalar snapshot payload를 전달합니다.
- hook publication은 best-effort이며 durable event store, outbox, replay 또는 exactly-once delivery를 제공하지 않습니다.

### Deferred

- notifications, Workflow integration, activity history, timeline, 새 API, 새 권한 및 Admin UI 변경은 제공하지 않습니다.

## [0.11.0] - 2026-09-22

### Added

- `tasks.create`로 보호되는 관리자 Task 생성 UI를 추가했습니다.
- `tasks.update`로 보호되는 제목·설명·우선순위·마감일 수정 UI를 추가했습니다.
- `tasks.assign`으로 보호되는 할당·재할당·해제 UI와 `tasks.transition`으로 보호되는 시작·완료·취소 UI를 추가했습니다.
- 기존 admin API를 재사용하는 permission-aware JSON Layout 관리자 화면과 한·영 상태·오류·성공 처리를 추가했습니다.

### Deferred

- 삭제, member/public UI, 검색, 설정, 사용자 조회/자동완성, custom JavaScript/CSS, events, notifications 및 Workflow 연동은 아직 제공하지 않습니다.

## [0.10.0] - 2026-09-22

### Added

- `tasks.read`로 보호되는 읽기 전용 관리자 Task 목록·상세 브라우저 UI를 추가했습니다.
- 기존 admin API를 통해 status, priority, assignee ID, 미할당 및 creator ID discovery filter와 bounded pagination을 제공합니다.
- Task 목록에서 상세 화면으로 이동하고 상세 화면에서 목록으로 돌아오는 module-local JSON Layout 화면을 추가했습니다.

### Deferred

- 생성, 상세 수정, 할당, lifecycle 변경, 삭제 UI, member/public UI, 검색, 이벤트, 알림 및 Workflow 연동은 아직 제공하지 않습니다.

## [0.9.0] - 2026-09-22

### Added

- admin Task 목록 API에 `priority`, `assignee_id`, `unassigned`, `created_by` discovery filter를 추가했습니다.
- 기존 `status`, bounded pagination 및 `created_at DESC`, `id DESC` 고정 정렬을 유지합니다.
- 모든 필터는 AND semantics로 결합하며 `unassigned=true`와 `assignee_id`의 충돌을 422로 거절합니다.

### Deferred

- text search, date/overdue filter, configurable sorting, Admin UI, member/public API 및 ownership authorization은 아직 제공하지 않습니다.

## [0.8.0] - 2026-09-22

### Added

- `tasks.update` 권한으로 보호되는 admin Task details editing API를 추가했습니다.
- `title`, `description`, `priority`, `due_at`의 부분 수정과 명시적 nullable clearing을 지원합니다.
- terminal Task 수정 거부와 row lock 기반 terminal race 보호를 추가했습니다.

### Deferred

- 삭제, 상태·assignee·creator·source·metadata 수정, member/public API, UI, events 및 Workflow 연동은 아직 제공하지 않습니다.

## [0.7.0] - 2026-09-22

### Added

- admin-context API에 할당, 할당 해제, 시작, 완료 및 취소 operation을 추가했습니다.
- 기존 `tasks.assign` 및 `tasks.transition` 권한으로 각 operation을 보호합니다.
- `assign` 요청은 양의 정수 `assignee_id`만 허용하며, 모든 변경 응답은 기존 TaskResource를 사용합니다.
- 잘못된 도메인 상태는 422, stale 동시성 충돌은 409로 구조적으로 매핑합니다.

### Deferred

- generic update/delete, title·description·priority·due date 변경, member-facing API, UI, frontend 및 Workflow 연동은 아직 제공하지 않습니다.

## [0.6.0] - 2026-09-22

### Added

- admin-context JSON API에 Task 목록, 상세 조회 및 생성을 추가했습니다.
- `glitter-tasks.tasks.read` 권한과 `admin`/`manager` 역할 부여를 추가했습니다.
- `auth:sanctum`, G7 admin boundary 및 route permission middleware로 API를 보호합니다.
- 생성 입력과 응답 필드를 명시적으로 제한했습니다. `tasks.read` 통과 관리자는 모든 Task를 조회합니다.

### Deferred

- assignment/lifecycle HTTP operation, member-facing authorization, API UI 및 frontend는 아직 제공하지 않습니다.

## [0.5.0] - 2026-09-22

### Added

- 선언형 `glitter-tasks.tasks.create` 권한을 추가했습니다.
- 선언형 `glitter-tasks.tasks.assign` 권한을 추가했습니다. 할당, 재할당 및 해제를 포함합니다.
- 선언형 `glitter-tasks.tasks.transition` 권한을 추가했습니다. 시작, 완료 및 취소를 포함합니다.
- `admin` 및 `manager` 역할에 대한 명시적 권한 부여를 선언했습니다.

### Deferred

- 아직 외부 endpoint enforcement, API, UI 또는 권한 검사 진입점은 구현하지 않습니다.

## [0.4.0] - 2026-09-22

### Added

- nullable 단일 assignee와 생성 후 할당, 재할당 및 해제 Service 기능을 추가했습니다.
- stale assignment write를 막는 row lock 기반 expected-state 검사를 추가했습니다.
- 코어 users를 참조하는 nullable `assignee_id` additive schema field를 추가했습니다.

## [0.3.0] - 2026-09-22

### Added

- `TaskService`에 `start`, `complete`, `cancel` lifecycle operation을 추가했습니다.
- expected status를 조건으로 한 원자적 Repository transition 계약을 추가했습니다.
- 잘못된 상태 및 stale transition을 위한 typed exception과 한·영 번역을 추가했습니다.

### Deferred

- reopen, assignment, permissions, events, API, UI, frontend 및 Workflow 연동은 아직 구현하지 않습니다.

## [0.2.0] - 2026-09-22

### Added

- Task status와 우선순위 backed Enum을 추가했습니다.
- `glitter_tasks` 테이블, Task 모델, Repository 및 최소 생성 Service를 추가했습니다.
- 모듈 Provider에서 Task Repository 계약을 바인딩합니다.

### Deferred

- assignee 영속화, API, UI, frontend 및 Workflow 연동은 아직 구현하지 않습니다.

## [0.1.0] - 2026-09-22

### Added

- Glitter Tasks 모듈 메타데이터, 진입점 및 Composer 자동 로딩 골격을 추가했습니다.
- 작업 수명주기를 위한 확장 개발자 문서 기반을 추가했습니다.
