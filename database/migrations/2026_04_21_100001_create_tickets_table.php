<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->string('public_token', 64)->unique();
            $table->enum('source', ['email', 'chat', 'form']);
            $table->string('name', 150);
            $table->string('email')->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->enum('category', ['support', 'billing', 'other'])->default('other');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('ai_suggested_reply')->nullable();
            $table->timestamp('notification_email_sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('source');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
