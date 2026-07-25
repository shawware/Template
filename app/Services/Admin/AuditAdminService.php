<?php

namespace App\Services\Admin;

use App\Repositories\AuditLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditAdminService
{
    public function __construct(private AuditLogRepositoryInterface $auditRepo) {}

    public function list(): LengthAwarePaginator
    {
        return $this->auditRepo->paginate();
    }
}
