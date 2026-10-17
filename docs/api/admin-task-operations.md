# 관리자 Task Operation API

## 공통 계약

모든 경로는 기존 모듈 API prefix 아래의 admin-context API이며 `auth:sanctum`과 G7 admin boundary를 사용한다. 각 경로는 아래에 명시한 permission middleware를 추가로 요구한다. Task ID는 숫자이며, 권한 검사가 Task 조회보다 먼저 적용된다.

성공 응답은 기존 `successWithResource(...)` envelope와 `TaskResource`를 사용하고 HTTP 200을 반환한다. 인증 실패는 401, 권한 부족은 403, Task 없음은 404, 잘못된 요청 또는 도메인 상태는 422, stale 동시성 충돌은 409다.

TaskService가 도메인 invariant를 소유하므로 controller는 검증된 입력만 전달한다. TaskService와 assignment/lifecycle Repository의 동시성 계약은 이 API에서 변경하지 않는다.

## Operation

| Method | URI | Permission | 동작 |
|---|---|---|---|
| POST | `/api/modules/glitter-tasks/admin/tasks/{task}/assign` | `glitter-tasks.tasks.assign` | Task에 active/non-withdrawn 사용자 할당 또는 재할당 |
| POST | `/api/modules/glitter-tasks/admin/tasks/{task}/unassign` | `glitter-tasks.tasks.assign` | Task 할당 해제 |
| POST | `/api/modules/glitter-tasks/admin/tasks/{task}/start` | `glitter-tasks.tasks.transition` | `open` → `in_progress` |
| POST | `/api/modules/glitter-tasks/admin/tasks/{task}/complete` | `glitter-tasks.tasks.transition` | `in_progress` → `completed` |
| POST | `/api/modules/glitter-tasks/admin/tasks/{task}/cancel` | `glitter-tasks.tasks.transition` | `open` 또는 `in_progress` → `cancelled` |

## Task 목록 조회

`GET /api/modules/glitter-tasks/admin/tasks`는 기존 `glitter-tasks.tasks.read` 권한과 bounded pagination을 사용한다. 지원하는 query parameter는 `status`, `priority`, `assignee_id`, `unassigned`, `created_by`, `page`, `per_page`뿐이다.

- `status`: `open`, `in_progress`, `completed`, `cancelled` 중 하나
- `priority`: `low`, `normal`, `high`, `urgent` 중 하나
- `assignee_id`: 선택적 양의 정수. 사용자 존재 여부는 확인하지 않는다.
- `unassigned`: 선택적 boolean. `true`는 `assignee_id IS NULL`, `false`는 predicate 없음이다.
- `created_by`: 선택적 양의 정수. 사용자 존재 여부는 확인하지 않으며 ownership authorization이 아니다.
- `page`, `per_page`: 기존 pagination bounds와 result cap을 그대로 사용한다.

빈 문자열은 해당 선택적 filter가 없는 것으로 정규화된다. `unassigned=true`와 `assignee_id`를 함께 보내면 422이며, malformed/non-positive enum·ID·boolean 값도 422다. 유효하지만 존재하지 않는 assignee/creator ID는 요청 오류가 아니며 일반적으로 일치하는 Task가 없다. 모든 active filter는 AND로 결합하고 정렬은 `created_at DESC`, `id DESC`로 고정한다. 응답 resource와 pagination shape는 변경하지 않는다.

## Task 상세 수정

`PATCH /api/modules/glitter-tasks/admin/tasks/{task}`는 `glitter-tasks.tasks.update` 권한이 필요하며 다음 필드만 부분 수정할 수 있다.

- `title`: 선택적 문자열, trim 후 비어 있지 않아야 하며 최대 255자
- `description`: 선택적 nullable 문자열. 명시적 `null` 또는 빈 문자열은 clearing으로 처리
- `priority`: 선택적 `low`, `normal`, `high`, `urgent` 중 하나
- `due_at`: 선택적 nullable 날짜. 과거 날짜도 허용

요청에는 위 네 가지 인식 가능한 수정 필드 중 하나 이상이 포함되어야 한다. 수정 필드가 없거나 지원하지 않는 필드만 포함한 요청은 HTTP 422 validation failure로 거절되며, 지원하지 않는 필드는 저장되지 않는다.

생략한 필드는 기존 값을 유지한다. `status`, `assignee_id`, `created_by`, lifecycle timestamps, source references, `metadata`, 식별자 및 생성·수정 시각은 이 API에서 수정할 수 없다. completed/cancelled Task는 일반 도메인 거부로 422이며, 잠금 후 terminal 상태가 확인되면 stale 충돌로 409다. 동시 descriptive edit는 허용된 필드에 대해 last-write-wins이고, row lock은 terminal race를 차단한다.

## Assign 요청

`assign`은 다음 필드만 받는다.

```json
{
  "assignee_id": 123
}
```

`assignee_id`는 필수 양의 정수다. 사용자 존재 여부, active 상태 및 `withdrawn_at`은 request가 아니라 TaskService가 검증한다. 이미 같은 사용자가 할당된 경우 유효한 no-op이고, stale assignment는 409다.

`unassign`은 operation payload가 없으며 이미 할당되지 않은 Task에 대한 반복 호출도 유효한 no-op다. completed/cancelled Task의 assignment 변경과 잘못된 lifecycle 상태 전이는 422다.

## 범위 제외

delete, text search, date/overdue filter, configurable sorting, member-facing API, public API, custom frontend runtime, events, notifications 및 Workflow 연동은 제공하지 않는다. Phase 11은 이 문서의 기존 9개 Admin API route를 사용하는 Admin Task management UI를 제공하지만, 별도의 API endpoint나 PATCH 외의 generic mass update와 status·assignee·creator·source·metadata 변경은 추가하지 않는다.
