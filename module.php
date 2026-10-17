<?php

namespace Modules\Glitter\Tasks;

use App\Extension\AbstractModule;
use Modules\Glitter\Tasks\Listeners\TaskActivityLogListener;

class Module extends AbstractModule
{
    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return [
            'author' => 'Glitter.kr',
            'license' => 'MIT',
            'keywords' => ['tasks', 'operations', 'human-work'],
        ];
    }

    /**
     * 모듈 권한 목록 반환 (계층형 구조, 다국어 지원)
     *
     * @return array<string, mixed>
     */
    public function getPermissions(): array
    {
        return [
            'name' => [
                'ko' => 'Glitter Tasks',
                'en' => 'Glitter Tasks',
            ],
            'description' => [
                'ko' => '작업 도메인 권한',
                'en' => 'Task domain permissions',
            ],
            'categories' => [
                [
                    'identifier' => 'tasks',
                    'name' => [
                        'ko' => '작업',
                        'en' => 'Tasks',
                    ],
                    'description' => [
                        'ko' => '작업 생성, 조회, 수정, 할당 및 lifecycle 전이 권한',
                        'en' => 'Task creation, reading, editing, assignment, and lifecycle transition permissions',
                    ],
                    'permissions' => [
                        [
                            'action' => 'create',
                            'name' => [
                                'ko' => '작업 생성',
                                'en' => 'Create Tasks',
                            ],
                            'description' => [
                                'ko' => '작업을 생성할 수 있는 권한',
                                'en' => 'Permission to create tasks',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'manager'],
                        ],
                        [
                            'action' => 'read',
                            'name' => [
                                'ko' => '작업 조회',
                                'en' => 'View Tasks',
                            ],
                            'description' => [
                                'ko' => '모든 작업을 조회할 수 있는 권한',
                                'en' => 'Permission to view all tasks',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'manager'],
                        ],
                        [
                            'action' => 'assign',
                            'name' => [
                                'ko' => '작업 할당 관리',
                                'en' => 'Assign Tasks',
                            ],
                            'description' => [
                                'ko' => '작업을 할당, 재할당 및 해제할 수 있는 권한',
                                'en' => 'Permission to assign, reassign, and unassign tasks',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'manager'],
                        ],
                        [
                            'action' => 'update',
                            'name' => [
                                'ko' => '작업 상세 수정',
                                'en' => 'Edit Task Details',
                            ],
                            'description' => [
                                'ko' => '작업 제목, 설명, 우선순위 및 마감일을 수정할 수 있는 권한',
                                'en' => 'Permission to edit task title, description, priority, and due date',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'manager'],
                        ],
                        [
                            'action' => 'transition',
                            'name' => [
                                'ko' => '작업 lifecycle 전이',
                                'en' => 'Transition Tasks',
                            ],
                            'description' => [
                                'ko' => '작업을 시작, 완료 및 취소할 수 있는 권한',
                                'en' => 'Permission to start, complete, and cancel tasks',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin', 'manager'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * 관리자 메뉴 정의
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAdminMenus(): array
    {
        return [
            [
                'name' => [
                    'ko' => '작업 관리',
                    'en' => 'Task Management',
                ],
                'slug' => 'glitter-tasks',
                'url' => '/admin/tasks',
                'icon' => 'fas fa-tasks',
                'order' => 35,
                'permission' => 'glitter-tasks.tasks.read',
            ],
        ];
    }

    /**
     * @return array<class-string>
     */
    public function getHookListeners(): array
    {
        return [
            TaskActivityLogListener::class,
        ];
    }

}
