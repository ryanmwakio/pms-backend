<?php

use App\Models\PmsNotification;

describe('Notifications', function () {

    it('lists notifications for the authenticated user', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        PmsNotification::factory()->count(5)->create(['user_id' => $user->id]);

        $res = $this->withToken($token)
            ->getJson('/api/notifications')
            ->assertOk();

        expect($res->json('data.notifications'))->toHaveCount(5);
        expect($res->json('data.unread_count'))->toBe(5);
    });

    it('filters to unread only', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        PmsNotification::factory()->count(3)->create(['user_id' => $user->id, 'is_read' => false]);
        PmsNotification::factory()->count(2)->create(['user_id' => $user->id, 'is_read' => true]);

        $res = $this->withToken($token)
            ->getJson('/api/notifications?unread_only=1')
            ->assertOk();

        expect($res->json('data.notifications'))->toHaveCount(3);
    });

    it('marks a notification as read', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $n = PmsNotification::factory()->create(['user_id' => $user->id, 'is_read' => false]);

        $this->withToken($token)
            ->postJson("/api/notifications/{$n->id}/read")
            ->assertOk();

        $this->assertDatabaseHas('pms_notifications', ['id' => $n->id, 'is_read' => true]);
    });

    it('marks all notifications as read', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        PmsNotification::factory()->count(4)->create(['user_id' => $user->id, 'is_read' => false]);

        $this->withToken($token)
            ->postJson('/api/notifications/read-all')
            ->assertOk();

        expect(PmsNotification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
    });

    it('deletes a notification', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $n = PmsNotification::factory()->create(['user_id' => $user->id]);

        $this->withToken($token)
            ->deleteJson("/api/notifications/{$n->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('pms_notifications', ['id' => $n->id]);
    });

    it('cannot access another user notification', function () {
        [$user, $workspace, $token]     = actingAsNewUser();
        [$other, $workspace2, $token2] = actingAsNewUser(['email' => 'other@example.com']);

        $n = PmsNotification::factory()->create(['user_id' => $other->id, 'is_read' => false]);

        $this->withToken($token)
            ->postJson("/api/notifications/{$n->id}/read")
            ->assertStatus(403);
    });
});
