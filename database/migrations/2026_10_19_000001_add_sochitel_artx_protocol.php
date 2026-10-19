<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up()
    {
        Schema::table('international_settings', function (Blueprint $t) {
            $t->string('sochitel_protocol', 10)->default('artx');
            $t->string('sochitel_username')->nullable();
            $t->text('sochitel_password')->nullable();
        });
        // Existing accounts and their pending transactions retain their REST protocol.
        DB::table('international_settings')->whereNotNull('sochitel_private_key')->update(['sochitel_protocol' => 'rest']);
    }
    public function down()
    {
        Schema::table('international_settings', fn(Blueprint $t) => $t->dropColumn(['sochitel_protocol', 'sochitel_username', 'sochitel_password']));
    }
};
