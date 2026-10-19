<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Public author profile: URL name, short bio / resume and website. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'username')) {
                $t->string('username', 40)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $t->text('bio')->nullable();
            }
            if (! Schema::hasColumn('users', 'website')) {
                $t->string('website', 190)->nullable();
            }
        });

        // give existing users a public URL name
        foreach (\Illuminate\Support\Facades\DB::table('users')->whereNull('username')->orderBy('id')->get() as $u) {
            $name = \App\Support\Members::usernameFrom((string) $u->name, fn ($s) => \Illuminate\Support\Facades\DB::table('users')->where('username', $s)->exists());
            \Illuminate\Support\Facades\DB::table('users')->where('id', $u->id)->update(['username' => $name]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            foreach (['username', 'bio', 'website'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
