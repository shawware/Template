<?php

namespace App\Services;

interface AuditLoggerInterface
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function log(
        string $action,
        string $entityType,
        string $entityId,
        ?string $description = null,
        ?array $changes = null,
    ): void;
}
