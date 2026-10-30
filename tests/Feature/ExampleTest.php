<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->boolean('status');
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value');
        });
        DB::table('settings')->insert([
            ['key' => 'ABOUT', 'value' => 'Swiftlink'],
            ['key' => 'SOCIAL', 'value' => '||'],
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
