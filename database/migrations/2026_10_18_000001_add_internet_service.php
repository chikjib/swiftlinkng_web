<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration {
    public function up() {
        $category = DB::table('category')->where('title', 'Internet')->value('id');
        if (!$category) $category = DB::table('category')->insertGetId(['title'=>'Internet','status'=>1,'created_at'=>now(),'updated_at'=>now()]);
        foreach (['Smile'=>'SMILE','Spectranet'=>'SPECTRANET','Nitel'=>'NITEL'] as $title=>$key) {
            if (!DB::table('subcategory')->where('description', 'INTERNET_'.$key)->exists()) DB::table('subcategory')->insert(['title'=>$title,'category_id'=>$category,'description'=>'INTERNET_'.$key,'products'=>'[]','status'=>1,'created_at'=>now(),'updated_at'=>now()]);
        }
        Schema::create('internet_purchases', function(Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('order_id');
            $t->string('request_key', 100); $t->string('provider', 20); $t->string('reference', 64)->unique();
            $t->text('payload'); $t->text('response')->nullable(); $t->string('status', 20)->default('pending')->index();
            $t->timestamps(); $t->unique(['user_id','request_key']);
        });
    }
    // Keep service and order history on rollback.
    public function down() { Schema::dropIfExists('internet_purchases'); }
};
