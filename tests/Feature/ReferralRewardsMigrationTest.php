<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferralRewardsMigrationTest extends TestCase
{
    private function migration(): object
    {
        return require database_path('migrations/2026_08_02_000002_create_referral_rewards_table.php');
    }

    private function legacyTable(bool $unique = true): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) use ($unique) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id')->index();
            $table->unsignedBigInteger('referred_user_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('type', 50);
            $table->string('wallet_type', 20);
            $table->string('reward_key', 191);
            if ($unique) {
                $table->unique('reward_key');
            }
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2)->default(0);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    private function reward(): array
    {
        return ['id' => 42, 'beneficiary_id' => 123, 'referred_user_id' => 456,
            'order_id' => 789, 'type' => 'referral', 'wallet_type' => 'commission',
            'reward_key' => 'existing-reward', 'amount' => '100.25',
            'balance_before' => '200.50', 'balance_after' => '300.75',
            'created_at' => '2026-07-26 12:00:00', 'updated_at' => '2026-07-26 12:00:00'];
    }

    public function test_existing_rewards_and_indexes_survive_repeated_migration(): void
    {
        $this->legacyTable();
        DB::table('referral_rewards')->insert($this->reward());
        $before = (array) DB::table('referral_rewards')->first();

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame([$before], DB::table('referral_rewards')->get()->map(fn ($row) => (array) $row)->all());
        foreach ([['beneficiary_id'], ['beneficiary_id', 'type'], ['order_id'], ['referred_user_id']] as $columns) {
            $this->assertTrue(Schema::hasIndex('referral_rewards', $columns));
        }
        $this->assertTrue(Schema::hasIndex('referral_rewards', ['reward_key'], 'unique'));

        DB::table('referral_rewards')->insert([
            'beneficiary_id' => 124, 'type' => 'referral', 'reward_key' => 'new-reward', 'amount' => 1,
        ]);
        $new = DB::table('referral_rewards')->where('reward_key', 'new-reward')->first();
        $this->assertSame('commission', $new->wallet_type);
        $this->assertEquals(0, $new->balance_before);
        $this->assertEquals(0, $new->balance_after);

        $this->expectException(QueryException::class);
        DB::table('referral_rewards')->insert(array_replace($this->reward(), ['id' => 99]));
    }

    public function test_new_install_can_create_table(): void
    {
        $this->migration()->up();
        DB::table('referral_rewards')->insert($this->reward());
        $this->assertSame(1, DB::table('referral_rewards')->count());
        $this->assertTrue(Schema::hasIndex('referral_rewards', ['reward_key'], 'unique'));
    }

    public function test_missing_unique_key_stops_before_changes(): void
    {
        $this->legacyTable(false);
        $before = Schema::getColumns('referral_rewards');
        try {
            $this->migration()->up();
            $this->fail('Unverified ledger was accepted.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('unique reward_key', $exception->getMessage());
        }
        $this->assertSame($before, Schema::getColumns('referral_rewards'));
        $this->assertFalse(Schema::hasIndex('referral_rewards', ['beneficiary_id', 'type']));
    }

    public function test_rollback_cannot_drop_legacy_rewards(): void
    {
        $this->legacyTable();
        DB::table('referral_rewards')->insert($this->reward());
        $this->migration()->up();
        try {
            $this->migration()->down();
            $this->fail('Ledger rollback must be refused.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('must not delete', $exception->getMessage());
        }
        $this->assertSame(1, DB::table('referral_rewards')->count());
    }
}
