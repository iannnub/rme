<?php

namespace App\Models;

use App\Enums\NurseType;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $role
 * @property NurseType|null $nurse_type
 * @property string|null $nip
 * @property string|null $sip
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'nurse_type',
        'nip',
        'sip',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logOnly(['name', 'username', 'email', 'role', 'nurse_type', 'nip', 'sip', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'role' => Role::class,
            'nurse_type' => NurseType::class,
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isKepalaRuangan(): bool
    {
        return $this->role === Role::KepalaRuangan;
    }

    public function isPerawat(): bool
    {
        return $this->role === Role::Perawat;
    }

    public function isDokter(): bool
    {
        return $this->isDokterOperator() || $this->isDokterAnestesi();
    }

    public function isDokterOperator(): bool
    {
        return $this->role === Role::DokterOperator;
    }

    public function isDokterAnestesi(): bool
    {
        return $this->role === Role::DokterAnestesi;
    }
}
