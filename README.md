# Glitter Tasks

**Gnuboard7 third-party module · glitter-tasks · Glitter.kr**

<!-- @generated:badges START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
<p align="center">
  <img src="https://img.shields.io/badge/version-0.13.0-0066FF?style=flat-square" alt="version 0.13.0">
  <img src="https://img.shields.io/badge/type-%EB%AA%A8%EB%93%88-555555?style=flat-square" alt="type 모듈">
  <img src="https://img.shields.io/badge/%EA%B7%B8%EB%88%84%EB%B3%B4%EB%93%9C7-%3E%3D7.0.11-1F883D?style=flat-square" alt="그누보드7 &gt;=7.0.11">
  <img src="https://img.shields.io/badge/license-MIT-8250DF?style=flat-square" alt="license MIT">
</p>
<!-- @generated:badges END -->

## 소개

Glitter Tasks는 사람이 수행하는 운영 작업의 생성, 할당, 추적, 완료를 위한 독립형 Gnuboard7 모듈이다. 작업 상태의 단일 진실 공급원은 이 모듈이 소유하며 Glitter Workflow 없이도 사용할 수 있도록 설계한다.

## 주요 기능

- Gnuboard7 모듈 수명주기에 맞는 설치 가능한 기반
- 다른 확장에 대한 의존성이 없는 독립 모듈 경계
- Task 도메인과 서비스 계층을 위한 PSR-4 기반 구조

현재 배포에는 작업 영속화, 명시적인 lifecycle 전이, 생성 후 단일 assignee 할당 기반과 G7 선언형 permission catalogue, admin-context JSON API 기반이 포함된다. 0.11.0은 기존 API를 소비하는 관리자 Task 목록·상세·생성·상세 수정 화면과 할당·재할당·해제·시작·완료·취소 UI를 제공하고, 0.12.0은 성공한 Task operation을 위한 7개 선택적 action hook을 제공한다. 0.13.0은 이 7개 hook을 G7 전역 Activity Log에 연결하는 best-effort listener를 제공한다. 기록은 scalar snapshot과 occurred_at을 사용하지만 durable event history, notifications 및 Workflow 연동은 포함되지 않는다.

## 동작 방식

```text
Gnuboard7 module discovery
  -> Glitter Tasks Module metadata and provider
  -> TaskService
  -> TaskRepositoryInterface -> TaskRepository -> Task model
  -> atomic lifecycle transition / assignment persistence
```

## 요구 사항

<!-- @generated:requirements START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 항목 | 값 |
|---|---|
| 그누보드7 코어 | `>=7.0.11` |
| PHP | `^8.2` |
<!-- @generated:requirements END -->

| 항목 | 값 |
|---|---|
| Gnuboard7 | `>=7.0.11` |
| PHP | `^8.2` |
| 다른 모듈 또는 플러그인 | 없음 |

## 설치

<!-- @generated:install START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
```bash
# 번들 설치 (코어에 동봉된 소스에서 설치)
php artisan module:install glitter-tasks

# 활성화
php artisan module:activate glitter-tasks

# 업데이트 (번들 소스 기준 강제 반영)
php artisan module:update glitter-tasks --force
```
<!-- @generated:install END -->

운영 환경 적용은 배포 승인 후 Gnuboard7 수명주기 명령으로 수행한다.

```bash
/usr/local/bin/php83 artisan module:install glitter-tasks
/usr/local/bin/php83 artisan module:activate glitter-tasks
```

번들 소스 업데이트는 다음 명령으로 활성 설치본에 반영한다.

```bash
/usr/local/bin/php83 artisan module:update glitter-tasks --force
```

## 관리자 설정

<!-- @generated:settings-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_별도의 관리자 설정 항목이 없습니다._
<!-- @generated:settings-summary END -->

별도의 관리자 설정 항목이 없다.

## 사용 방법

현재 버전은 Task 영속화 기반과 `start`, `complete`, `cancel` lifecycle operation을 제공한다. 일반 human task 생성은 인증된 현재 사용자를 creator로 사용하며, 제목이 필요하고 상태는 항상 `open`, 우선순위는 항상 `normal`, `started_at`과 `completed_at`은 항상 NULL로 시작한다. `start`는 `open`에서 `in_progress`, `complete`는 `in_progress`에서 `completed`, `cancel`은 `open` 또는 `in_progress`에서 `cancelled`로만 전이한다. reopen과 `open`에서 `completed`로의 직접 전이는 없다.

Lifecycle 전이는 TaskService가 소유하고 expected status 조건부 UPDATE로 원자적으로 저장한다. assignment도 TaskService의 별도 경계이며 row lock으로 expected status와 assignee를 비교한 뒤 `assignee_id`만 저장한다. assignment는 생성 시 지정하지 않고 `open` 또는 `in_progress`에서만 할당·재할당·해제할 수 있으며, terminal task에서는 거절한다. 동일 대상 재할당과 이미 비어 있는 해제는 valid no-op이다. stale 또는 잘못된 상태와 유효하지 않은 사용자 대상은 typed exception으로 거절한다. assignment는 status, `due_at`, `started_at`, `completed_at`을 바꾸지 않는다. 취소는 `completed_at`을 사용하지 않으며 `cancelled_at`도 없다. 0.11.0 admin API 목록은 `tasks.read`로 보호되며 `status`, `priority`, `assignee_id`, `unassigned`, `created_by` filter를 AND로 결합한다. 정렬은 `created_at DESC`, `id DESC`로 고정되고 bounded pagination을 유지한다. 상세 수정은 `tasks.update`, 생성은 `tasks.create`, 할당 operation은 `tasks.assign`, lifecycle operation은 `tasks.transition`을 사용한다. API는 인증과 route permission middleware로 보호되고, TaskService 자체는 permission-aware하지 않다. 관리자 브라우저는 이 API를 사용하는 목록·상세·생성·상세 수정 및 관리 operation 화면이며 mutation control은 기존 permission으로 제한된다. delete, search, date/overdue filter, configurable sorting과 member-facing API는 제공하지 않는다.

## 다른 확장과의 연동

<!-- @generated:integrations START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
**이 확장이 의존하는 확장**

없음 — 코어만으로 동작합니다.

**이 확장에 의존하는 확장** (이 확장을 비활성화하면 함께 영향을 받습니다)

없음.
<!-- @generated:integrations END -->

필수 확장이 없으며 Glitter Workflow를 포함한 다른 확장에 의존하지 않는다. source 참조는 문자열로만 저장하며 다른 확장 테이블에 외래 키를 만들지 않는다. 향후 연동은 확인된 G7 공개 훅, 이벤트 또는 API만 사용하며 상대 확장의 소스 변경을 요구하지 않는다.

## 문서

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

- [개발자 문서](docs/README.md)
- [아키텍처](docs/architecture.md)
- [확장점](docs/extension-points.md)
- [데이터 모델](docs/data-model.md)
- [설정 및 권한](docs/settings.md)
- [프런트엔드](docs/frontend.md)
- [레이아웃 편집기](docs/editor-spec.md)
- [관리자 Task operation API](docs/api/admin-task-operations.md)

## 트러블슈팅

- 관리자 화면은 `tasks.read` 권한으로 보호되는 Task 목록·상세 화면이며, 생성·상세 수정·할당 및 lifecycle operation 진입점은 각각 기존 `tasks.create`, `tasks.update`, `tasks.assign`, `tasks.transition` 권한으로 보호된다. 화면은 기존 API를 통해서만 변경을 수행한다.
- 설치 전에는 활성 모듈 디렉터리나 런타임 권한에 반영되지 않는다.
- 활성 디렉터리를 직접 수정하지 말고 bundled 소스와 모듈 수명주기 명령을 사용한다.

## 변경 이력

[CHANGELOG.md](CHANGELOG.md)를 참고한다.

## 라이선스

MIT License. Copyright © 2026 Glitter.kr. [LICENSE](LICENSE)를 참고한다.
