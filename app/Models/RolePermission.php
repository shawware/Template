<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['role_id', 'permission'])]
class RolePermission extends Model
{
    public $timestamps = false;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
