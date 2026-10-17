# Glitter Tasks 레이아웃 편집기 스펙

## 선언 요약

<!-- @generated:editor-spec-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 스펙(`editor-spec.json`)을 두지 않습니다. 편집기는 코어 기본 팔레트와 활성 템플릿의 스펙만으로 이 확장의 화면을 다룹니다._
<!-- @generated:editor-spec-summary END -->

`admin_tasks_list`, `admin_tasks_detail`, `admin_tasks_create` 및 `admin_tasks_edit` Admin JSON Layout과 각 API data source를 선언한다. 다만 dedicated editor manifest/editor-spec integration은 선언하지 않는다.

## 선언 블록

<!-- @generated:editor-spec-blocks START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_선언된 편집기 스펙 블록이 없습니다._
<!-- @generated:editor-spec-blocks END -->

선언 블록이 없다.

## 컴포넌트 팔레트

<!-- @generated:editor-spec-palette START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 팔레트에 항목을 추가하지 않습니다._
<!-- @generated:editor-spec-palette END -->

추가하는 컴포넌트나 팔레트 항목이 없다.

## 샘플 데이터와 페이지 상태

<!-- @generated:editor-spec-samples START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 편집기 스펙을 두지 않아 선언된 샘플 데이터·페이지 상태가 없습니다._

**프리뷰 샘플이 없는 `data_source` 2개** — 편집기 캔버스에서 이 자리만 빈 화면이 됩니다. 실제 화면은 정상이라 오류도 경고도 남지 않습니다.

`task` · `tasks`
<!-- @generated:editor-spec-samples END -->

Admin JSON Layout의 API data source는 있지만, 편집기 preview sample data 또는 editor-specific page state는 제공하지 않는다.

## 수정 시 동반 의무

<!-- @generated:editor-spec-obligations START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_이 확장은 아직 편집기 스펙을 두지 않습니다. 아래 변경이 생기면 `editor-spec.json` 을 신설합니다._

| 이런 변경을 했다면 | 편집기 스펙에서 함께 할 일 |
|---|---|
| 컴포넌트를 새로 만들었다 | `componentPalette` 에 항목 추가 · `componentCapabilities` 에 편집 역량 선언 · `nesting` 에 담길 자리 규정 |
| 레이아웃에 `data_sources` 를 추가했다 | `sampleData` 에 같은 ID 로 프리뷰 응답 추가 (없으면 편집기 캔버스만 빈 화면) |
| `_global.*` 을 새로 읽는다 | `sampleGlobal` 에 baseline 값 추가 |
| 빈 목록·오류 같은 화면 변종을 추가했다 | `states` 에 변종 추가 · `stateLabels` 에 친화 명칭 |
| 새 액션·조건 패턴을 도입했다 | `actionRecipes` / `conditionRecipes` 에 친화 명칭 등록 |
<!-- @generated:editor-spec-obligations END -->

Admin Layout이나 data source를 추가해도 dedicated editor preview sample, editor-specific page state 및 editor manifest는 제공하지 않는다. 지원되지 않은 레이아웃 문법을 만들지 않는다.
