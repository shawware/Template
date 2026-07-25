<?php

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function log(array $data): void;

    public function paginate(int $perPage = 50): LengthAwarePaginator;
}
