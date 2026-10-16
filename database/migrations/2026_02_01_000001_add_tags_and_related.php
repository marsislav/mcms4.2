<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Tags (multilingual) + manually chosen related posts. Safe to run on existing installs. */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $t) {
                $t->id();
            });
        }

        if (! Schema::hasTable('tag_translations')) {
            Schema::create('tag_translations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $t->string('locale', 10);
                $t->string('name', 80);
                $t->string('slug', 120);
                $t->unique(['locale', 'slug']);
                $t->unique(['tag_id', 'locale']);
            });
        }

        if (! Schema::hasTable('content_tag')) {
            Schema::create('content_tag', function (Blueprint $t) {
                $t->foreignId('content_id')->constrained()->cascadeOnDelete();
                $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $t->primary(['content_id', 'tag_id']);
            });
        }

        if (! Schema::hasTable('content_related')) {
            Schema::create('content_related', function (Blueprint $t) {
                $t->foreignId('content_id')->constrained()->cascadeOnDelete();
                $t->foreignId('related_id')->constrained('contents')->cascadeOnDelete();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->primary(['content_id', 'related_id']);
            });
        }

        // UI strings for existing installs (fresh installs get them from database/seeders/strings.php)
        $strings = [
            'bg' => ['site.tags' => 'Тагове', 'site.related' => 'Свързани публикации', 'site.tag' => 'Таг: :name'],
            'en' => ['site.tags' => 'Tags', 'site.related' => 'Related posts', 'site.tag' => 'Tag: :name'],
        ];
        $existing = DB::table('languages')->pluck('code')->all();
        foreach ($strings as $locale => $map) {
            if (! in_array($locale, $existing, true)) {
                continue;
            }
            foreach ($map as $key => $value) {
                DB::table('translations')->updateOrInsert(['key' => $key, 'locale' => $locale], ['value' => $value]);
            }
        }
    }

    public function down(): void
    {
        foreach (['content_related', 'content_tag', 'tag_translations', 'tags'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
