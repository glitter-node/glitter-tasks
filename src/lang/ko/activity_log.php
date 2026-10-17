<?php

return [
    'action' => [
        'task' => [
            'created' => '작업 생성',
            'details_updated' => '작업 상세 수정',
            'assigned' => '작업 할당',
            'unassigned' => '작업 할당 해제',
            'started' => '작업 시작',
            'completed' => '작업 완료',
            'cancelled' => '작업 취소',
        ],
    ],
    'description' => [
        'task_created' => '작업 처리 완료 (작업 #:task_id)',
        'task_details_updated' => '작업 상세 수정 (작업 #:task_id, 필드: :changed_fields)',
        'task_assigned' => '작업 할당 변경 (작업 #:task_id, :from_assignee_id에서 :to_assignee_id로)',
        'task_unassigned' => '작업 할당 해제 (작업 #:task_id, :from_assignee_id에서 :to_assignee_id로)',
        'task_started' => '작업 시작 (작업 #:task_id, :from_status에서 :to_status로)',
        'task_completed' => '작업 완료 (작업 #:task_id, :from_status에서 :to_status로)',
        'task_cancelled' => '작업 취소 (작업 #:task_id, :from_status에서 :to_status로)',
    ],
];
