<?php

use App\Models\AiResponse;
use App\Models\AnnotationSource;
use App\Models\Role;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;
use Spatie\Activitylog\Models\Activity;

return [
    'resources' => [
        'users' => [
            'model' => User::class,
            'permissions' => [
                'view.users',
                'create.users',
                'update.users',
                'delete.users',
            ],
        ],
        'roles' => [
            'model' => Role::class,
            'permissions' => [
                'view.roles',
                'create.roles',
                'update.roles',
                'delete.roles',
            ],
        ],
        'audits' => [
            'model' => Audit::class,
            'permissions' => [
                'view.audits',
            ],
        ],
        'activity-logs' => [
            'model' => Activity::class,
            'permissions' => [
                'view.activity-logs',
            ],
        ],
        'annotation-sources' => [
            'model' => AnnotationSource::class,
            'permissions' => [
                'view.annotation-sources',
                'create.annotation-sources',
                'update.annotation-sources',
                'delete.annotation-sources',
            ],
        ],
        'ai-responses' => [
            'model' => AiResponse::class,
            'permissions' => [
                'view.ai-responses',
            ],
        ],
    ],
];
