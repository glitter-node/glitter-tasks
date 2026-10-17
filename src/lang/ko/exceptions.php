<?php

return [
    'invalid_transition' => '상태가 ":current"인 작업은 :operation할 수 없습니다. 허용 상태: :expected.',
    'stale_transition' => '작업이 저장되기 전에 변경되어 :operation 전이를 적용할 수 없습니다. 허용 상태: :expected.',
    'invalid_assignment_target' => '활성 상태이고 탈퇴하지 않은 사용자만 작업의 할당 대상이 될 수 있어 :operation할 수 없습니다.',
    'assignment_status_not_allowed' => '상태가 ":current"인 작업은 :operation할 수 없습니다. 할당 변경은 open 또는 in_progress 상태에서만 가능합니다.',
    'stale_assignment' => '저장 전에 작업의 할당 또는 상태가 변경되어 :operation할 수 없습니다.',
    'invalid_update_fields' => '수정할 수 있는 작업 필드가 없습니다.',
    'invalid_update_title' => '작업 제목은 비어 있을 수 없습니다.',
    'invalid_update_priority' => '작업 우선순위가 올바르지 않습니다.',
    'update_terminal' => '종료된 작업은 수정할 수 없습니다.',
    'stale_update' => '작업 상세를 저장하기 전에 작업이 종료 상태로 변경되었습니다.',
];
