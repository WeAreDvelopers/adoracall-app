<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'active', 'empresa_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password', 'api_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Generate a new API token for the user.
     *
     * @return string
     */
    public function generateApiToken()
    {
        $this->api_token = bin2hex(random_bytes(40));
        $this->save();
        return $this->api_token;
    }

    // Relacionamentos

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }

    /**
     * Check if the user is a super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if the user is an admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a supervisor
     *
     * @return bool
     */
    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    /**
     * Check if the user is an operator
     *
     * @return bool
     */
    public function isOperador(): bool
    {
        return $this->role === 'operador';
    }

    /**
     * Check if the user has one of the specified roles
     *
     * @param  string  ...$roles
     * @return bool
     */
    public function hasRole(...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if the user has a specific permission based on their role
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getRolePermissions();
        return in_array($permission, $permissions);
    }

    /**
     * Get all permissions for the user's role
     *
     * @return array
     */
    public function getRolePermissions(): array
    {
        $allPermissions = [
            'super_admin' => [
                // Tudo que admin tem + gerenciar empresas
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'scripts.view', 'scripts.create', 'scripts.edit', 'scripts.delete', 'scripts.publish',
                'campaigns.view', 'campaigns.create', 'campaigns.edit', 'campaigns.delete',
                'campaigns.import', 'campaigns.activate', 'campaigns.pause', 'campaigns.resume', 'campaigns.cancel',
                'calls.start', 'calls.view',
                'analytics.view', 'analytics.export',
                'payments.view', 'payments.create', 'payments.edit', 'payments.delete', 'payments.resend',
                'queue.view', 'queue.control',
                'system.config', 'system.logs',
                'empresas.view', 'empresas.create', 'empresas.edit', 'empresas.delete',
                'empresas.manage_users',
            ],
            'admin' => [
                // Usuários
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',

                // Scripts
                'scripts.view',
                'scripts.create',
                'scripts.edit',
                'scripts.delete',
                'scripts.publish',

                // Campanhas
                'campaigns.view',
                'campaigns.create',
                'campaigns.edit',
                'campaigns.delete',
                'campaigns.import',
                'campaigns.activate',
                'campaigns.pause',
                'campaigns.resume',
                'campaigns.cancel',

                // Chamadas
                'calls.start',
                'calls.view',

                // Analytics
                'analytics.view',
                'analytics.export',

                // Propostas de Pagamento
                'payments.view',
                'payments.create',
                'payments.edit',
                'payments.delete',
                'payments.resend',

                // Fila
                'queue.view',
                'queue.control',

                // Sistema
                'system.config',
                'system.logs',
            ],

            'supervisor' => [
                // Usuários
                'users.view',

                // Scripts
                'scripts.view',

                // Campanhas
                'campaigns.view',
                'campaigns.create',
                'campaigns.edit',
                'campaigns.import',
                'campaigns.activate',
                'campaigns.pause',
                'campaigns.resume',

                // Chamadas
                'calls.start',
                'calls.view',

                // Analytics
                'analytics.view',
                'analytics.export',

                // Propostas de Pagamento
                'payments.view',
                'payments.resend',

                // Fila
                'queue.view',
            ],

            'operador' => [
                // Campanhas
                'campaigns.view',

                // Chamadas
                'calls.start',
                'calls.view',

                // Analytics (básico)
                'analytics.view',

                // Propostas de Pagamento
                'payments.view',

                // Fila
                'queue.view',
            ],
        ];

        return $allPermissions[$this->role] ?? [];
    }

    /**
     * Scope to get only active users
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to filter users by role
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
