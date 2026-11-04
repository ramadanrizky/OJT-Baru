<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::statement(
            "UPDATE users
             SET data = JSON_REMOVE(data, '$.telegram_chat_id')
             WHERE JSON_VALID(data)
               AND JSON_CONTAINS_PATH(data, 'one', '$.telegram_chat_id')"
        );

        if (Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('telegram_chat_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('telegram_chat_id', 64)->nullable();
            });
        }
    }
};
