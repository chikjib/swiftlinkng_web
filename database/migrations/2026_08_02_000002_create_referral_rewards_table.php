<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('referred_user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('type', 50);
            $table->string('wallet_type', 30)->default('commission');
            $table->string('reward_key')->unique();
            $table->decimal('amount', 16, 2);
            $table->decimal('balance_before', 16, 2)->default(0);
            $table->decimal('balance_after', 16, 2)->default(0);
            $table->timestamps();

            $table->index(['beneficiary_id', 'type']);
            $table->index('referred_user_id');
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('referral_rewards');
    }
};
