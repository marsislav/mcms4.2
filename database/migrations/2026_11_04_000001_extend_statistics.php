<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statistics v2: extra columns on `visits` (traffic source, UTM tags, bot name, city, time on page, scroll depth,
 * returning-visitor id) and two aggregate tables: `search_terms` (what visitors search for) and `click_log`
 * (tracked clicks). Safe to run more than once: every step checks what already exists.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $t) {
                $add = [
                    'vid' => fn () => $t->char('vid', 16)->nullable()->index(),            // hash of IP + browser (no date) for new/returning
                    'source' => fn () => $t->string('source', 10)->nullable()->index(),    // direct | search | social | ai | email | paid | referral
                    'search_engine' => fn () => $t->string('search_engine', 40)->nullable(), // human name of the referrer (Google, Facebook ...)
                    'utm_source' => fn () => $t->string('utm_source', 100)->nullable(),
                    'utm_medium' => fn () => $t->string('utm_medium', 100)->nullable(),
                    'utm_campaign' => fn () => $t->string('utm_campaign', 100)->nullable(),
                    'bot' => fn () => $t->string('bot', 40)->nullable()->index(),          // crawler name when is_bot
                    'city' => fn () => $t->string('city', 80)->nullable(),                 // from CDN header when present
                    'seconds' => fn () => $t->unsignedSmallInteger('seconds')->nullable(), // time on page
                    'scroll' => fn () => $t->unsignedTinyInteger('scroll')->nullable(),    // max scroll depth, percent
                ];
                foreach ($add as $col => $define) {
                    if (! Schema::hasColumn('visits', $col)) {
                        $define();
                    }
                }
            });
        }

        if (! Schema::hasTable('search_terms')) {
            Schema::create('search_terms', function (Blueprint $t) {
                $t->id();
                $t->date('day');
                $t->string('term', 100);
                $t->unsignedInteger('searches')->default(0);
                $t->unsignedInteger('zero')->default(0);     // how many of those searches found nothing
                $t->unique(['day', 'term']);
            });
        }

        if (! Schema::hasTable('click_log')) {
            Schema::create('click_log', function (Blueprint $t) {
                $t->id();
                $t->date('day');
                $t->string('label', 120);
                $t->string('kind', 10);                      // custom | mailto | tel | external | download
                $t->unsignedInteger('clicks')->default(0);
                $t->unique(['day', 'label', 'kind']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('click_log');
        Schema::dropIfExists('search_terms');
        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $t) {
                foreach (['vid', 'source', 'search_engine', 'utm_source', 'utm_medium', 'utm_campaign', 'bot', 'city', 'seconds', 'scroll'] as $col) {
                    if (Schema::hasColumn('visits', $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
