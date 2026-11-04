<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sites served from many rotating domains send their consume URL with each
     * sign-in, signed with the site secret; this flag allows that per site.
     */
    public function up(): void
    {
        Schema::table('auth_sites', function (Blueprint $table) {
            $table->boolean('signed_consume')->default(false)->after('consume_url');
        });
    }

    public function down(): void
    {
        Schema::table('auth_sites', function (Blueprint $table) {
            $table->dropColumn('signed_consume');
        });
    }
};
