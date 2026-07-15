<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'name']);
            $table->dropIndex(['company_id', 'archived_at']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            $table->unique('name');
            $table->index('archived_at');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'parent_id', 'kind', 'name']);
            $table->dropIndex(['company_id', 'kind', 'archived_at']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            $table->unique(['parent_id', 'kind', 'name']);
            $table->index(['kind', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropUnique('wallets_name_unique');
            $table->dropIndex('wallets_archived_at_index');

            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'archived_at']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique(['parent_id', 'kind', 'name']);
            $table->dropIndex(['kind', 'archived_at']);

            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();

            $table->unique(['company_id', 'parent_id', 'kind', 'name']);
            $table->index(['company_id', 'kind', 'archived_at']);
        });
    }
};
