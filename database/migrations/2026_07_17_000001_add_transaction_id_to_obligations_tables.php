<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obligations', function (Blueprint $table): void {
            $table->foreignId('transaction_id')->nullable()->after('wallet_id')->constrained()->nullOnDelete();
        });

        Schema::table('obligation_payments', function (Blueprint $table): void {
            $table->foreignId('transaction_id')->nullable()->after('wallet_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('obligations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transaction_id');
        });

        Schema::table('obligation_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transaction_id');
        });
    }
};
