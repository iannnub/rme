<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Spatie\Activitylog\Models\Activity;

/**
 * @property int|null $case_id
 * @property string|null $reason
 * @property string|null $ip_address
 * @property-read SurgeryCase|null $surgeryCase
 */
class AuditActivity extends Activity
{
    protected $casts = [
        'properties' => 'collection',
        'case_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Log audit tidak dapat diubah atau dihapus.');
        });

        static::deleting(function (): void {
            throw new LogicException('Log audit tidak dapat diubah atau dihapus.');
        });
    }

    /**
     * @return BelongsTo<SurgeryCase, $this>
     */
    public function surgeryCase(): BelongsTo
    {
        return $this->belongsTo(SurgeryCase::class, 'case_id');
    }
}
