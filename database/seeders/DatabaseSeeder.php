<?php /** @noinspection ALL */

/** @noinspection ALL */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        User::factory()
            ->times(10)
            ->create();
    }
}
