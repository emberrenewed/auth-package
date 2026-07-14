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

        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'phone')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->string('phone')->nullable()->index();
        });
    }

    public function down(): void
    {
        $tableName = $this->resolveTableName();

        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'phone')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }

    private function resolveTableName(): string
    {
        $modelClass = config('auth-kit.subjects.web.model')
            ?? config('auth-kit.subjects.api.model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return 'users';
        }

        return (new $modelClass)->getTable();
    }
};