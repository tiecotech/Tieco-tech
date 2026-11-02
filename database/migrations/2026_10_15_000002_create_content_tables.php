<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Translatable columns are JSON: {"en": "...", "ar": "..."}
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('group', 40)->default('general')->index();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('title');
            $t->json('body')->nullable();
            $t->json('seo')->nullable();
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });

        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('icon', 40)->nullable();
            $t->string('image_path')->nullable();
            $t->json('name');
            $t->json('summary')->nullable();
            $t->json('description')->nullable();
            $t->json('problems')->nullable();
            $t->json('deliverables')->nullable();
            $t->json('features')->nullable();
            $t->json('steps')->nullable();
            $t->json('faqs')->nullable();
            $t->json('seo')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['is_visible', 'sort_order']);
        });

        Schema::create('project_categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('name');
            $t->boolean('is_visible')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->foreignId('project_category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $t->json('title');
            $t->json('summary')->nullable();
            $t->json('description')->nullable();
            $t->string('cover_path')->nullable();
            $t->string('url')->nullable();
            $t->json('tech')->nullable();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_visible')->default(true);
            $t->boolean('is_demo')->default(false); // sample content must be flagged
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['is_visible', 'is_featured', 'sort_order']);
        });

        Schema::create('project_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->json('alt')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('team_members', function (Blueprint $t) {
            $t->id();
            $t->json('name');
            $t->json('role');
            $t->string('photo_path')->nullable();
            $t->json('socials')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['is_visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        foreach (['team_members', 'project_images', 'projects', 'project_categories', 'services', 'pages', 'site_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
