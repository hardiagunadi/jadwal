<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InstagramPost;
use App\Models\Personil;
use App\Support\RoleAccess;

class InstagramPostPolicy
{
    public function before(Personil $user): bool|null
    {
        return $user->role === UserRole::Admin ? true : null;
    }

    public function viewAny(Personil $user): bool
    {
        return $this->canManage($user);
    }

    public function view(Personil $user, InstagramPost $instagramPost): bool
    {
        return $this->canManage($user);
    }

    public function create(Personil $user): bool
    {
        return $this->canManage($user);
    }

    public function update(Personil $user, InstagramPost $instagramPost): bool
    {
        return $this->canManage($user);
    }

    public function delete(Personil $user, InstagramPost $instagramPost): bool
    {
        return $this->canManage($user);
    }

    protected function canManage(Personil $user): bool
    {
        return RoleAccess::routeMatchesAllowed(
            'filament.admin.resources.instagram-posts',
            RoleAccess::allowedPagesFor($user->role)
        );
    }
}
