<?php

use Illuminate\Support\Facades\Route;
use Modules\Glitter\Tasks\Http\Controllers\Admin\TaskController;

/*
|--------------------------------------------------------------------------
| Glitter Tasks Module API Routes
|--------------------------------------------------------------------------
|
| ModuleRouteServiceProvider applies the api/modules/glitter-tasks prefix.
|
*/
Route::prefix('admin/tasks')
    ->middleware(['auth:sanctum', 'throttle:600,1'])
    ->name('admin.tasks.')
    ->group(function (): void {
        Route::get('/', [TaskController::class, 'index'])
            ->middleware('permission:admin,glitter-tasks.tasks.read')
            ->name('index');

        Route::post('/', [TaskController::class, 'store'])
            ->middleware('permission:admin,glitter-tasks.tasks.create')
            ->name('store');

        Route::patch('/{task}', [TaskController::class, 'update'])
            ->whereNumber('task')
            ->middleware('permission:admin,glitter-tasks.tasks.update')
            ->name('update');

        Route::get('/{task}', [TaskController::class, 'show'])
            ->whereNumber('task')
            ->middleware('permission:admin,glitter-tasks.tasks.read')
            ->name('show');

        Route::post('/{task}/assign', [TaskController::class, 'assign'])
            ->whereNumber('task')
            ->middleware('permission:admin,glitter-tasks.tasks.assign')
            ->name('assign');

        Route::post('/{task}/unassign', [TaskController::class, 'unassign'])
            ->whereNumber('task')
            ->middleware('permission:admin,glitter-tasks.tasks.assign')
            ->name('unassign');

        Route::post('/{task}/start', [TaskController::class, 'start'])
            ->whereNumber('task')
            ->middleware('permission:admin,glitter-tasks.tasks.transition')
            ->name('start');

        Route::post('/{task}/complete', [TaskController::class, 'complete'])
            ->whereNumber('task')
            ->middleware('permission:admin,glitter-tasks.tasks.transition')
            ->name('complete');

        Route::post('/{task}/cancel', [TaskController::class, 'cancel'])
            ->whereNumber('task')
            ->middleware('permission:admin,glitter-tasks.tasks.transition')
            ->name('cancel');
    });
