<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('epic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('status_id')->constrained('statuses')->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->string('key', 20)->comment('e.g. PMS-42');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->enum('type', ['task', 'story', 'bug', 'epic', 'subtask'])->default('task');
            $table->enum('priority', ['urgent', 'high', 'medium', 'low', 'none'])->default('medium');
            $table->unsignedSmallInteger('story_points')->nullable();
            $table->unsignedInteger('time_estimate')->nullable()->comment('Minutes');
            $table->unsignedInteger('time_spent')->nullable()->comment('Minutes');
            $table->date('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'key']);
            $table->index(['project_id', 'status_id']);
            $table->index(['project_id', 'sprint_id']);
            $table->index(['assignee_id']);
        });

        Schema::create('issue_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['issue_id', 'label_id']);
        });

        Schema::create('issue_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['issue_id', 'user_id']);
        });

        Schema::create('issue_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('linked_issue_id')->constrained('issues')->cascadeOnDelete();
            $table->enum('link_type', ['blocks', 'is_blocked_by', 'relates_to', 'duplicates', 'is_duplicated_by', 'clones', 'is_cloned_by'])->default('relates_to');
            $table->timestamps();

            $table->unique(['issue_id', 'linked_issue_id', 'link_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_links');
        Schema::dropIfExists('issue_watchers');
        Schema::dropIfExists('issue_labels');
        Schema::dropIfExists('issues');
    }
};
