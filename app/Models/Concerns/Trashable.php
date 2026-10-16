<?php

namespace App\Models\Concerns;

use App\Support\Trash;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SoftDeletes that degrades gracefully: before the "deleted_at" migration has been run
 * (existing installs right after an upgrade) the scope is not applied and delete() is a hard delete.
 */
trait Trashable
{
    use SoftDeletes {
        SoftDeletes::bootSoftDeletes as protected bootSoftDeletesOriginal;
        SoftDeletes::performDeleteOnModel as protected performSoftDeleteOnModel;
    }

    public static function bootSoftDeletes(): void
    {
        if (Trash::ready()) {
            static::bootSoftDeletesOriginal();
        }
    }

    protected function performDeleteOnModel()
    {
        if (! Trash::ready()) {
            $this->forceDeleting = true;
        }

        return $this->performSoftDeleteOnModel();
    }
}
