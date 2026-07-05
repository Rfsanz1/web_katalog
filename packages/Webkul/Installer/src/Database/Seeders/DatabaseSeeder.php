<?php

namespace Webkul\Installer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\Attribute\DatabaseSeeder as AttributeSeeder;
use Webkul\Installer\Database\Seeders\Category\DatabaseSeeder as CategorySeeder;
use Webkul\Installer\Database\Seeders\CMS\DatabaseSeeder as CMSSeeder;
use Webkul\Installer\Database\Seeders\Core\DatabaseSeeder as CoreSeeder;
use Webkul\Installer\Database\Seeders\Customer\DatabaseSeeder as CustomerSeeder;
use Webkul\Installer\Database\Seeders\Inventory\DatabaseSeeder as InventorySeeder;
use Webkul\Installer\Database\Seeders\RMA\DatabaseSeeder as RMASeeder;
use Webkul\Installer\Database\Seeders\Shop\ThemeCustomizationTableSeeder as ShopSeeder;
use Webkul\Installer\Database\Seeders\SocialLogin\DatabaseSeeder as SocialLoginSeeder;
use Webkul\Installer\Database\Seeders\User\DatabaseSeeder as UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        $this->call(AttributeSeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(CategorySeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(InventorySeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(CoreSeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(CustomerSeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(CMSSeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(SocialLoginSeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(ShopSeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(UserSeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();

        $this->call(RMASeeder::class, false, ['parameters' => $parameters]);
        $this->fixSequences();
    }

    /**
     * On PostgreSQL, seeders that insert rows with explicit primary key
     * values do not advance the underlying sequence, which causes later
     * inserts relying on auto-increment defaults to collide. Realign every
     * table's sequence with the current max id after each seeder batch.
     */
    protected function fixSequences(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $tables = DB::select("SELECT table_name FROM information_schema.columns WHERE column_name = 'id' AND table_schema = 'public'");

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            $sequence = DB::selectOne("SELECT pg_get_serial_sequence(?, 'id') AS seq", [$tableName]);

            if (! $sequence || ! $sequence->seq) {
                continue;
            }

            DB::statement("SELECT setval('{$sequence->seq}', COALESCE((SELECT MAX(id) FROM \"{$tableName}\"), 0) + 1, false)");
        }
    }
}
