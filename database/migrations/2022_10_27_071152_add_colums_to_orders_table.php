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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('phone')->nullable(true)->after('total');
            $table->string('iuc')->nullable(true)->after('phone');
            $table->string('meter')->nullable(true)->after('iuc');
            $table->decimal('bal', 16, 2)->nullable(true)->after('meter');
            $table->decimal('prev_bal', 16, 2)->nullable(true)->after('bal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('iuc');
            $table->dropColumn('meter');
            $table->dropColumn('bal');
            $table->dropColumn('prev_bal');
        });
    }
};
