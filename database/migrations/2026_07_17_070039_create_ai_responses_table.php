<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('ai_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('request_id', 26)->unique();
            $table->string('entity');
            $table->string('record_id');
            $table->string('status')->index();
            $table->longText('answer')->nullable();
            $table->json('ai_response')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('ai_responses');
    }
};
