<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('international_settings', function (Blueprint $t) {
            $t->string('provider', 20)->default('vtugate');
            $t->string('sochitel_user_id')->nullable();
            $t->text('sochitel_private_key')->nullable();
            $t->string('sochitel_currency', 3)->default('NGN');
            $t->decimal('sochitel_ngn_rate', 16, 6)->default(1);
            $t->string('sochitel_verification_hash', 64)->nullable();
            $t->timestamp('sochitel_verified_at')->nullable();
            $t->text('sochitel_logos')->nullable();
        });
        Schema::table('international_quotes', function (Blueprint $t) {
            $t->string('provider', 20)->default('vtugate');
            $t->string('provider_account', 64)->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->text('provider_payload')->nullable();
        });
        Schema::table('international_purchases', function (Blueprint $t) {
            $t->string('provider', 20)->default('vtugate');
        });
    }

    public function down()
    {
        Schema::table('international_purchases', fn(Blueprint $t) => $t->dropColumn('provider'));
        Schema::table('international_quotes', fn(Blueprint $t) => $t->dropColumn(['provider', 'provider_account', 'product_id', 'provider_payload']));
        Schema::table('international_settings', fn(Blueprint $t) => $t->dropColumn(['provider', 'sochitel_user_id', 'sochitel_private_key', 'sochitel_currency', 'sochitel_ngn_rate', 'sochitel_verification_hash', 'sochitel_verified_at', 'sochitel_logos']));
    }
};
