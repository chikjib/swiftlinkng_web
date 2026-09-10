<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whats_app_transaction_admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            $table->uuid('bot_order_id');
            $table->string('old_status', 30);
            $table->string('new_status', 30);
            $table->text('note');
            $table->timestamps();
            $table->index('bot_order_id');
            $table->index('admin_user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whats_app_transaction_admin_logs');
    }
};
