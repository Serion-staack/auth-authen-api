<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            'title'             => 'Admin',
            'description'       => 'Admin',
            'notes'             => 'Admin Role',
            'created_at'        => Carbon::now()->toDateTimeString(),
            'updated_at'        => Carbon::now()->toDateTimeString(),
        ]);

        DB::table('roles')->insert([
            'title'             => 'Subject',
            'description'       => 'Subject',
            'notes'             => 'Subject Role',
            'created_at'        => Carbon::now()->toDateTimeString(),
            'updated_at'        => Carbon::now()->toDateTimeString(),
        ]);

        DB::table('roles')->insert([
            'title'             => 'Staff',
            'description'       => 'Staff',
            'notes'             => 'Staff Role',
            'created_at'        => Carbon::now()->toDateTimeString(),
            'updated_at'        => Carbon::now()->toDateTimeString(),
        ]);

    }
}
