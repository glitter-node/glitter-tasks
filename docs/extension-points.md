# Glitter Tasks 확장점

## 발행 훅

<!-- @generated:hooks-published START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
발행 훅 7종 / 호출 지점 7곳. 이 중 7종은 `getHooks()` 선언에 없어 소스에서 자동 감지한 것입니다 — 선언에 추가하면 유형과 설명이 함께 실립니다.

| 훅 이름 | 유형 | 설명 | 발행 위치 |
|---|---|---|---|
| `glitter-tasks.task.after_assigned` | action | — | `src/Services/TaskEventPublisher.php:75` |
| `glitter-tasks.task.after_cancelled` | action | — | `src/Services/TaskEventPublisher.php:129` |
| `glitter-tasks.task.after_completed` | action | — | `src/Services/TaskEventPublisher.php:116` |
| `glitter-tasks.task.after_create` | action | — | `src/Services/TaskEventPublisher.php:37` |
| `glitter-tasks.task.after_details_updated` | action | — | `src/Services/TaskEventPublisher.php:59` |
| `glitter-tasks.task.after_started` | action | — | `src/Services/TaskEventPublisher.php:103` |
| `glitter-tasks.task.after_unassigned` | action | — | `src/Services/TaskEventPublisher.php:91` |
<!-- @generated:hooks-published END -->

Phase 12부터 TaskService의 성공한 operation은 아래 7개 action hook을 발행한다. generated inventory는 operator가 `ext:docgen`으로 갱신하며, 이 문서의 계약 설명이 payload와 시점의 SSoT다.

공통 payload는 모든 hook에 정확히 하나의 associative array로 전달되며 `event_id`(UUID), `task_id`, `actor_id`(integer 또는 null), `occurred_at`(UTC ISO-8601)을 포함한다. payload에는 scalar, null 및 그 배열만 들어가고 Model, Enum, Request, Repository, Service 또는 Closure는 전달하지 않는다. `event_id`는 consumer deduplication aid일 뿐 durable identity가 아니다.

| Hook | 발행 시점 | 추가 payload | no-op/실패 동작 |
|---|---|---|---|
| `glitter-tasks.task.after_create` | 생성 영속화 성공 후 commit 경계 | `task`: `title`, `status`, `priority`, `created_by`, `assignee_id`, `due_at` | 생성 실패 시 미발행 |
| `glitter-tasks.task.after_details_updated` | details update 성공 후 | `changed_fields`: 제출된 `title`, `description`, `priority`, `due_at`의 accepted field 이름 목록 | empty update·실패·stale/terminal 거부 시 미발행 |
| `glitter-tasks.task.after_assigned` | 실제 assign/reassign 성공 후 | `from_assignee_id`, `to_assignee_id` | same-assignee no-op·실패 시 미발행 |
| `glitter-tasks.task.after_unassigned` | 실제 unassign 성공 후 | `from_assignee_id`, `to_assignee_id: null` | already-unassigned no-op·실패 시 미발행 |
| `glitter-tasks.task.after_started` | `open` → `in_progress` 성공 후 | `from_status`, `to_status`, `started_at`, `completed_at` | invalid/stale 전이 시 미발행 |
| `glitter-tasks.task.after_completed` | `in_progress` → `completed` 성공 후 | `from_status`, `to_status`, `started_at`, `completed_at` | invalid/stale 전이 시 미발행 |
| `glitter-tasks.task.after_cancelled` | `open`/`in_progress` → `cancelled` 성공 후 | `from_status`, `to_status`, `started_at`, `completed_at` | invalid/stale 전이 시 미발행 |

생성 snapshot에는 `description`, `metadata`, `source_type`, `source_id`, `source_url`을 포함하지 않는다. details update의 `changed_fields`는 old/new diff가 아니며 description 내용도 포함하지 않는다. 모든 Task timestamp는 UTC ISO-8601 또는 null이다.

Publisher는 operation 성공 후 payload를 완성하고, 활성 transaction이 있으면 G7/Laravel `afterCommit` callback에 immutable scalar payload를 등록한다. transaction이 없으면 이미 성공한 persistence 뒤에 발행한다. publisher 또는 dispatch infrastructure의 실패는 context를 로그에 남기고 Task mutation/API 성공을 실패로 바꾸지 않는다. 기본 G7 action listener는 `DispatchHookListenerJob`을 통해 after-commit 및 queue semantics를 따르며, listener 실패는 subscriber의 best-effort 문제다.

이 surface는 durable event store, outbox, replay, exactly-once delivery 또는 history/timeline이 아니다. Subscriber는 duplicate/retried delivery를 허용해야 하며 `event_id`로 deduplicate할 수 있다. Hook payload는 발행 시점의 operation snapshot이지 현재 Task 상태의 SSoT가 아니므로, 최신 상태가 필요하면 지원된 Task 인터페이스를 사용한다.

## 구독 훅

<!-- @generated:hooks-subscribed START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 훅 이름 | 유형 | 리스너 | 메서드 | 우선순위 |
|---|---|---|---|---|
| `glitter-tasks.task.after_assigned` | action (미선언) | `TaskActivityLogListener` | `handleAfterAssigned` | 20 |
| `glitter-tasks.task.after_cancelled` | action (미선언) | `TaskActivityLogListener` | `handleAfterCancelled` | 20 |
| `glitter-tasks.task.after_completed` | action (미선언) | `TaskActivityLogListener` | `handleAfterCompleted` | 20 |
| `glitter-tasks.task.after_create` | action (미선언) | `TaskActivityLogListener` | `handleAfterCreate` | 20 |
| `glitter-tasks.task.after_details_updated` | action (미선언) | `TaskActivityLogListener` | `handleAfterDetailsUpdated` | 20 |
| `glitter-tasks.task.after_started` | action (미선언) | `TaskActivityLogListener` | `handleAfterStarted` | 20 |
| `glitter-tasks.task.after_unassigned` | action (미선언) | `TaskActivityLogListener` | `handleAfterUnassigned` | 20 |
<!-- @generated:hooks-subscribed END -->

Phase 13의 `TaskActivityLogListener`는 위 7개 Task operation hook을 구독해 기존 G7 Activity Log에 best-effort로 기록한다. 이 listener는 Task를 다시 조회하거나 변경하지 않으며, Activity Log 기록은 durable history나 immutable audit ledger가 아니다.

## 훅 리스너

<!-- @generated:listeners START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 리스너 | 구독 훅 | 등록 방식 | HookListenerInterface | 파일 |
|---|---|---|---|---|
| `TaskActivityLogListener` | 7개 | 명시 등록 | ✅ | `src/Listeners/TaskActivityLogListener.php` |
<!-- @generated:listeners END -->

등록된 listener는 `TaskActivityLogListener` 하나이며, Task hook의 operation snapshot과 안전하게 제한된 properties만 Activity Log에 전달한다. 큐 지연·실패·재시도에 따른 누락 또는 중복 가능성과 G7 retention/pruning/deletion 정책을 그대로 따른다.

## 레이아웃 확장

<!-- @generated:layout-extensions START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_레이아웃 확장이 없습니다._
<!-- @generated:layout-extensions END -->

제공하는 레이아웃 확장이 없다.

## 미들웨어

<!-- @generated:middleware START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 미들웨어가 없습니다._
<!-- @generated:middleware END -->

등록하는 모듈 미들웨어가 없다.

## 브로드캐스트 채널

<!-- @generated:channels START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 브로드캐스트 채널이 없습니다._
<!-- @generated:channels END -->

등록하는 브로드캐스트 채널이 없다.

## 스케줄

<!-- @generated:schedules START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 스케줄이 없습니다._
<!-- @generated:schedules END -->

등록하는 스케줄이 없다. 시간 기반 overdue 의미도 정의하지 않는다.

## 알림 정의

<!-- @generated:notifications START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 알림 정의가 없습니다._
<!-- @generated:notifications END -->

등록하는 알림 정의가 없다. 향후 알림은 G7 알림 계약을 재사용해야 한다.
