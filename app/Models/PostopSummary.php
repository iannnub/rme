<?php

namespace App\Models;

use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\PostopSummaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property bool $ready_to_transfer
 * @property string|null $pre_transfer_condition_notes
 * @property Carbon|null $assessed_at
 * @property int|null $assessed_by
 * @property int $created_by
 * @property User|null $assessor
 */
class PostopSummary extends Model
{
    /** @use HasFactory<PostopSummaryFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'ready_to_transfer',
        'pre_transfer_condition_notes',
        'assessed_at',
        'assessed_by',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ready_to_transfer' => 'boolean',
            'assessed_at' => 'datetime',
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
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
