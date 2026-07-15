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
            if (Schema::hasIndex('wallets', 'wallets_company_id_name_unique')) {
                $table->dropUnique(['company_id', 'name']);
            }

            if (Schema::hasIndex('wallets', 'wallets_company_id_archived_at_index')) {
                $table->dropIndex(['company_id', 'archived_at']);
            }

            if (Schema::hasForeignKey('wallets', ['company_id'])) {
                $table->dropForeign(['company_id']);
            }

            if (Schema::hasColumn('wallets', 'company_id')) {
                $table->dropColumn('company_id');
            }

            if (! Schema::hasIndex('wallets', 'wallets_name_unique')) {
                $table->unique('name');
            }

            if (! Schema::hasIndex('wallets', 'wallets_archived_at_index')) {
                $table->index('archived_at');
            }
        });

        Schema::table('categories', function (Blueprint $table): void {
            if (Schema::hasIndex('categories', 'categories_company_id_parent_id_kind_name_unique')) {
                $table->dropUnique(['company_id', 'parent_id', 'kind', 'name']);
            }

            if (Schema::hasIndex('categories', 'categories_company_id_kind_archived_at_index')) {
                $table->dropIndex(['company_id', 'kind', 'archived_at']);
            }

            if (Schema::hasForeignKey('categories', ['company_id'])) {
                $table->dropForeign(['company_id']);
            }

            if (Schema::hasColumn('categories', 'company_id')) {
                $table->dropColumn('company_id');
            }

            if (! Schema::hasIndex('categories', 'categories_parent_id_kind_name_unique')) {
                $table->unique(['parent_id', 'kind', 'name']);
            }

            if (! Schema::hasIndex('categories', 'categories_kind_archived_at_index')) {
                $table->index(['kind', 'archived_at']);
            }
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
