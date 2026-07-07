<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('avatar_color', 20)->default('#4264f5')->after('avatar_url');
            $table->string('avatar_initials', 5)->nullable()->after('avatar_color');
            $table->string('role')->nullable()->after('avatar_initials')->comment('Job role e.g. Engineer');
            $table->string('timezone')->default('UTC')->after('role');
            $table->enum('theme', ['light', 'dark', 'system'])->default('system')->after('timezone');
            $table->json('preferences')->nullable()->after('theme');
            $table->foreignId('active_workspace_id')->nullable()->after('preferences')
                ->constrained('workspaces')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_workspace_id']);
            $table->dropColumn([
                'avatar_url', 'avatar_color', 'avatar_initials',
                'role', 'timezone', 'theme', 'preferences', 'active_workspace_id',
            ]);
        });
    }
};
