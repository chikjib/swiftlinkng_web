<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('betting_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('biller_id', 64);
            $table->string('biller_name');
            $table->string('recharge_account', 15);
            $table->string('account_name');
            $table->timestamps();
            $table->unique(['user_id', 'biller_id', 'recharge_account'], 'betting_account_owner');
        });
        Schema::create('betting_fundings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('reference', 32)->unique();
            $table->string('biller_id', 64);
            $table->string('biller_name');
            $table->string('item_id', 64);
            $table->string('recharge_account', 15);
            $table->string('account_name');
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('betting_fundings');
        Schema::dropIfExists('betting_accounts');
    }
};
