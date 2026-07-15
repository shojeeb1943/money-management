<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligations', function (Blueprint $table): void {
            $table->id();
            $table->string('kind');
            $table->string('label');
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->bigInteger('remaining');
            $table->char('currency', 3)->default('BDT');
            $table->string('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['kind', 'status']);
        });

        Schema::create('obligation_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->string('direction');
            $table->date('date');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_payments');
        Schema::dropIfExists('obligations');
    }
};
