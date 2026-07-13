<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->unsignedInteger('articles_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
