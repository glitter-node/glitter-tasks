# Glitter Tasks 프런트엔드

## 레이아웃

<!-- @generated:layouts START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
레이아웃 4개 (루트: `resources/layouts`).

| 그룹 | 개수 |
|---|---|
| `admin` | 4개 |

| 레이아웃 | 그룹 | 종류 | extends |
|---|---|---|---|
| `admin_tasks_create` | `admin` | 화면 | `_admin_base` |
| `admin_tasks_detail` | `admin` | 화면 | `_admin_base` |
| `admin_tasks_edit` | `admin` | 화면 | `_admin_base` |
| `admin_tasks_list` | `admin` | 화면 | `_admin_base` |
<!-- @generated:layouts END -->

관리자 Task 목록·상세·생성·수정 레이아웃을 제공한다. 화면은 기존 admin API를 data source와 action 대상으로 재사용하며, 생성·상세 수정·assignment·lifecycle control은 기존 permission으로 게이팅된다.

## 액션 핸들러

<!-- @generated:handlers START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_등록하는 액션 핸들러가 없습니다._
<!-- @generated:handlers END -->

등록하는 액션 핸들러가 없다.

## 전역 진입점

<!-- @generated:frontend-entry START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_프론트 엔트리포인트가 없습니다._
<!-- @generated:frontend-entry END -->

현재 별도 프런트엔드 런타임 코드나 전역 객체는 없으며, 관리자 화면은 G7 JSON Layout과 기존 API data source/action만 사용한다. custom JavaScript/CSS나 build asset은 추가하지 않는다.

향후 프런트엔드 런타임에서 안정적인 공개 소비자 API의 전역 이름은 `GlitterTasks`이며, 브라우저에서는 `window.GlitterTasks`로 참조하고 `globalThis.GlitterTasks`는 이에 해당하는 전역 참조이다. 현재는 어느 것도 존재하지 않는다.

향후 G7 액션 핸들러는 모듈 자산 재등록 계약을 별도로 따르며, 내부 핸들러 부트스트랩 및 재등록에는 `window.__GlitterTasks.initModule()`을 사용한다. 이는 공개 API인 `window.GlitterTasks`와 구별되며, 현재는 핸들러와 JavaScript가 모두 존재하지 않는다.

전역이 필요해지면 모듈 `resources/js` 엔트리에서 IIFE 빌드로 노출하고 `module.json`의 asset 선언과 G7 모듈 빌드·업데이트 수명주기를 사용한다.

## 에셋

<!-- @generated:assets START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_프론트 에셋이 없습니다._
<!-- @generated:assets END -->

JS, CSS, package manifest 또는 build 산출물을 추가하지 않는다. 따라서 루트 `public/build`나 활성 모듈 자산을 변경하지 않는다.
