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
        Schema::create('subcategory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id');
            $table->string('title', 50);
            $table->text('description')->nullable(true);
            $table->string('subcat_image')->nullable(true);
            $table->text('pins')->nullable(true);
            $table->string('telegram')->nullable(true);
            $table->text('products')->nullable(true);
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
        Schema::dropIfExists('subcategory');
    }
};
