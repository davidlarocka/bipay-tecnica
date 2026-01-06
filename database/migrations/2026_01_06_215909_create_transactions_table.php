<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('to_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
