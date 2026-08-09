<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DemoCustomerOrderSeeder extends EcommerceDemoSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = $this->adminUser();
            $catalog = $this->seedCatalog($admin);

            $this->seedMarketing($catalog);
            $this->seedCustomersAndOrders($catalog, $admin);
        });
    }

    protected function adminUser(): ?User
    {
        return User::query()->where('email', $this->adminEmail())->first();
    }
}
