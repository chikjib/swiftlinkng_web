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
        Schema::create('products', function (Blueprint $table) {
            //product name, description, add tags, quantity
            $table->id();
            $table->foreignId('subcategory_id');
            $table->foreignId('category_id');
            $table->string('item')->nullable(true);
            $table->float('ussdcode', 12, 2)->nullable(true);
            $table->float('amount', 12, 2)->nullable(true);
            $table->string('channel', 12, 1)->default('telegram'); //telegram, endpoint
            $table->integer('userlevel')->default(0);
            $table->boolean('status')->default(true);
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
        Schema::dropIfExists('products');
    }
};
