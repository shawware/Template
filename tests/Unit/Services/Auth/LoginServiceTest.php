<?php

namespace Tests\Unit\Services\Auth;

use App\Models\User;
use App\Repositories\UserLoginRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\AuditLoggerInterface;
use App\Services\Auth\LoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class LoginServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_returns_invalid_for_unknown_email(): void
    {
        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $userRepo->shouldReceive('findByEmail')->once()->with('nobody@example.com')->andReturnNull();

        $loginRepo = Mockery::mock(UserLoginRepositoryInterface::class);
        $loginRepo->shouldReceive('log')->never();

        $audit = Mockery::mock(AuditLoggerInterface::class);
        $audit->shouldReceive('log')->never();

        $service = new LoginService($userRepo, $loginRepo, $audit);

        $this->assertSame('invalid', $service->attempt('nobody@example.com', 'password', '127.0.0.1'));
    }

    public function test_attempt_returns_invalid_for_wrong_password(): void
    {
        $user = User::factory()->make(['password' => Hash::make('correct-password')]);

        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $userRepo->shouldReceive('findByEmail')->once()->andReturn($user);

        $loginRepo = Mockery::mock(UserLoginRepositoryInterface::class);
        $loginRepo->shouldReceive('log')->never();

        $audit = Mockery::mock(AuditLoggerInterface::class);
        $audit->shouldReceive('log')->never();

        $service = new LoginService($userRepo, $loginRepo, $audit);

        $this->assertSame('invalid', $service->attempt($user->email, 'wrong-password', '127.0.0.1'));
    }

    public function test_attempt_returns_suspended_for_suspended_user(): void
    {
        $user = User::factory()->make([
            'password' => Hash::make('correct-password'),
            'is_suspended' => true,
        ]);

        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $userRepo->shouldReceive('findByEmail')->once()->andReturn($user);

        $loginRepo = Mockery::mock(UserLoginRepositoryInterface::class);
        $loginRepo->shouldReceive('log')->never();

        $audit = Mockery::mock(AuditLoggerInterface::class);
        $audit->shouldReceive('log')->never();

        $service = new LoginService($userRepo, $loginRepo, $audit);

        $this->assertSame('suspended', $service->attempt($user->email, 'correct-password', '127.0.0.1'));
    }

    public function test_attempt_logs_in_and_records_login_on_success(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $userRepo->shouldReceive('findByEmail')->once()->andReturn($user);

        $loginRepo = Mockery::mock(UserLoginRepositoryInterface::class);
        $loginRepo->shouldReceive('log')->once()->with($user->id, '203.0.113.5', 'password');

        $audit = Mockery::mock(AuditLoggerInterface::class);
        $audit->shouldReceive('log')->once()->withArgs(
            fn ($action, $entityType, $entityId) => $entityType === 'user' && $entityId === (string) $user->id
        );

        $service = new LoginService($userRepo, $loginRepo, $audit);

        $this->assertSame('ok', $service->attempt($user->email, 'correct-password', '203.0.113.5'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_record_logout_logs_the_audit_action(): void
    {
        $user = User::factory()->create();

        $userRepo = Mockery::mock(UserRepositoryInterface::class);
        $loginRepo = Mockery::mock(UserLoginRepositoryInterface::class);

        $audit = Mockery::mock(AuditLoggerInterface::class);
        $audit->shouldReceive('log')->once()->withArgs(
            fn ($action, $entityType, $entityId) => $entityType === 'user' && $entityId === (string) $user->id
        );

        $service = new LoginService($userRepo, $loginRepo, $audit);
        $service->recordLogout($user);
    }
}
