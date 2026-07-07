<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $alex   = User::where('email', 'alex@acme.io')->first();
        $jordan = User::where('email', 'jordan@acme.io')->first();
        $sam    = User::where('email', 'sam@acme.io')->first();
        $casey  = User::where('email', 'casey@acme.io')->first();
        $riley  = User::where('email', 'riley@acme.io')->first();

        $pms3 = Issue::where('key', 'PMS-3')->first();
        $pms4 = Issue::where('key', 'PMS-4')->first();
        $pms5 = Issue::where('key', 'PMS-5')->first();
        $pms7 = Issue::where('key', 'PMS-7')->first();

        if ($pms3) {
            $c1 = Comment::firstOrCreate(
                ['issue_id' => $pms3->id, 'user_id' => $casey->id, 'body' => 'I can reproduce this consistently on Chrome 124. The token refresh fires but the redirect fires before the new token is stored.'],
                ['created_at' => '2026-06-11 10:30:00']
            );
            Comment::firstOrCreate(
                ['issue_id' => $pms3->id, 'user_id' => $jordan->id, 'parent_id' => $c1->id, 'body' => 'Thanks, I can see the race condition now. Will have a fix today.'],
                ['created_at' => '2026-06-11 14:00:00']
            );
            Comment::firstOrCreate(
                ['issue_id' => $pms3->id, 'user_id' => $alex->id, 'body' => '@jordan please also add a test that covers the expiry case.'],
                ['created_at' => '2026-06-12 09:00:00']
            );
        }

        if ($pms4) {
            Comment::firstOrCreate(
                ['issue_id' => $pms4->id, 'user_id' => $sam->id, 'body' => 'The burndown and velocity charts are the priority. The workload widget can follow in a separate ticket.'],
                ['created_at' => '2026-06-13 11:00:00']
            );
            Comment::firstOrCreate(
                ['issue_id' => $pms4->id, 'user_id' => $alex->id, 'body' => 'Agreed. Let\'s target those two for Sprint 2 and defer the rest.'],
                ['created_at' => '2026-06-13 13:30:00']
            );
        }

        if ($pms5) {
            Comment::firstOrCreate(
                ['issue_id' => $pms5->id, 'user_id' => $riley->id, 'body' => 'Using a sliding window counter backed by Redis. Limits: 1000/hour per API key, 100/min for unauthenticated.'],
                ['created_at' => '2026-07-02 15:00:00']
            );
        }

        if ($pms7) {
            Comment::firstOrCreate(
                ['issue_id' => $pms7->id, 'user_id' => $riley->id, 'body' => 'Ran EXPLAIN on the slow query — missing index on (project_id, status_id, sprint_id). Adding a compound index should drop it to <50ms.'],
                ['created_at' => '2026-06-21 10:00:00']
            );
            Comment::firstOrCreate(
                ['issue_id' => $pms7->id, 'user_id' => $jordan->id, 'body' => 'Also consider eager loading status/assignee in the repository to avoid N+1 on the list endpoint.'],
                ['created_at' => '2026-06-21 14:30:00']
            );
            Comment::firstOrCreate(
                ['issue_id' => $pms7->id, 'user_id' => $alex->id, 'body' => 'Good catch. Let\'s land both together.'],
                ['created_at' => '2026-06-22 09:00:00']
            );
            Comment::firstOrCreate(
                ['issue_id' => $pms7->id, 'user_id' => $casey->id, 'body' => 'I\'ll write a regression test against the slow path once the fix is in.'],
                ['created_at' => '2026-06-22 11:00:00']
            );
        }
    }
}
