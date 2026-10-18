<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->dateTime('clicked_at');
            $table->char('country', 2)->nullable();
            $table->string('referrer_host', 191)->nullable();
            $table->string('device', 16)->nullable();
            $table->string('browser', 32)->nullable();
            $table->string('os', 32)->nullable();
            $table->string('visitor_hash', 64)->nullable();

            $table->index(['link_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
