<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_requests', function (Blueprint $t) {
            // The deal: when it was sold and the final agreed price (may differ from the estimate and the quote).
            $t->date('sold_at')->nullable()->after('pricing_snapshot');
            $t->unsignedBigInteger('final_price_minor')->nullable()->after('sold_at');
            $t->foreignId('sold_by')->nullable()->after('final_price_minor')->constrained('users')->nullOnDelete();
            // website = submitted through the builder; manual = a deal entered by staff (sold outside the site).
            $t->string('source', 20)->default('website')->after('number');
            $t->index('sold_at');
        });

        Schema::create('request_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('amount_minor');
            $t->date('paid_at');
            $t->string('method', 20)->default('cash'); // cash | transfer | card | other
            $t->string('note', 500)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['project_request_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_payments');
        Schema::table('project_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('sold_by');
            $t->dropIndex(['sold_at']);
            $t->dropColumn(['sold_at', 'final_price_minor', 'source']);
        });
    }
};
