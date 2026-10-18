<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // utf8mb4_bin so "aB3" and "ab3" are different codes. SQLite (tests) is binary by default.
            $code = $table->string('code', 32);
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $code->collation('utf8mb4_bin');
            }
            $code->unique();
            $table->text('url');
            $table->string('title', 191)->nullable();
            $table->string('password')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->unsignedInteger('max_clicks')->nullable();
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('creator_ip_hash', 64)->nullable();
            $table->dateTime('last_clicked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('creator_ip_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
