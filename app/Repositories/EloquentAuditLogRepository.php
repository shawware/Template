<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function log(array $data): void
    {
        AuditLog::create($data);
    }

    public function paginate(int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::with('user')->latest('created_at')->paginate($perPage);
    }
}
