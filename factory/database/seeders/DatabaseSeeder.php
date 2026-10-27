<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (DB::table('customers')->exists() || DB::table('suppliers')->exists()) return;
        DB::table('customers')->insert([
            ['code'=>'CUS-0001','name'=>'Northstar Packaging','business_name'=>'Northstar Foods','phone'=>'0300 1234567','address'=>'Lahore','active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['code'=>'CUS-0002','name'=>'Crescent Traders','business_name'=>'Crescent Retail','phone'=>'0301 9876543','address'=>'Gujranwala','active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
        DB::table('suppliers')->insert([
            ['code'=>'SUP-0001','name'=>'Pak Paper Mills','business_name'=>'Pak Paper Mills','phone'=>'042 5550123','address'=>'Sheikhupura','active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
