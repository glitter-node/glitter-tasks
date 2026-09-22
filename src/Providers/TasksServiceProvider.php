<?php

namespace Modules\Glitter\Tasks\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Glitter\Tasks\Repositories\TaskRepository;
use Modules\Glitter\Tasks\Repositories\TaskRepositoryInterface;

class TasksServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'glitter-tasks';

    protected array $repositories = [
        TaskRepositoryInterface::class => TaskRepository::class,
    ];
}
