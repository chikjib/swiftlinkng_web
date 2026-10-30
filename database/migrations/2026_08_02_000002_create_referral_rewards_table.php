<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // The July production migration already created this financial ledger.
        if (Schema::hasTable('referral_rewards')) {
            $this->upgradeExistingTable();

            return;
        }

        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('referred_user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('type', 50);
            $table->string('wallet_type', 30)->default('commission');
            $table->string('reward_key')->unique();
            $table->decimal('amount', 16, 2);
            $table->decimal('balance_before', 16, 2)->default(0);
            $table->decimal('balance_after', 16, 2)->default(0);
            $table->timestamps();

            $table->index(['beneficiary_id', 'type']);
            $table->index('referred_user_id');
            $table->index('order_id');
        });
    }

    public function down()
    {
        throw new LogicException('Referral rewards are a financial ledger. Use a reviewed forward migration; automatic rollback must not delete existing rewards.');
    }

    private function upgradeExistingTable(): void
    {
        $columns = collect(Schema::getColumns('referral_rewards'))->keyBy('name');
        $required = ['id', 'beneficiary_id', 'referred_user_id', 'order_id', 'type',
            'wallet_type', 'reward_key', 'amount', 'balance_before', 'balance_after',
            'created_at', 'updated_at'];

        foreach ($required as $column) {
            if (! $columns->has($column)) {
                throw new LogicException("Existing referral_rewards table is missing {$column}; manual schema review required.");
            }
        }

        if (! Schema::hasIndex('referral_rewards', ['reward_key'], 'unique')) {
            throw new LogicException('Existing referral_rewards table needs a unique reward_key; manual schema review required.');
        }

        // Check every definition before altering anything. Never narrow an existing column.
        $lengths = [];
        foreach (['wallet_type' => 30, 'reward_key' => 255] as $name => $minimum) {
            $type = $columns[$name]['type'];
            if (! preg_match('/^varchar(?:\((\d+)\))?$/i', $type, $match)) {
                throw new LogicException("Unexpected referral_rewards.{$name} type: {$type}; manual schema review required.");
            }
            $lengths[$name] = max($minimum, (int) ($match[1] ?? $minimum));
        }

        $precisions = [];
        foreach (['amount', 'balance_before', 'balance_after'] as $name) {
            $type = $columns[$name]['type'];
            // SQLite stores decimal declarations as numeric without precision metadata.
            if (Schema::getConnection()->getDriverName() === 'sqlite' && $type === 'numeric') {
                $precisions[$name] = 16;

                continue;
            }
            if (! preg_match('/^(?:decimal|numeric)\((\d+),\s*2\)$/i', $type, $match)) {
                throw new LogicException("Unexpected referral_rewards.{$name} type: {$type}; manual schema review required.");
            }
            $precisions[$name] = max(16, (int) $match[1]);
        }

        Schema::table('referral_rewards', function (Blueprint $table) use ($lengths, $precisions) {
            $table->string('wallet_type', $lengths['wallet_type'])->default('commission')->change();
            $table->string('reward_key', $lengths['reward_key'])->change();
            $table->decimal('amount', $precisions['amount'], 2)->change();
            $table->decimal('balance_before', $precisions['balance_before'], 2)->default(0)->change();
            $table->decimal('balance_after', $precisions['balance_after'], 2)->default(0)->change();
        });

        if (! Schema::hasIndex('referral_rewards', ['beneficiary_id', 'type'])) {
            Schema::table('referral_rewards', function (Blueprint $table) {
                $table->index(['beneficiary_id', 'type']);
            });
        }
    }
};
