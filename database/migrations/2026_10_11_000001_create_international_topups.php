<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up()
    {
        Schema::create('international_settings', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary();
            $t->boolean('enabled')->default(false);
            $t->decimal('multiplier', 8, 4)->default(1.03);
            $t->text('api_key')->nullable();
            $t->text('calling_codes')->nullable();
            $t->unsignedBigInteger('subcategory_id')->nullable();
            $t->timestamps();
        });
        $category = DB::table('category')->where('title', 'International')->value('id');
        if (!$category) {
            $category = DB::table('category')->insertGetId(['title' => 'International', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        $sub = DB::table('subcategory')->where('category_id', $category)->where('title', 'International')->value('id');
        if (!$sub) {
            $sub = DB::table('subcategory')->insertGetId(['category_id' => $category, 'title' => 'International', 'description' => 'SWIFTLINK_SERVICE', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('international_settings')->insert(['id' => 1, 'subcategory_id' => $sub, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('international_quotes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('country_code', 2);
            $t->string('recipient_number', 15);
            $t->string('type', 10);
            $t->unsignedInteger('operator_id');
            $t->string('operator_name');
            $t->decimal('local_amount', 14, 2);
            $t->decimal('provider_price', 14, 2);
            $t->decimal('multiplier', 8, 4);
            $t->unsignedBigInteger('amount_kobo');
            $t->timestamp('expires_at');
            $t->timestamps();
        });
        Schema::create('international_purchases', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->uuid('quote_id')->unique();
            $t->unsignedBigInteger('order_id')->unique();
            $t->string('reference', 50)->unique();
            $t->unsignedBigInteger('transaction_id')->nullable();
            $t->unsignedBigInteger('amount_kobo');
            $t->string('status')->default('pending');
            $t->text('details')->nullable();
            $t->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('international_purchases');
        Schema::dropIfExists('international_quotes');
        Schema::dropIfExists('international_settings');
    }
};
