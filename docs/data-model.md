# Glitter Tasks 데이터 모델

## 모델

<!-- @generated:models START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 모델 | 테이블 | fillable | 관계 | 특성 |
|---|---|---|---|---|
| `Task` | `glitter_tasks` | 12 | creator→User, assignee→User | - |
<!-- @generated:models END -->

`Modules\\Glitter\\Tasks\\Models\\Task`가 `glitter_tasks` 테이블을 소유한다. 기본 정수 auto-increment `id`를 사용하며 UUID와 soft delete는 없다. Phase 4의 `assignee_id`는 이 모듈이 선택한 nullable 단일 assignee 관계이며 G7 공통 assignee 컬럼 관례가 아니다.

## 소유 테이블

<!-- @generated:tables START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 테이블 | 모델 |
|---|---|
| `glitter_tasks` | `Task` |
<!-- @generated:tables END -->

`glitter_tasks`는 제목, 설명, status, priority, 생성자, 선택적 assignee, 기한과 수명주기 시각, 느슨한 source 참조 및 metadata를 저장한다. `created_by`와 `assignee_id`는 코어 `users.id`를 참조하고 사용자 삭제 시 NULL이 된다. creator와 assignee는 독립적이며 같은 사용자일 수 있다. assignee는 생성 후에만 지정하고 `open` 또는 `in_progress`에서만 변경하며, terminal task에는 assignment 변경을 허용하지 않는다.

외부 리소스 참조가 구현되면 `source_type`, `source_id`, 선택적 `source_url`의 느슨한 결합을 사용하며 다른 확장 소유 테이블에 외래 키를 만들지 않는다.

## 마이그레이션

<!-- @generated:migrations START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
마이그레이션 2개.

| 파일 | 생성 테이블 | 변경 테이블 | down() |
|---|---|---|---|
| `2026_09_22_000001_create_glitter_tasks_table.php` | `glitter_tasks` | - | ✅ |
| `2026_09_22_000002_add_assignee_id_to_glitter_tasks_table.php` | - | `glitter_tasks` | ✅ |
<!-- @generated:migrations END -->

`2026_09_22_000001_create_glitter_tasks_table.php`가 테이블과 status, priority, created_by, due_at, source 및 created_at/id 인덱스를 생성한다. migration down은 `dropIfExists('glitter_tasks')`를 사용한다. 이 구현에서는 migration을 실행하지 않았다.

## Enum

<!-- @generated:enums START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| Enum | backing | case 수 | case |
|---|---|---|---|
| `TaskPriority` | `string` | 4 | `low`, `normal`, `high`, `urgent` |
| `TaskStatus` | `string` | 4 | `open`, `in_progress`, `completed`, `cancelled` |
<!-- @generated:enums END -->

`TaskStatus`는 `open`, `in_progress`, `completed`, `cancelled`를 제공하고 `TaskPriority`는 `low`, `normal`, `high`, `urgent`를 제공하는 PHP string-backed Enum이다.

## Repository

<!-- @generated:repositories START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 클래스 | 종류 | 설명 |
|---|---|---|
| `TaskRepository` | 구현 | - |
| `TaskRepositoryInterface` | 인터페이스 | - |
<!-- @generated:repositories END -->

`TaskRepositoryInterface`와 `TaskRepository`는 ID 조회, 예외 조회, 생성 및 갱신, expected-status 조건부 transition과 row lock 기반 expected-status/expected-assignee assignment persistence 계약을 제공한다. `TaskService`는 구체 Repository가 아닌 인터페이스를 주입받고, 생성 시 인증된 creator identity와 제목을 요구하며 status는 항상 `open`, priority는 항상 `normal`, `started_at`과 `completed_at`은 NULL로 저장한다. lifecycle 전이는 `start`(`open`→`in_progress`), `complete`(`in_progress`→`completed`), `cancel`(`open` 또는 `in_progress`→`cancelled`)만 제공한다.

`start`는 captured now를 `started_at`으로 저장하고 `completed_at`을 NULL로 한다. `complete`는 기존 `started_at`을 보존하며 NULL이면 동일한 captured now를 사용하고, `completed_at`에 같은 시각을 저장한다. `cancel`은 `started_at`을 보존하고 `completed_at`을 NULL로 하며 `due_at`은 모든 전이에서 변경하지 않는다. 취소에는 `cancelled_at`이 없다.

잘못된 source status, stale conditional update와 stale assignment는 module-owned typed exception으로 거절한다. assignment는 active이고 `withdrawn_at`이 NULL인 기존 사용자만 대상으로 하며 재할당과 해제를 지원하고 일치하는 반복 요청은 no-op이다. assignment는 status, `due_at`, lifecycle timestamp를 바꾸지 않고 history, actor 기록, multiple assignees, teams 및 settings를 만들지 않는다. 이 persistence 모델 단계 자체에는 permissions, API, UI/frontend, events 및 Workflow 연동이 포함되지 않았지만, 후속 단계에서 Admin API와 Admin management UI가 이 모델과 도메인 서비스를 사용하도록 구현되었다. Phase 12의 hook은 별도 event/history/timeline/outbox 모델을 만들지 않으며 `event_id`도 저장하지 않는다. Phase 13의 listener는 이 hook을 기존 G7 `activity_logs`에 best-effort로 기록할 뿐 Tasks 소유 history 모델이나 event_id 저장을 추가하지 않는다. Task 테이블은 계속 사람의 작업 상태를 보존하는 SSoT이고 hook payload와 Activity Log properties는 그 시점의 integration snapshot이다. G7의 retention/pruning/deletion 정책이 Activity Log에 적용되며 immutable audit ledger가 아니다. member/public API와 Workflow 연동은 여전히 제공하지 않는다.
