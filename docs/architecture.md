# Glitter Tasks 아키텍처

## 설계 의도

Glitter Tasks는 사람이 수행하는 작업 상태를 소유하는 독립형 모듈이다. Task는 외부 도메인을 선택적으로 가리킬 수 있지만 외부 확장의 데이터 수명주기나 테이블 구조를 소유하지 않는다.

## 계층 지도

```text
Gnuboard7 extension lifecycle
  -> Module entry point
  -> TaskService
  -> TaskRepositoryInterface
  -> TaskRepository
  -> Task model
  -> TaskEventPublisher
  -> G7 after-commit action hook
  -> TaskActivityLogListener
  -> G7 ActivityLogHandler
  -> optional external subscriber
```

HTTP 흐름은 `Route middleware -> Admin TaskController -> FormRequest -> TaskService -> TaskRepositoryInterface -> TaskRepository -> Task`를 따른다. 0.12.0에서 성공한 TaskService operation은 persistence 성공 후 scalar snapshot을 `TaskEventPublisher`에 전달하고, publisher는 7개 best-effort hook 중 해당 hook을 commit 이후 optional subscriber에 알린다. Phase 13의 `TaskActivityLogListener`는 이 hook snapshot을 `ActivityLogHandler`에 전달하며, Task mutation을 veto하지 않는다. Activity Log의 `occurred_at` property는 Task operation 시각이고, queued listener 실행 시점의 `created_at`과 다를 수 있다. durable/domain-event infrastructure와 persisted event/history는 이 계층에 포함되지 않고, notifications와 Workflow integration도 deferred scope다. 0.11.0 admin API는 `tasks.read`, `tasks.create`, `tasks.update`, `tasks.assign` 또는 `tasks.transition` permission middleware로 보호되며, discovery query·details editing·lifecycle·assignment boundary는 TaskService이다. Service가 생성 invariant와 도메인 동작을 소유하고, 읽기 목록은 Repository의 bounded query 계약을 호출한다. Admin Task 브라우저는 `tasks.read` 레이아웃 권한으로 읽기 화면을 보호하고 operation별 기존 permission으로 관리 control을 노출하며, 모든 읽기·변경은 기존 admin API에 위임하는 JSON Layout이다.

## 디렉토리

<!-- @generated:directory-map START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 경로 | 역할 | 수정 시 필요한 절차 |
|---|---|---|
| `module.json` | manifest (버전 SSoT) | version 변경 시 package.json·package-lock.json·composer.json 동기화 |
| `module.php` | 진입 클래스 (선언형 표면 SSoT) | 표면 변경 시 `ext:docgen` 재실행 + 코어 최소 버전 검토 |
| `src/Http/Controllers/` | 컨트롤러 | API 표면 변경 시 `api:docgen` 재실행 |
| `src/Http/Requests/` | FormRequest (검증 SSoT) | 검증 규칙은 Service 가 아니라 여기에 둔다 |
| `src/Http/Resources/` | API 리소스 | 목록 응답은 화면이 실제로 그리는 것만 싣는다 |
| `src/Services/` | 비즈니스 로직 | Repository 인터페이스 주입 (구체 클래스 금지) |
| `src/Repositories/` | 데이터 접근 | 목록 쿼리는 컬럼 프루닝·정렬 화이트리스트 확인 |
| `src/Models/` | Eloquent 모델 | 스키마 변경 시 마이그레이션 + 업그레이드 스텝 동반 |
| `src/Listeners/` | 훅 리스너 | Repository 경유 (Model·DB 파사드 직접 접근 금지) |
| `src/Enums/` | 상태·타입·분류 | 문자열 리터럴 대신 Enum 을 SSoT 로 둔다 |
| `src/routes/` | 라우트 | 모든 라우트에 `name()` 필수 |
| `src/lang/` | 백엔드 다국어 | ko·en 동시 반영 + 번들 ja 팩 동기화 |
| `database/migrations/` | 마이그레이션 | 한국어 comment + `down()` 필수, 기설치본은 업그레이드 스텝으로 백필 |
| `resources/layouts/` | 레이아웃 JSON | `php artisan module:update glitter-tasks --force` (빌드 불필요) |
| `resources/routes/` | 라우트 → 레이아웃 매핑 (분할) | `php artisan module:update glitter-tasks --force` |
| `CHANGELOG.md` | 변경 이력 | 버전 상향 시 항목 추가 (미기재 시 버전 상향 불가) |
| `docs/` | 개발자 문서 | 표면 변경 시 `php artisan ext:docgen` 재실행 |
<!-- @generated:directory-map END -->

- `module.json`: 이름, 버전, 코어 제약 및 의존성
- `module.php`: 선언형 모듈 표면
- `resources`: 관리자 route manifest, Task 목록·상세·생성·수정 JSON Layout 및 partial 번역
- `database/migrations`: 모듈 소유 `glitter_tasks` 테이블 마이그레이션
- `src/Enums`: Task status와 priority backed Enum
- `src/Models`: Task Eloquent 모델
- `src/Repositories`: Task Repository 계약과 구현
- `src/Services`: Task 생성, details editing, lifecycle 전이 및 assignment Service
- `src/Providers`: 모듈 Repository binding Provider
- `docs`: 공개 계약과 설계 경계

관리자 화면은 `/admin/tasks` 목록, `/admin/tasks/:id` 상세, `/admin/tasks/create` 생성 및 `/admin/tasks/:id/edit` 수정으로 구성된다. 목록은 status, priority, assignee ID, unassigned, creator ID, page 및 per_page만 API에 전달하고 API의 고정 정렬과 bounded pagination을 사용한다. 상세는 TaskResource가 제공하는 필드를 표시하고 기존 생성·상세 수정·assignment·lifecycle API를 permission-gated control로 호출한다. 생성은 title·description·due_at만, 수정은 title·description·priority·due_at만 제출하며 status·assignee·lifecycle timestamp는 전용 API가 소유한다. user lookup, search, custom JavaScript/CSS는 제공하지 않으며 목록·상세·폼 이동은 Layout의 `mergeQuery`가 지원하는 범위에서 query context를 유지한다.

admin API 소스는 `src/routes/api.php`, `src/Http/Controllers`, `src/Http/Requests` 및 `src/Http/Resources`에 둔다. assignment는 Task에 하나의 선택적 assignee만 두며, `getPermissions()`는 `tasks.create`, `tasks.read`, `tasks.update`, `tasks.assign`, `tasks.transition` capability와 admin/manager role grant를 선언한다. 이 선언은 TaskService의 authorization이 아니며 route middleware가 admin API entry point를 보호한다. `tasks.read`는 모든 Task를 읽을 수 있는 관리용 권한이고, 목록 Repository는 `status`, `priority`, `assignee_id`, `unassigned`, `created_by`의 명시적 AND predicates만 적용한다. `assignee_id`와 `created_by`는 존재 여부를 확인하지 않는 discovery filter이며 ownership authorization이 아니다. `unassigned=true`와 assignee_id의 조합은 422로 거절하고, 정렬은 `created_at DESC`, `id DESC`로 고정한다. generic search, date/overdue filter, configurable sorting, delete, member API, durable event/history infrastructure, notifications 및 Workflow integration, assignment history, multiple assignees, teams 및 settings는 이 단계에 포함되지 않는다.
