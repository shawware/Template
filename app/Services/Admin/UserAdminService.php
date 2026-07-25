<?php

namespace App\Services\Admin;

use App\Repositories\UserRepositoryInterface;
use App\Services\AuditActions;
use App\Services\AuditLoggerInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserAdminService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private AuditLoggerInterface $audit,
    ) {}

    public function list(): LengthAwarePaginator
    {
        return $this->userRepo->paginate();
    }

    /**
     * @param  array{role_id?: ?int, is_suspended?: bool}  $data
     */
    public function update(int $userId, array $data): void
    {
        $user = $this->userRepo->findById($userId);
        if (! $user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        $suspending = array_key_exists('is_suspended', $data) && $data['is_suspended'] && ! $user->is_suspended;
        $unsuspending = array_key_exists('is_suspended', $data) && ! $data['is_suspended'] && $user->is_suspended;

        $this->userRepo->update($userId, $data);

        $action = match (true) {
            $suspending => AuditActions::USER_SUSPENDED,
            $unsuspending => AuditActions::USER_UNSUSPENDED,
            default => AuditActions::USER_UPDATED,
        };
        $this->audit->log($action, 'user', (string) $userId, "Updated: {$user->name}");
    }

    /**
     * Clear the "needs review" badge for an auto-registered user.
     */
    public function review(int $userId): void
    {
        $user = $this->userRepo->findById($userId);
        if (! $user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        $this->userRepo->update($userId, ['reviewed_at' => now()]);
    }

    public function delete(int $userId): void
    {
        $user = $this->userRepo->findById($userId);
        if (! $user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        if ($user->google_id) {
            throw ValidationException::withMessages(['user' => 'Google-linked accounts must be suspended, not deleted — deleting would let them re-register.']);
        }

        $this->userRepo->delete($userId);
        $this->audit->log(AuditActions::USER_DELETED, 'user', (string) $userId, "Deleted: {$user->name}");
    }
}
