<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DemoStorefrontContentSeeder extends EcommerceDemoSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $catalog = $this->seedCatalog($this->adminUser());

            $this->seedMarketing($catalog);
            $this->seedContent($catalog);
        });
    }

    protected function adminUser(): ?User
    {
        return User::query()->where('email', $this->adminEmail())->first();
    }
}
