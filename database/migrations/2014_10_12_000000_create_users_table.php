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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('phone');
            $table->float('wallet')->default(0);
            $table->float('commission')->default(0);
            $table->integer('role')->default(0);
            $table->string('reserved_acct')->nullable(true);
            $table->string('bank_name')->nullable(true);
            $table->string('account_number')->nullable(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->text("deviceToken")->nullable(true);
            $table->text("userToken")->nullable(true);
            $table->unsignedBigInteger('referral_id')->default(0);
            $table->integer('status')->default(0);
            $table->integer('userlevel')->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
