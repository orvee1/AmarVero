<?php

namespace App\Policies;

class OrderNotePolicy extends AdminPolicy
{
    protected string $permissionGroup = 'customer-notes';

    /**
     * @var array<string, string|list<string>>
     */
    protected array $permissions = [
        'view' => 'customer-notes.manage',
        'create' => 'customer-notes.manage',
        'update' => 'customer-notes.manage',
        'delete' => 'customer-notes.manage',
    ];
}
