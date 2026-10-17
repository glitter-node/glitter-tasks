# Glitter Tasks — 에이전트 가이드

## TL;DR (5초 요약)

Glitter Tasks는 사람이 수행하는 운영 작업 상태를 소유하는 독립형 Gnuboard7 모듈이다. 현재 Phase 13(0.13.0)은 모듈 소유 Task 영속화, 제한된 lifecycle 전이, 생성 후 단일 assignee assignment, G7 선언형 permission catalogue, 기존 admin API를 소비하는 permission-aware 관리자 Task 관리 UI, 성공한 Task operation을 위한 best-effort action hook surface 및 G7 전역 Activity Log listener를 제공하며, 다른 확장과 결합하지 않고 확인된 G7 공개 계약만 사용한다.

## 1. 이 확장은 무엇인가

작업 생성, 할당, 추적, 완료를 위한 human-work SSoT다. 자동화 실행 원장은 Glitter Workflow가 소유하고, 사람이 수행하는 Task 상태는 Glitter Tasks가 소유한다. 관리자 브라우저는 기존 admin API를 소비하는 목록·상세·생성·수정 및 관리 operation 화면이다. 성공한 Task operation은 TaskService와 TaskEventPublisher 경계를 통해 선택적 subscriber hook으로 알릴 수 있다.

## 2. 디렉토리 지도

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

| 경로 | 역할 |
|---|---|
| `module.json` | 모듈 메타데이터와 버전 SSoT |
| `module.php` | 선언형 모듈 진입점 |
| `resources/routes/` | 템플릿 유형별 프런트엔드 라우트 manifest |
| `docs/` | 아키텍처와 공개 계약 문서 |

## 3. 핵심 흐름

Gnuboard7이 bundled manifest와 진입 클래스를 발견하고 모듈 Provider와 API route를 자동 등록한다. Task 생성, details editing, lifecycle 전이와 assignment는 `TaskService -> TaskRepositoryInterface -> TaskRepository -> Task` 흐름으로 저장된다. admin API는 `tasks.read`, `tasks.create`, `tasks.update`, `tasks.assign`, `tasks.transition` route permission으로 보호되고, `getPermissions()`는 capability와 admin/manager role grant를 선언하지만 TaskService를 직접 보호하지 않는다. 관리자 Task UI는 `tasks.read` 레이아웃 권한과 operation별 기존 permission으로 버튼·화면을 게이팅하며 기존 API만 호출한다. generic delete UI, member API, custom frontend runtime 및 Workflow 연동은 없다.

## 4. 확장점

<!-- @generated:extension-points-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 확장점 | 수 | 상세 |
|---|---|---|
| 발행 훅 | 7개 | [발행 훅](docs/extension-points.md#발행-훅) |
| 구독 훅 | 7개 | [구독 훅](docs/extension-points.md#구독-훅) |
| 훅 리스너 | 1개 | [훅 리스너](docs/extension-points.md#훅-리스너) |
| 레이아웃 확장 | 0개 | [레이아웃 확장](docs/extension-points.md#레이아웃-확장) |
| 미들웨어 | 0개 | [미들웨어](docs/extension-points.md#미들웨어) |
| 브로드캐스트 채널 | 0개 | [브로드캐스트 채널](docs/extension-points.md#브로드캐스트-채널) |
| 스케줄 | 0개 | [스케줄](docs/extension-points.md#스케줄) |
| 알림 정의 | 0개 | [알림 정의](docs/extension-points.md#알림-정의) |
<!-- @generated:extension-points-summary END -->

별도의 레이아웃 확장, 미들웨어, 브로드캐스트 채널, 스케줄 및 알림 정의는 제공하지 않는다. TaskService의 성공한 operation에 대해 `TaskEventPublisher`가 7개 action hook을 best-effort로 발행하고, `TaskActivityLogListener`가 그 7개 hook을 구독해 G7 Activity Log에 전달한다. durable history나 Workflow/notification integration은 제공하지 않는다. 권한은 선언형 catalogue와 role grant 및 admin API route enforcement에 사용되며 member-facing policy, assignment history 및 상태 이력은 아직 제공하지 않는다. 공개 확장점은 구현과 동시에 이름, 인수, 시점, 실패 의미를 문서화한다.

## 5. 수정 시 동반 의무

- 동작 변경 시 `module.json`, `composer.json`, `CHANGELOG.md` 버전을 함께 검토한다.
- Task mutation은 성공 결과를 판단하는 TaskService와 operation hook을 발행하는 TaskEventPublisher 경계를 우회하지 않는다. 새 operation에 hook이 필요한지는 별도 설계 검토로 결정한다.
- 상태나 우선순위 값을 추가하면 backed Enum, 검증, 영속 계약과 문서를 함께 갱신한다.
- API 추가 시 FormRequest, Resource/Collection, ResponseHelper 응답과 `docs/api/` 레퍼런스를 함께 제공한다.
- 프런트엔드 표면 추가 시 한·영 번역, 정적 빌드, 활성 반영 및 캐시 상태를 검증한다.
- 다른 확장 연동은 공개 G7 계약으로만 구현하고 상대 확장을 수정하지 않는다.

## 6. 금지 패턴

- Board, Page, Workflow, CRM, Reservation 또는 다른 확장 테이블로의 외래 키
- 다른 확장의 내부 클래스나 비공개 구현에 대한 결합
- 활성 모듈 디렉터리 또는 생성 자산 직접 수정
- 레이아웃의 하드코딩된 표시 문장
- Task 상태와 기계 자동화 실행 상태의 혼합

## 7. 테스트 실행

<!-- @generated:test-commands START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 종류 | 개수 | 위치 |
|---|---|---|
| PHPUnit | 0개 | — |
| Vitest | 0개 | — |
| Playwright | 0개 | — |
| 시나리오 매니페스트 | 0개 | — |
<!-- @generated:test-commands END -->

운영 체크아웃에서는 구조적 격리가 입증되기 전 PHPUnit, Vitest, E2E 또는 Laravel 테스트 bootstrap을 실행하지 않는다. 현재 모듈은 테스트 스위트를 포함하지 않으며 순수 PHP lint와 JSON 파싱만 안전한 정적 검증 대상으로 사용한다. API 표면 변경 후에는 `api:docgen`을 실행하지 않고 독립적인 소스 검토를 먼저 수행한다.

## 8. 문서 목차

<!-- @generated:docs-index START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 문서 | 내용 | 상태 |
|---|---|---|
| [docs/README.md](docs/README.md) | 문서 통합 목차와 실측 집계 | ✅ |
| [docs/architecture.md](docs/architecture.md) | 설계 의도·계층 지도·디렉토리 맵 | ✅ |
| [docs/extension-points.md](docs/extension-points.md) | 발행/구독 훅·미들웨어·채널·스케줄 | ✅ |
| [docs/data-model.md](docs/data-model.md) | 모델·소유 테이블·마이그레이션·Enum | ✅ |
| [docs/settings.md](docs/settings.md) | 설정 스키마·권한·메뉴·라우트·의존 관계 | ✅ |
| [docs/frontend.md](docs/frontend.md) | 레이아웃·액션 핸들러·전역 진입점·에셋 | ✅ |
| [docs/editor-spec.md](docs/editor-spec.md) | 레이아웃 편집기에 선언한 팔레트·컨트롤·샘플 데이터 | ✅ |
| [docs/api/](docs/api/README.md) | API 레퍼런스 (엔드포인트별 파라미터·응답 필드) | ✅ |
| [CHANGELOG.md](CHANGELOG.md) | 변경 이력 | ✅ |
<!-- @generated:docs-index END -->

- [운영자 README](README.md)
- [개발자 문서](docs/README.md)
- [아키텍처](docs/architecture.md)
- [확장점](docs/extension-points.md)
- [데이터 모델](docs/data-model.md)
- [설정 및 권한](docs/settings.md)
- [프런트엔드](docs/frontend.md)
- [레이아웃 편집기](docs/editor-spec.md)
- [관리자 Task operation API](docs/api/admin-task-operations.md)
