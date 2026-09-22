# Glitter Tasks 개발자 문서

## 문서 목차

<!-- @generated:stats START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
**훅 수**: 7 · **구독 훅 수**: 7 · **라우트 수**: 9 · **모델 수**: 1 · **테이블 수**: 1 · **마이그레이션 수**: 2 · **레이아웃 수**: 4 · **핸들러 수**: 0
<!-- @generated:stats END -->

<!-- @generated:doc-toc START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 문서 | 내용 |
|---|---|
| [architecture.md](architecture.md) | 설계 의도·계층 지도·디렉토리 맵 |
| [extension-points.md](extension-points.md) | 발행/구독 훅·미들웨어·채널·스케줄 |
| [data-model.md](data-model.md) | 모델·소유 테이블·마이그레이션·Enum |
| [settings.md](settings.md) | 설정 스키마·권한·메뉴·라우트·의존 관계 |
| [frontend.md](frontend.md) | 레이아웃·액션 핸들러·전역 진입점·에셋 |
| [editor-spec.md](editor-spec.md) | 레이아웃 편집기에 선언한 팔레트·컨트롤·샘플 데이터 |
| [api/](api/README.md) | API 레퍼런스 |
| [../AGENTS.md](../AGENTS.md) | 에이전트·확장개발자 진입점 |
| [../README.md](../README.md) | 사람(도입검토자·운영자) 진입점 |
<!-- @generated:doc-toc END -->

| 문서 | 내용 |
|---|---|
| [architecture.md](architecture.md) | 모듈 소유권과 계층 경계 |
| [extension-points.md](extension-points.md) | 공개 확장점 현황 |
| [data-model.md](data-model.md) | Task 개념 계약과 미구현 데이터 표면 |
| [settings.md](settings.md) | 설정, 권한, 메뉴, 라우트, 의존 관계 |
| [frontend.md](frontend.md) | 레이아웃, 핸들러, 전역 진입점, 에셋 |
| [editor-spec.md](editor-spec.md) | 레이아웃 편집기 선언 현황 |
| [api/admin-task-operations.md](api/admin-task-operations.md) | 관리자 Task operation API |
| [../README.md#주요-기능](../README.md#주요-기능) | 기존 admin API를 소비하는 관리자 Task 관리 브라우저 |

현재 모듈은 Task 영속화와 `start` / `complete` / `cancel` lifecycle, 생성 후 하나의 선택적 assignee를 할당·재할당·해제하는 기반 및 G7 선언형 `create` / `read` / `assign` / `transition` / `update` permission catalogue를 제공한다. 0.11.0 admin API 목록은 `tasks.read`로 보호되며 `status`, `priority`, `assignee_id`, `unassigned`, `created_by` filter를 지원한다. 필터는 AND로 결합하고 bounded pagination과 `created_at DESC`, `id DESC` 고정 정렬을 유지한다. 관리자 브라우저는 `tasks.read`로 목록·상세를 보호하고, `tasks.create` 생성, `tasks.update` 상세 수정, `tasks.assign` 할당·해제, `tasks.transition` 시작·완료·취소 UI를 기존 API에 위임한다. 사용자 존재 여부를 조회하지 않는 ID discovery filter이며 ownership authorization이 아니다. Phase 12는 7개 best-effort Task operation hook을 제공하고, Phase 13은 이를 G7 전역 Activity Log에 연결한다. durable/domain-event infrastructure, persisted event/activity history, notifications, Workflow integration, 검색, 날짜/overdue filter, configurable sorting, generic delete, member-facing authorization 및 custom frontend runtime은 deferred scope다.
