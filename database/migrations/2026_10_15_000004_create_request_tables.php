<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_statuses', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();
            $t->json('name');
            $t->string('color', 20)->default('slate');
            $t->boolean('is_default')->default(false);
            $t->boolean('is_final')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('project_requests', function (Blueprint $t) {
            $t->id();
            $t->string('number', 32)->unique();
            $t->string('locale', 5)->default('en');
            $t->foreignId('project_type_id')->nullable()->constrained()->nullOnDelete();
            $t->json('project_type_snapshot'); // name/key at submission time
            $t->foreignId('request_status_id')->constrained()->restrictOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $t->string('customer_name');
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('country', 80)->nullable();
            $t->string('timezone', 60)->nullable();
            $t->string('preferred_contact', 20)->nullable();
            $t->boolean('whatsapp_opt_in')->default(false);

            $t->text('description')->nullable();
            $t->text('goal')->nullable();
            $t->text('audience')->nullable();
            $t->date('expected_date')->nullable();
            $t->unsignedBigInteger('budget_minor')->nullable();
            $t->unsignedSmallInteger('pages')->nullable();

            // Pricing frozen at submission. Later price edits never touch these.
            $t->char('currency', 3);
            $t->unsignedBigInteger('subtotal_minor')->default(0);
            $t->unsignedBigInteger('discount_minor')->default(0);
            $t->unsignedBigInteger('total_minor')->default(0);
            $t->boolean('has_unpriced_items')->default(false);
            $t->json('pricing_snapshot');

            $t->timestamps();
            $t->softDeletes();
            $t->index(['request_status_id', 'created_at']);
            $t->index('project_type_id');
            $t->index('phone');
            $t->index('email');
        });

        Schema::create('project_request_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('service_option_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 20); // base | pages | option | discount
            $t->string('code', 60);
            $t->json('name'); // snapshot
            $t->unsignedInteger('quantity')->default(1);
            $t->unsignedBigInteger('unit_price_minor')->nullable();
            $t->bigInteger('line_total_minor')->default(0); // negative for discounts
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['project_request_id', 'kind']);
        });

        Schema::create('project_request_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $t->string('disk', 20);
            $t->string('path'); // random generated name, never the user's filename
            $t->string('original_name');
            $t->string('mime', 100);
            $t->unsignedBigInteger('size');
            $t->char('sha256', 64);
            $t->timestamps();
        });

        Schema::create('project_request_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 20)->default('note'); // note | contact | status
            $t->string('channel', 20)->nullable(); // phone | whatsapp | email | meeting
            $t->text('body');
            $t->timestamps();
            $t->index(['project_request_id', 'created_at']);
        });

        Schema::create('quotes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('version')->default(1);
            $t->string('number', 40)->unique();
            $t->string('status', 20)->default('draft'); // draft | sent | accepted | rejected | superseded
            $t->char('currency', 3);
            $t->unsignedBigInteger('subtotal_minor')->default(0);
            $t->unsignedBigInteger('discount_minor')->default(0);
            $t->unsignedBigInteger('total_minor')->default(0);
            $t->unsignedSmallInteger('eta_days')->nullable();
            $t->text('terms')->nullable();
            $t->text('notes')->nullable();
            $t->date('valid_until')->nullable();
            $t->timestamp('issued_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['project_request_id', 'version']);
        });

        Schema::create('quote_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $t->string('description');
            $t->unsignedInteger('quantity')->default(1);
            $t->unsignedBigInteger('unit_price_minor')->default(0);
            $t->unsignedBigInteger('discount_minor')->default(0);
            $t->unsignedBigInteger('total_minor')->default(0);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['quote_items', 'quotes', 'project_request_notes', 'project_request_attachments', 'project_request_items', 'project_requests', 'request_statuses'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
