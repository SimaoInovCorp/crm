<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete()->after('owner_id');
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete()->after('entity_id');
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete()->after('person_id');
            $table->boolean('notify_person')->default(false)->after('all_day');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entity_id');
            $table->dropConstrainedForeignId('person_id');
            $table->dropConstrainedForeignId('deal_id');
            $table->dropColumn('notify_person');
        });
    }
};
