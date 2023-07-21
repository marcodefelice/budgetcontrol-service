<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\BudgetTracker\Models\Transfer;

class TransferSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Transfer::factory(1)->create([
            'transfer_id' => 2,
            'account_id' => 1,
            'amount' => -200,
            'user_id' => 1,
            'uuid' => '64b54d02cdcfd_test',
        ]);


        Transfer::factory(1)->create([
            'transfer_id' => 1,
            'account_id' => 2,
            'amount' => 200,
            'user_id' => 1,
            'uuid' => '64b54d02cdcft_test',
        ]);

    }
}
