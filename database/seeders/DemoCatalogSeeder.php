<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DemoCatalogSeeder extends EcommerceDemoSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCatalog($this->adminUser());
        });
    }

    protected function adminUser(): ?User
    {
        return User::query()->where('email', $this->adminEmail())->first();
    }
}
