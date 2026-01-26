<?php

namespace Database\Seeders;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Factory;
use App\Models\listing;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Auth\User;
use Database\Factories\ListingFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        // $user = User::factory()->create([
        //     'name' => 'Ditik Rimal',
        //     'email' => 'rml.ditik@gmail.com'
        // ]);
        // Listing::factory(6)->create([
        //     'user_id' => $user->id
        // ]);
        News::factory()->create([
            'user_id' => $user->id
        ]);
       
    }
}
