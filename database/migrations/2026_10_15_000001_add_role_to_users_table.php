<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('viewer')->after('email'); // least privilege by default
            $t->boolean('is_active')->default(true)->after('role');
            $t->string('locale', 5)->default('en')->after('is_active');
            $t->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['role']);
            $t->dropColumn(['role', 'is_active', 'locale']);
        });
    }
};
