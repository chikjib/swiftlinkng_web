<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up() {
        Schema::create('service_controls', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary();
            $t->boolean('betting_enabled')->default(false);
            $t->boolean('esim_enabled')->default(false);
            $t->decimal('usd_ngn',14,4)->default(0);
            $t->text('esim_terms')->nullable();
            $t->text('betting_biller_ids')->nullable();
            $t->unsignedBigInteger('betting_subcategory_id')->nullable();
            $t->unsignedBigInteger('esim_subcategory_id')->nullable();
            $t->timestamps();
        });
        // Dedicated categories keep these services out of existing airtime/data provider crons.
        $ids = [];
        foreach (['Betting', 'Travel eSIM'] as $title) {
            $category = DB::table('category')->where('title', $title)->value('id');
            if (!$category) $category = DB::table('category')->insertGetId(['title'=>$title, 'status'=>1, 'created_at'=>now(), 'updated_at'=>now()]);
            $id = DB::table('subcategory')->where('category_id',$category)->where('title',$title)->value('id');
            if (!$id) $id = DB::table('subcategory')->insertGetId(['category_id'=>$category, 'title'=>$title, 'description'=>'SWIFTLINK_SERVICE', 'status'=>1, 'created_at'=>now(), 'updated_at'=>now()]);
            $ids[] = $id;
        }
        DB::table('service_controls')->insert(['id'=>1, 'esim_terms'=>config('esim_terms.draft'), 'betting_biller_ids'=>json_encode(config('betting.biller_ids', [])), 'betting_subcategory_id'=>$ids[0], 'esim_subcategory_id'=>$ids[1], 'created_at'=>now(), 'updated_at'=>now()]);
        Schema::create('service_control_audits', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('admin_id'); $t->text('before_values'); $t->text('after_values'); $t->timestamps();
        });
        Schema::create('esim_quotes', function (Blueprint $t) {
            $t->string('id',36)->primary(); $t->unsignedBigInteger('user_id')->index();
            $t->string('package_code'); $t->string('package_name'); $t->unsignedBigInteger('provider_price');
            $t->decimal('exchange_rate',14,4); $t->unsignedBigInteger('amount_kobo');
            $t->string('kind'); $t->unsignedBigInteger('profile_id')->nullable();
            $t->unsignedInteger('period_num')->nullable(); $t->text('package_snapshot');
            $t->timestamp('expires_at'); $t->timestamps();
        });
        Schema::create('esim_purchases', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->index(); $t->unsignedBigInteger('order_id')->unique();
            $t->string('reference',50)->unique(); $t->string('quote_id',36)->unique();
            $t->string('kind'); $t->unsignedBigInteger('profile_id')->nullable();
            $t->string('package_code'); $t->string('package_name'); $t->unsignedBigInteger('provider_price');
            $t->decimal('exchange_rate',14,4); $t->unsignedBigInteger('amount_kobo');
            $t->unsignedInteger('period_num')->nullable(); $t->text('package_snapshot');
            $t->string('provider_order')->nullable(); $t->string('status')->default('pending')->index();
            $t->string('error_code')->nullable(); $t->timestamp('last_attempt_at')->nullable(); $t->timestamps();
        });
        Schema::create('esim_profiles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->index(); $t->unsignedBigInteger('purchase_id')->unique();
            $t->string('esim_tran_no')->unique(); $t->string('iccid'); $t->text('payload'); $t->timestamp('synced_at')->nullable(); $t->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('esim_profiles'); Schema::dropIfExists('esim_purchases'); Schema::dropIfExists('esim_quotes');
        Schema::dropIfExists('service_control_audits'); Schema::dropIfExists('service_controls');
        // Preserve categories referenced by transaction history.
    }
};
