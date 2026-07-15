<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('ai_provider')->nullable()->after('current_company_id');
            $table->string('ai_model')->nullable()->after('ai_provider');
            $table->text('ai_api_key')->nullable()->after('ai_model');
            $table->string('ai_base_url')->nullable()->after('ai_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['ai_provider', 'ai_model', 'ai_api_key', 'ai_base_url']);
        });
    }
};
