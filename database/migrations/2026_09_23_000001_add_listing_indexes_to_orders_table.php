<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'orders_created_at_index');
            $table->index(['status', 'created_at'], 'orders_status_created_at_index');
            $table->index(['user_id', 'created_at'], 'orders_user_created_at_index');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_created_at_index');
            $table->dropIndex('orders_status_created_at_index');
            $table->dropIndex('orders_user_created_at_index');
        });
    }
};
