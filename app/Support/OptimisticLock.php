<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OptimisticLock
{
    public const CONFLICT_MESSAGE = 'Data telah diperbarui oleh petugas lain. Muat ulang halaman sebelum menyimpan agar perubahan tidak saling menimpa.';

    public static function token(?Model $model): ?string
    {
        if ($model === null) {
            return null;
        }

        $updatedAt = $model->getAttribute('updated_at');

        if ($updatedAt instanceof Carbon) {
            return $updatedAt->copy()->utc()->toIso8601String();
        }

        if (is_string($updatedAt) && $updatedAt !== '') {
            return Carbon::parse($updatedAt)->utc()->toIso8601String();
        }

        return null;
    }

    /**
     * Memastikan rekaman tidak berubah sejak terakhir dimuat oleh klien.
     * Bersifat opsional bila klien (mis. test lama) tidak mengirimkan expected_updated_at.
     */
    public static function assertFresh(?Model $model, mixed $expectedUpdatedAt): void
    {
        if (! is_string($expectedUpdatedAt)) {
            return;
        }

        $expected = trim($expectedUpdatedAt);

        if ($expected === '') {
            if ($model !== null && self::token($model) !== null) {
                throw ValidationException::withMessages([
                    'concurrency' => [self::CONFLICT_MESSAGE],
                ]);
            }

            return;
        }

        $current = self::token($model);

        if ($current === null) {
            throw ValidationException::withMessages([
                'concurrency' => [self::CONFLICT_MESSAGE],
            ]);
        }

        try {
            $expectedNormalized = Carbon::parse($expected)->utc()->toIso8601String();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'concurrency' => [self::CONFLICT_MESSAGE],
            ]);
        }

        if ($current !== $expectedNormalized) {
            throw ValidationException::withMessages([
                'concurrency' => [self::CONFLICT_MESSAGE],
            ]);
        }
    }
}
