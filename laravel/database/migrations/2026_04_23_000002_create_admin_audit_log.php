<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_log', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_type', 16)->default('admin'); // admin|staff|system
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('actor_label', 128)->nullable();
            $table->string('action', 64);                        // e.g. channel.create, staff.reset_password
            $table->string('target_type', 32)->nullable();       // e.g. channel, staff, feed
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id']);
            $table->index(['action']);
            $table->index(['target_type', 'target_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_log');
    }
};
