<?php

namespace Trail\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        return in_array($this->role, ['developer', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
