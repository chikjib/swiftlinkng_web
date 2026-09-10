<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Removes the temporary table from any environment where the earlier
        // OPay draft migration had already been run. OPay funding now uses
        // the existing orders table, like PalmPay and Monnify.
        Schema::dropIfExists('opay_wallet_transactions');
    }

    public function down()
    {
        // The removed table is intentionally not restored; orders are the
        // source of truth for wallet-funding transactions.
    }
};
