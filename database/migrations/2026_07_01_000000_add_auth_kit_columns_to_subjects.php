<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = $this->resolveTableName();

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasColumn($tableName, 'provider')) {
                $table->string('provider')->nullable()->after('id');
            }

            if (! Schema::hasColumn($tableName, 'provider_id')) {
                $table->string('provider_id')->nullable()->after('provider');
            }

            if (! Schema::hasColumn($tableName, 'avatar')) {
                $table->string('avatar')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'phone')) {
                $table->string('phone')->nullable()->index();
            }

            $this->addProviderUniqueIndex($table, $tableName);
        });
    }

    public function down(): void
    {
        $tableName = $this->resolveTableName();

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if ($this->hasIndex($tableName, 'auth_kit_provider_unique')) {
                $table->dropUnique('auth_kit_provider_unique');
            }

            $columns = ['phone', 'avatar', 'provider_id', 'provider'];

            foreach ($columns as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function resolveTableName(): string
    {
        $modelClass = config('auth-kit.subjects.web.model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return 'users';
        }

        return (new $modelClass)->getTable();
    }

    private function addProviderUniqueIndex(Blueprint $table, string $tableName): void
    {
        if ($this->hasIndex($tableName, 'auth_kit_provider_unique')) {
            return;
        }

        $table->unique(['provider', 'provider_id'], 'auth_kit_provider_unique');
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $indexes = Schema::getIndexes($tableName);

        foreach ($indexes as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
