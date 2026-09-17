<?php

namespace App\Policies;

use App\Models\AnnotationSource;
use App\Models\User;

class AnnotationSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view.annotation-sources');
    }

    public function view(User $user, AnnotationSource $source): bool
    {
        return $user->can('view.annotation-sources');
    }

    public function create(User $user): bool
    {
        return $user->can('create.annotation-sources');
    }

    public function update(User $user, AnnotationSource $source): bool
    {
        return $user->can('update.annotation-sources');
    }

    public function delete(User $user, AnnotationSource $source): bool
    {
        return $user->can('delete.annotation-sources');
    }
}
