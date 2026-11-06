<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Classified ads ("Обяви"): submitted by registered users, moderated in the panel, expire after N days. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('listings')) {
            return;
        }

        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('title', 160);
            $t->string('slug', 190)->default('');
            $t->text('body');
            $t->decimal('price', 12, 2)->nullable();          // null = negotiable
            $t->string('currency', 3)->default('EUR');
            $t->string('city', 80)->nullable();
            $t->string('contact_name', 80);
            $t->string('contact_phone', 40)->nullable();
            $t->string('contact_email', 120)->nullable();
            $t->text('media_ids')->nullable();                // JSON array of media.id (photos)
            $t->string('status', 12)->default('pending')->index();   // pending | published | rejected
            $t->unsignedSmallInteger('days')->default(30);    // chosen validity
            $t->timestamp('published_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
