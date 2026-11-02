<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // All money is stored as integer minor units (e.g. cents). NULL price = "not configured yet".
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();
            $t->string('kind', 20)->index(); // website | system | mobile | creative | bundle
            $t->json('name');
            $t->json('description')->nullable();
            $t->string('icon', 40)->nullable();
            $t->unsignedBigInteger('base_price_minor')->nullable();
            $t->boolean('supports_pages')->default(false);
            $t->unsignedSmallInteger('min_pages')->default(1);
            $t->unsignedSmallInteger('max_pages')->default(1);
            $t->unsignedSmallInteger('included_pages')->default(1);
            $t->unsignedBigInteger('extra_page_price_minor')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['is_active', 'sort_order']);
        });

        Schema::create('service_options', function (Blueprint $t) {
            $t->id();
            $t->string('code', 60)->unique();
            $t->string('group', 20)->index(); // addon | platform | creative
            $t->string('exclusive_group', 40)->nullable(); // at most one option per exclusive group
            $t->json('name');
            $t->json('description')->nullable();
            $t->string('pricing_mode', 20)->default('fixed'); // fixed | per_page | percent_bp
            $t->unsignedBigInteger('amount')->nullable(); // minor units, or basis points for percent_bp
            $t->unsignedSmallInteger('max_quantity')->default(1);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['is_active', 'sort_order']);
        });

        Schema::create('project_type_service_option', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_type_id')->constrained()->cascadeOnDelete();
            $t->foreignId('service_option_id')->constrained()->cascadeOnDelete();
            $t->unique(['project_type_id', 'service_option_id'], 'pt_so_unique'); // explicit name: the default exceeds MySQL's 64-char limit
        });

        Schema::create('discount_rules', function (Blueprint $t) {
            $t->id();
            $t->string('code', 60)->unique();
            $t->json('name');
            $t->json('description')->nullable();
            $t->string('type', 20)->default('fixed'); // fixed | percent_bp
            $t->unsignedBigInteger('value')->default(0);
            $t->string('condition_type', 30)->default('none'); // none | whatsapp_phone | attachment | min_subtotal
            $t->unsignedBigInteger('condition_value')->nullable();
            $t->unsignedBigInteger('max_discount_minor')->nullable();
            $t->boolean('stackable')->default(false);
            $t->unsignedSmallInteger('priority')->default(0);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('is_active')->default(false); // off until the company decides its policy
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['discount_rules', 'project_type_service_option', 'service_options', 'project_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
