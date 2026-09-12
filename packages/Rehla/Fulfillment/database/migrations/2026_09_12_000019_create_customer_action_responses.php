<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_action_responses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('action_request_id')->unique()->constrained('customer_action_requests');
            $table->uuid('account_id');
            $table->text('message')->nullable();
            $table->uuid('document_id')->nullable();
            $table->timestampTz('submitted_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_action_responses');
    }
};
