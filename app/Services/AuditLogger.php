<?php

namespace App\Services;

use App\Repositories\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AuditLogger implements AuditLoggerInterface
{
    public function __construct(private AuditLogRepositoryInterface $repo) {}

    public function log(
        string $action,
        string $entityType,
        string $entityId,
        ?string $description = null,
        ?array $changes = null,
    ): void {
        $this->repo->log([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'changes' => $changes,
        ]);
    }
}
