<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_type', 20)->default('individual')->after('title');
            $table->json('links')->nullable()->after('submission_url');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->json('links')->nullable()->after('external_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['assignment_type', 'links']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('links');
        });
    }
};
