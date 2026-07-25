<?php

namespace App\Console\Commands;

use App\Services\SuperuserService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

#[Signature('app:create-superuser')]
#[Description('Create a superuser account')]
class CreateSuperuser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SuperuserService $service): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');

        $password = $this->secret('Password');
        $confirm = $this->secret('Confirm password');
        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        try {
            $service->create($name, $email, $password);
        } catch (ValidationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Superuser '{$email}' created successfully.");

        return self::SUCCESS;
    }
}
