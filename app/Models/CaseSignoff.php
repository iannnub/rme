<?php

namespace App\Models;

use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\CaseSignoffFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $case_id
 * @property SignoffSection $section
 * @property SignoffKind $kind
 * @property string $role_label
 * @property int $user_id
 * @property string|null $nip_snapshot
 * @property string|null $sip_snapshot
 * @property Carbon $signed_at
 * @property Carbon|null $revoked_at
 * @property string|null $revoke_reason
 */
class CaseSignoff extends Model
{
    /** @use HasFactory<CaseSignoffFactory> */
    use HasFactory, LogsClinicalActivity;

    protected $fillable = [
        'case_id',
        'section',
        'kind',
        'role_label',
        'user_id',
        'nip_snapshot',
        'sip_snapshot',
        'signed_at',
        'revoked_at',
        'revoke_reason',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Pengesahan tidak dapat dihapus; gunakan pencabutan (revoked_at).');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => SignoffSection::class,
            'kind' => SignoffKind::class,
            'signed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function resolveAuditCaseId(): ?int
    {
        return $this->case_id;
    }

    /**
     * @return BelongsTo<SurgeryCase, $this>
     */
    public function surgeryCase(): BelongsTo
    {
        return $this->belongsTo(SurgeryCase::class, 'case_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }
}
