<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->enum('type', ['text', 'voice', 'file', 'image', 'gif'])->default('text');
            $table->boolean('is_read')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};