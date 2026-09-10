<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('opay_wallet_number', 32)->nullable()->unique();
            $table->string('opay_wallet_ref_id', 15)->nullable()->unique();
            $table->string('opay_wallet_name', 150)->nullable();
            $table->string('opay_wallet_account_type', 20)->nullable();
            $table->string('opay_wallet_status', 20)->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['opay_wallet_number']);
            $table->dropUnique(['opay_wallet_ref_id']);
            $table->dropColumn([
                'opay_wallet_number',
                'opay_wallet_ref_id',
                'opay_wallet_name',
                'opay_wallet_account_type',
                'opay_wallet_status',
            ]);
        });
    }
};
