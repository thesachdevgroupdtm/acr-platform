<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * FILEUPLOAD-RECOVERY (D-FU-3) — deletes the previous image file from the
 * public disk when a model's `image` path changes, so re-uploading a
 * different format (e.g. .png → .webp, slug stays the same) doesn't leave
 * an orphan duplicate (wagon-r.png + wagon-r.webp).
 *
 * Uses Laravel's per-trait boot convention (boot{TraitName}) so it composes
 * with any model that already defines booted()/boot(). Same-path overwrites
 * (old === new) are skipped — Storage already overwrote the file in place,
 * and deleting would remove the just-written image.
 */
trait CleansOldImage
{
    protected static function bootCleansOldImage(): void
    {
        static::saving(function ($model): void {
            if (! $model->isDirty('image')) {
                return;
            }

            $old = $model->getOriginal('image');
            $new = $model->image;

            // Delete the previous file only when the path actually changed
            // (covers format/extension change AND clearing the image).
            if (is_string($old) && $old !== '' && $old !== $new) {
                Storage::disk('public')->delete($old);
            }
        });
    }
}
