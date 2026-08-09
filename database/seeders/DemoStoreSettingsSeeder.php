<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

class DemoStoreSettingsSeeder extends EcommerceDemoSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSettings();
        });
    }
}
