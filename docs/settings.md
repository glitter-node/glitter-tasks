# Glitter Tasks 설정과 관리자 표면

## 설정 스키마

<!-- @generated:settings-schema START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_`getSettingsSchema()` 선언이 없습니다._
<!-- @generated:settings-schema END -->

설정 스키마와 기본값 파일이 없다.

## 권한

<!-- @generated:permissions START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 카테고리 | 이름 | 액션 | 라우트 키 |
|---|---|---|---|
| `tasks` | 작업 | `create`, `read`, `assign`, `update`, `transition` | - |
<!-- @generated:permissions END -->

G7 선언형 권한 catalogue는 `tasks.create`, `tasks.read`, `tasks.assign`, `tasks.transition`, `tasks.update`으로 제공되며 `admin`과 `manager` 역할에 명시적으로 부여된다. `tasks.read`는 모든 Task를 조회할 수 있고, `tasks.update`는 `title`, `description`, `priority`, `due_at`의 admin 상세 수정을 보호한다. 이는 capability와 role grant 선언 및 route enforcement를 위한 것이며 TaskService 자체를 보호하지 않는다. member-facing authorization은 별도 설계가 필요하다. 별도의 설정 스키마는 추가하지 않는다.

## 메뉴

<!-- @generated:menus START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 구분 | slug | 이름 | URL | 하위 |
|---|---|---|---|---|
| 관리자 | `glitter-tasks` | 작업 관리 | `/admin/tasks` | - |
<!-- @generated:menus END -->

관리자 메뉴는 `glitter-tasks.tasks.read` 권한으로 보호되는 Task 관리 브라우저 `/admin/tasks`를 가리킨다. 생성·수정·할당·lifecycle control은 각 기존 permission으로 게이팅되며, 별도의 설정 메뉴나 설정 스키마는 추가하지 않는다.

## 라우트

<!-- @generated:routes START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 종류 | 파일 | URL prefix |
|---|---|---|
| `api` | `src/routes/api.php` | `/api/modules/glitter-tasks/...` |

확장 라우트는 **활성 상태인 확장의 것만** 등록됩니다. 라우트 정의를 바꾸면 라우트 캐시 재생성이 필요합니다.
<!-- @generated:routes END -->

admin-context 백엔드 API는 `src/routes/api.php`에 있으며 web route는 없다. `resources/routes/admin.json`은 `/admin/tasks` 목록, `/admin/tasks/create` 생성, `/admin/tasks/:id` 상세 및 `/admin/tasks/:id/edit` 수정의 JSON Layout manifest다. 이는 설정 스키마가 아니며 API enable, page-size, visibility 또는 ownership 설정은 추가하지 않는다.

## 의존 관계

<!-- @generated:dependencies START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
**이 확장이 의존하는 확장**

없음 — 코어만으로 동작합니다.

**이 확장에 의존하는 확장** (이 확장을 비활성화하면 함께 영향을 받습니다)

없음.
<!-- @generated:dependencies END -->

다른 모듈이나 플러그인 의존성이 없다. Glitter Workflow는 선택적 통합 대상일 수 있으나 필수 의존성이 아니다.
