<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 190)->unique();
            $t->string('password');
            $t->string('role', 20)->default('author');
            $t->boolean('active')->default(true);
            $t->text('totp_secret')->nullable();
            $t->boolean('totp_enabled')->default(false);
            $t->unsignedBigInteger('totp_last_step')->default(0);
            $t->text('recovery_codes')->nullable();
            $t->unsignedSmallInteger('failed_attempts')->default(0);
            $t->timestamp('locked_until')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->string('last_login_ip', 45)->nullable();
            $t->timestamp('password_changed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('languages', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();
            $t->string('name', 60);
            $t->string('native_name', 60)->nullable();
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('translations', function (Blueprint $t) {
            $t->id();
            $t->string('key', 150);
            $t->string('locale', 10);
            $t->text('value');
            $t->unique(['key', 'locale']);
            $t->index('locale');
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 100)->primary();
            $t->text('value')->nullable();
        });

        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('path', 190)->unique();
            $t->string('thumb_path', 190)->nullable();
            $t->string('original_name', 190);
            $t->string('mime', 60);
            $t->unsignedInteger('size');
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->string('alt', 190)->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('category_translations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->string('locale', 10);
            $t->string('name', 150);
            $t->string('slug', 150);
            $t->text('description')->nullable();
            $t->unique(['locale', 'slug']);
            $t->unique(['category_id', 'locale']);
        });

        Schema::create('contents', function (Blueprint $t) {
            $t->id();
            $t->string('type', 10)->index(); // page | post
            $t->string('status', 10)->default('draft'); // draft | published
            $t->timestamp('publish_at')->nullable()->index();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->boolean('is_home')->default(false);
            $t->boolean('noindex')->default(false);
            $t->unsignedInteger('sort')->default(0);
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('content_translations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('content_id')->constrained()->cascadeOnDelete();
            $t->string('locale', 10);
            $t->string('title', 190);
            $t->string('slug', 190);
            $t->text('excerpt')->nullable();
            $t->longText('body')->nullable();
            $t->string('meta_title', 190)->nullable();
            $t->string('meta_description', 300)->nullable();
            $t->timestamps();
            $t->unique(['locale', 'slug']);
            $t->unique(['content_id', 'locale']);
        });

        Schema::create('revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('content_translation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title', 190);
            $t->longText('body')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('menus', function (Blueprint $t) {
            $t->id();
            $t->string('location', 40)->unique();
            $t->string('name', 80);
        });

        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('parent_id')->nullable()->index();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->string('type', 10); // content | builtin | custom
            $t->foreignId('content_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('url', 500)->nullable(); // custom url or builtin key
            $t->text('labels')->nullable(); // json {locale: label}
            $t->boolean('new_tab')->default(false);
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 190);
            $t->string('subject', 190)->nullable();
            $t->text('body');
            $t->string('ip', 45)->nullable();
            $t->string('locale', 10)->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('redirects', function (Blueprint $t) {
            $t->id();
            $t->string('from_path', 190)->unique();
            $t->string('to_url', 500);
            $t->unsignedSmallInteger('code')->default(301);
            $t->unsignedInteger('hits')->default(0);
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 60)->index();
            $t->string('subject', 190)->nullable();
            $t->text('details')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 250)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'redirects', 'messages', 'menu_items', 'menus', 'revisions', 'content_translations',
            'contents', 'category_translations', 'categories', 'media', 'settings', 'translations', 'languages', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
