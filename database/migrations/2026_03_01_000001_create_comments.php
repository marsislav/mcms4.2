<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Comments (Settings > Дискусии): table + per-content override "comments_open" (NULL = use the default). */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
                $t->unsignedBigInteger('parent_id')->nullable()->index();
                $t->string('author_name', 100);
                $t->string('author_email', 190)->nullable();
                $t->string('author_url', 200)->nullable();
                $t->string('author_ip', 45)->nullable();
                $t->string('user_agent', 255)->nullable();
                $t->text('body');
                $t->string('status', 12)->default('pending')->index(); // pending | approved | spam | trash
                $t->unsignedBigInteger('user_id')->nullable(); // set when an admin/editor wrote it
                $t->timestamps();
                $t->index(['content_id', 'status', 'created_at']);
            });
        }

        if (! Schema::hasColumn('contents', 'comments_open')) {
            Schema::table('contents', function (Blueprint $t) {
                $t->boolean('comments_open')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        if (Schema::hasColumn('contents', 'comments_open')) {
            Schema::table('contents', fn (Blueprint $t) => $t->dropColumn('comments_open'));
        }
    }
};
