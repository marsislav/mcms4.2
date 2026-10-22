<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Trash": pages, posts and categories are soft-deleted (deleted_at). */
return new class extends Migration {
    public function up(): void
    {
        foreach (['contents', 'categories'] as $table) {
            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->timestamp('deleted_at')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['contents', 'categories'] as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('deleted_at'));
            }
        }
    }
};
