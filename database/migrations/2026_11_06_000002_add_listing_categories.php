<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Categories for classified ads: own small table + listings.category_id. */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('listing_categories')) {
            Schema::create('listing_categories', function (Blueprint $t) {
                $t->id();
                $t->string('name', 80);
                $t->string('slug', 100)->unique();
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });
        }
        if (Schema::hasTable('listings') && ! Schema::hasColumn('listings', 'category_id')) {
            Schema::table('listings', function (Blueprint $t) {
                $t->unsignedBigInteger('category_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('listings', 'category_id')) {
            Schema::table('listings', function (Blueprint $t) {
                $t->dropColumn('category_id');
            });
        }
        Schema::dropIfExists('listing_categories');
    }
};
