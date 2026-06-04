<?php

namespace Trail\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Trail\Enums\TrailUserRole;

class TrailUser extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canViewTechnicalContext(): bool
    {
        return TrailUserRole::tryFrom($this->role)?->canViewTechnicalContext() ?? false;
    }

    public function isAdmin(): bool
    {
        return $this->role === TrailUserRole::Admin->value;
    }
}
