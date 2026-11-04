<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (! Schema::hasTable('migration_flags')) {
            Schema::create('migration_flags', function (Blueprint $table) {
                $table->string('module', 64)->primary();
                $table->string('driver', 16)->default('flask');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_flags');
        Schema::dropIfExists('sessions');
    }
};
