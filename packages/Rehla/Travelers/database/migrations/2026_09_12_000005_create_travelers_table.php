<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travelers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('owner_id')->index();
            $table->string('full_name');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('passport_number');
            $table->string('normalized_passport_number')->unique();
            $table->date('passport_issued_at');
            $table->date('passport_expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travelers');
    }
};
