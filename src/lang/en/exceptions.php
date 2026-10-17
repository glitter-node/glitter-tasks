<?php

return [
    'invalid_transition' => 'Cannot :operation task from status ":current". Expected status: :expected.',
    'stale_transition' => 'Cannot apply the :operation transition because the task changed before it was saved. Expected status: :expected.',
    'invalid_assignment_target' => 'Cannot :operation task because the assignment target is not an active, non-withdrawn user.',
    'assignment_status_not_allowed' => 'Cannot :operation task from status ":current". Assignment changes are allowed only for open or in_progress tasks.',
    'stale_assignment' => 'Cannot :operation task because its assignment or status changed before the assignment was saved.',
    'invalid_update_fields' => 'The task update contains no supported editable fields.',
    'invalid_update_title' => 'Task title must not be empty.',
    'invalid_update_priority' => 'Task priority is invalid.',
    'update_terminal' => 'Terminal tasks cannot be edited.',
    'stale_update' => 'The task became terminal before its details were saved.',
];
