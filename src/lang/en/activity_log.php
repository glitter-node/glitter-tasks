<?php

return [
    'action' => [
        'task' => [
            'created' => 'Task created',
            'details_updated' => 'Task details updated',
            'assigned' => 'Task assigned',
            'unassigned' => 'Task unassigned',
            'started' => 'Task started',
            'completed' => 'Task completed',
            'cancelled' => 'Task cancelled',
        ],
    ],
    'description' => [
        'task_created' => 'Task operation completed (Task #:task_id)',
        'task_details_updated' => 'Task details updated (Task #:task_id; fields: :changed_fields)',
        'task_assigned' => 'Task assignment changed (Task #:task_id; from :from_assignee_id to :to_assignee_id)',
        'task_unassigned' => 'Task assignment removed (Task #:task_id; from :from_assignee_id to :to_assignee_id)',
        'task_started' => 'Task started (Task #:task_id; :from_status to :to_status)',
        'task_completed' => 'Task completed (Task #:task_id; :from_status to :to_status)',
        'task_cancelled' => 'Task cancelled (Task #:task_id; :from_status to :to_status)',
    ],
];
