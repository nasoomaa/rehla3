<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_executions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->uuid('account_id')->index();
            $table->uuid('traveler_id');
            $table->uuid('service_id');
            $table->uuid('form_version_id');
            $table->string('status')->default('order_received');
            $table->timestampTz('last_status_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('execution_status_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('execution_id')->constrained('service_executions');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type');
            $table->uuid('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('execution_internal_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('execution_id')->constrained('service_executions');
            $table->uuid('staff_id');
            $table->text('body');
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('customer_action_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('execution_id')->constrained('service_executions');
            $table->uuid('requested_by');
            $table->text('description_en');
            $table->text('description_ar');
            $table->string('required_document_purpose')->nullable();
            $table->string('status')->default('open');
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('resolved_at')->nullable();
        });

        Schema::create('execution_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('execution_id')->constrained('service_executions');
            $table->uuid('document_id');
            $table->string('purpose');
            $table->timestampTz('attached_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_documents');
        Schema::dropIfExists('customer_action_requests');
        Schema::dropIfExists('execution_internal_notes');
        Schema::dropIfExists('execution_status_history');
        Schema::dropIfExists('service_executions');
    }
};
