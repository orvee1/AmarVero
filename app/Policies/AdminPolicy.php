<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Model;

abstract class AdminPolicy
{
    /**
     * @var array<string, string|list<string>>
     */
    protected array $permissions = [];

    protected string $permissionGroup = '';

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(AdminPermissions::SuperAdmin) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allows($user, 'restore');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allows($user, 'forceDelete');
    }

    public function manage(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'manage');
    }

    public function moderate(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'moderate');
    }

    public function updateStatus(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'updateStatus');
    }

    public function updatePayment(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'updatePayment');
    }

    public function cancel(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'cancel');
    }

    public function refund(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'refund');
    }

    public function export(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'export');
    }

    public function printInvoice(User $user, ?Model $model = null): bool
    {
        return $this->allows($user, 'printInvoice');
    }

    protected function allows(User $user, string $ability): bool
    {
        if (! $user->can(AdminPermissions::AdminAccess)) {
            return false;
        }

        foreach ($this->permissionCandidates($ability) as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function permissionCandidates(string $ability): array
    {
        $configured = $this->permissions[$ability] ?? null;

        if (is_string($configured)) {
            return [$configured];
        }

        if (is_array($configured)) {
            return $configured;
        }

        if ($this->permissionGroup === '') {
            return [];
        }

        $action = match ($ability) {
            'viewAny' => 'view',
            'restore', 'forceDelete' => 'delete',
            'updateStatus' => 'update-status',
            'updatePayment' => 'update-payment',
            'printInvoice' => 'print-invoice',
            default => $ability,
        };

        return array_values(array_unique([
            AdminPermissions::permission($this->permissionGroup, $action),
            AdminPermissions::permission($this->permissionGroup, 'manage'),
        ]));
    }
}
