<?php

namespace App\Filament\Vendor\Concerns;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Vendor pages forward uploads to the kasabazaar API instead of storing them
 * here, so their FileUpload fields are dehydrated(false). Two traps follow:
 *
 *  - the files are NOT in getState(), so read them from the component's raw
 *    form data (a pending upload is a TemporaryUploadedFile or Livewire's
 *    serialised "livewire-file:…" string — both are accepted);
 *  - getState() still moves Livewire's temporary file, so the contents must
 *    be read BEFORE calling getState().
 *
 * Hence pendingUploads() returns ready-to-send multipart parts.
 */
trait ReadsTemporaryUploads
{
    /**
     * @return array<int, array{name: string, contents: string, filename: string}>
     */
    protected function pendingUploads(string $field, string $partName): array
    {
        $raw = data_get($this->data, $field) ?? ($this->form->getRawState()[$field] ?? []);

        return collect(is_array($raw) ? $raw : [$raw])
            ->flatMap(function ($value) {
                if (is_string($value) && TemporaryUploadedFile::canUnserialize($value)) {
                    $value = TemporaryUploadedFile::unserializeFromLivewireRequest($value);
                }

                return is_array($value) ? $value : [$value];
            })
            ->filter(fn ($file) => $file instanceof TemporaryUploadedFile && $file->exists())
            ->map(fn (TemporaryUploadedFile $file) => [
                'name' => $partName,
                'contents' => $file->get(),
                'filename' => $file->getClientOriginalName(),
            ])
            ->values()
            ->all();
    }
}
