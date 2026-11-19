<?php

namespace App\Models;

use App\Enums\HandoverDestination;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\HandoverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property HandoverDestination $destination
 * @property string|null $destination_detail
 * @property bool $documents_complete
 * @property int $handed_by_id
 * @property string $received_by_name
 * @property int|null $received_by_id
 * @property Carbon $handed_over_at
 * @property string|null $ongoing_issues
 * @property string|null $follow_up_instructions
 * @property array<string, string>|null $summary_snapshot
 * @property int $created_by
 * @property User|null $handedBy
 */
class Handover extends Model
{
    /** @use HasFactory<HandoverFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'destination',
        'destination_detail',
        'documents_complete',
        'handed_by_id',
        'received_by_name',
        'received_by_id',
        'handed_over_at',
        'ongoing_issues',
        'follow_up_instructions',
        'summary_snapshot',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'destination' => HandoverDestination::class,
            'documents_complete' => 'boolean',
            'handed_over_at' => 'datetime',
            'summary_snapshot' => 'array',
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
    public function handedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_by_id');
    }
}
