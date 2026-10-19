<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up()
    {
        Schema::create('internet_provider_settings', function (Blueprint $table) {
            $table->string('provider', 20)->primary();
            $table->boolean('enabled')->default(true);
            $table->decimal('multiplier', 8, 4)->nullable();
            $table->decimal('fixed_fee', 12, 2)->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('tested_at')->nullable();
            $table->string('test_status', 20)->nullable();
            $table->string('test_message')->nullable();
            $table->timestamps();
        });
        foreach (['fibrex', 'smile', 'spectranet', 'nitel'] as $provider) {
            DB::table('internet_provider_settings')->insert(['provider' => $provider, 'enabled' => true, 'fixed_fee' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::create('internet_packages', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('package_key', 191);
            $table->string('name');
            $table->unsignedBigInteger('source_subcategory_id')->nullable();
            $table->decimal('cost', 16, 2)->default(0);
            $table->text('snapshot');
            $table->boolean('available')->default(true);
            $table->boolean('enabled')->default(true);
            $table->decimal('price_override', 16, 2)->nullable();
            $table->timestamps();
            $table->unique(['provider', 'package_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('internet_packages');
        Schema::dropIfExists('internet_provider_settings');
    }
};
