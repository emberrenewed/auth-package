<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_kit_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 32);
            $table->string('destination');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['channel', 'destination'], 'auth_kit_otps_channel_destination_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_kit_otps');
    }
};
