<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Custom CSS class(es) and ID for every menu item. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $t) {
            if (! Schema::hasColumn('menu_items', 'css_class')) {
                $t->string('css_class', 200)->nullable();
            }
            if (! Schema::hasColumn('menu_items', 'css_id')) {
                $t->string('css_id', 80)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $t) {
            foreach (['css_class', 'css_id'] as $c) {
                if (Schema::hasColumn('menu_items', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
