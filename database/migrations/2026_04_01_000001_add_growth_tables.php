<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables for: visit counter (page_views), 404 log (not_found_log), newsletter (subscribers),
 * reusable blocks and content templates (blocks). Every table is optional — the code checks Tables::has().
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('page_views')) {
            Schema::create('page_views', function (Blueprint $t) {
                $t->id();
                $t->date('day');
                $t->string('view_key', 120);              // "c:<content id>" | "cat:<id>" | "tag:<id>" | "p:<name>"
                $t->unsignedBigInteger('content_id')->nullable()->index();
                $t->string('label', 190)->nullable();     // human readable title/path of the counted thing
                $t->unsignedInteger('hits')->default(0);
                $t->unique(['day', 'view_key']);
                $t->index(['day', 'content_id']);
            });
        }

        if (! Schema::hasTable('not_found_log')) {
            Schema::create('not_found_log', function (Blueprint $t) {
                $t->id();
                $t->char('path_hash', 32)->unique();
                $t->string('path', 300);
                $t->string('referrer', 300)->nullable();
                $t->unsignedInteger('hits')->default(1);
                $t->timestamp('first_seen')->nullable();
                $t->timestamp('last_seen')->nullable()->index();
            });
        }

        if (! Schema::hasTable('subscribers')) {
            Schema::create('subscribers', function (Blueprint $t) {
                $t->id();
                $t->string('email', 190)->unique();
                $t->string('name', 100)->nullable();
                $t->string('locale', 10)->nullable();
                $t->string('token', 40)->unique();
                $t->string('ip', 45)->nullable();
                $t->timestamp('confirmed_at')->nullable();
                $t->timestamp('unsubscribed_at')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('blocks')) {
            Schema::create('blocks', function (Blueprint $t) {
                $t->id();
                $t->string('kind', 10)->default('block')->index();   // block (reusable, [block slug]) | template (starting content)
                $t->string('name', 120);
                $t->string('slug', 80)->unique();
                $t->string('applies_to', 10)->default('any');        // templates: any | post | page
                $t->longText('body')->nullable();                    // HTML in the default language
                $t->text('i18n')->nullable();                        // JSON {locale: html} for other languages
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['blocks', 'subscribers', 'not_found_log', 'page_views'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
