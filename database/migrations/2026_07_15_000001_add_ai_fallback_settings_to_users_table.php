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
            $table->string('ai_fallback_provider')->nullable()->after('ai_base_url');
            $table->string('ai_fallback_model')->nullable()->after('ai_fallback_provider');
            $table->text('ai_fallback_api_key')->nullable()->after('ai_fallback_model');
            $table->string('ai_fallback_base_url')->nullable()->after('ai_fallback_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['ai_fallback_provider', 'ai_fallback_model', 'ai_fallback_api_key', 'ai_fallback_base_url']);
        });
    }
};
