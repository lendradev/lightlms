<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->softDeletes();
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('locale', 8)->default('en')->after('last_login_at');
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('language', 8)->default('en');
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('price')->default(0);
            $table->text('learning_outcomes')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('self_enroll')->default(true);
            $table->boolean('allow_discussion')->default(true);
            $table->string('status')->default('draft');
            $table->softDeletes();
            $table->index('status');
            $table->index('code');
        });

        Schema::create('course_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unique(['course_id', 'tag_id']);
        });

        Schema::table('course_user', function (Blueprint $table): void {
            $table->string('status')->default('active');
            $table->timestamp('last_accessed_at')->nullable();
            $table->decimal('grade', 6, 2)->nullable();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_tag');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('tags');

        Schema::table('course_user', function (Blueprint $table): void {
            $table->dropColumn(['status', 'last_accessed_at', 'grade']);
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn([
                'category_id', 'user_id', 'code', 'language', 'currency', 'price',
                'learning_outcomes', 'requirements', 'capacity', 'self_enroll',
                'allow_discussion', 'status', 'deleted_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['deleted_at', 'last_login_at', 'locale']);
        });
    }
};
