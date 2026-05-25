<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_email_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending_review');
            $table->string('task_type')->nullable();
            $table->string('title');
            $table->text('summary');
            $table->string('priority')->default('medium');
            $table->string('suggested_team')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('missing_information')->nullable();
            $table->text('suggested_next_action')->nullable();
            $table->text('operator_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_drafts');
    }
};
