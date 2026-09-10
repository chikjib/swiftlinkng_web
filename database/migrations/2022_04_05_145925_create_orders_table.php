<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ref');
            $table->foreignId('user_id');
            $table->foreignId('subcategory_id');
            $table->string('plan');
            $table->decimal('amount', 16, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->decimal("subtotal", 16, 2)->nullable(true);
            $table->decimal('total', 16, 2)->default(0);
            $table->string('description')->nullable(true);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
