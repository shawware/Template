<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->unsignedBigInteger('role_id')->nullable()->after('remember_token');
            $table->string('google_id', 100)->nullable()->unique()->after('role_id');
            $table->string('avatar_url', 500)->nullable()->after('google_id');
            $table->boolean('is_suspended')->default(false)->after('avatar_url');
            $table->boolean('auto_registered')->default(false)->after('is_suspended');
            $table->timestamp('reviewed_at')->nullable()->after('auto_registered');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'google_id', 'avatar_url', 'is_suspended', 'auto_registered', 'reviewed_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
