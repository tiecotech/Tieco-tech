<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('subject');
            $t->text('message');
            $t->string('preferred_contact', 20)->nullable();
            $t->string('locale', 5)->default('en');
            $t->string('status', 20)->default('new'); // new | read | replied | archived
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 80);
            $t->nullableMorphs('subject');
            $t->json('properties')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
            $t->index(['action', 'created_at']);
        });

        // Standard Laravel database notifications table.
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'activity_logs', 'contact_messages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
