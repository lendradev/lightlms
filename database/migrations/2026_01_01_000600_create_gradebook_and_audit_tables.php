<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course-scoped roles, a real gradebook, and an audit trail.
 *
 * `users.role` stays as the site-wide role. Course membership gains its own
 * role so an instructor can teach one course without becoming an instructor
 * everywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_user', function (Blueprint $table): void {
            $table->string('role')->default('student')->after('user_id');
            $table->index('role');
        });

        Schema::create('grade_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('grade_categories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('hidden')->default(false);
            $table->timestamps();
            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('grade_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('grade_categories')->cascadeOnDelete();
            $table->string('source_type')->default('activity');
            $table->foreignId('source_id')->nullable();
            $table->string('name');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('max_points', 8, 2)->nullable();
            $table->decimal('pass_points', 8, 2)->nullable();
            $table->string('aggregation')->default('weighted_mean');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('hidden')->default(false);
            $table->boolean('locked')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('grade_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('grade_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('raw_points', 8, 2)->nullable();
            $table->decimal('final_points', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_overridden')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->string('source')->default('auto');
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['item_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('grade_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('value')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'name']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->nullable();
            $table->string('event', 60);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['event', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('actor_id');
        });

        Schema::create('notification_events', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('category')->default('system');
            $table->string('channel')->default('both');
            $table->boolean('default_enabled')->default(true);
            $table->boolean('is_digestable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_event_id')->constrained('notification_events')->cascadeOnDelete();
            $table->string('channel')->default('inapp');
            $table->boolean('enabled')->default(true);
            $table->string('digest')->default('off');
            $table->timestamps();
            $table->unique(['user_id', 'notification_event_id', 'channel'], 'notification_preferences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_events');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('grade_settings');
        Schema::dropIfExists('grade_grades');
        Schema::dropIfExists('grade_items');
        Schema::dropIfExists('grade_categories');

        Schema::table('course_user', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
