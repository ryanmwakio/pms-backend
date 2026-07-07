<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            WorkspaceSeeder::class,
            TeamSeeder::class,
            ProjectSeeder::class,
            LabelSeeder::class,
            EpicSeeder::class,
            SprintSeeder::class,
            IssueSeeder::class,
            CommentSeeder::class,
            ActivitySeeder::class,
        ]);
    }
}
