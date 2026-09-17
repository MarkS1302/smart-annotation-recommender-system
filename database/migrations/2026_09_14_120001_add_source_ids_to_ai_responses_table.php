<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_responses', function (Blueprint $table): void {
            $table->foreignId('input_source_id')->nullable()->after('user_id')->constrained('annotation_sources')->nullOnDelete();
            $table->foreignId('knowledge_base_source_id')->nullable()->after('input_source_id')->constrained('annotation_sources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_responses', function (Blueprint $table): void {
            $table->dropForeign(['input_source_id']);
            $table->dropForeign(['knowledge_base_source_id']);
            $table->dropColumn(['input_source_id', 'knowledge_base_source_id']);
        });
    }
};
