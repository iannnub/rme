<?php

namespace App\Models;

use App\Enums\FluidColor;
use App\Enums\FluidDirection;
use App\Enums\FluidKind;
use App\Enums\FluidOdor;
use App\Enums\VitalSignPhase;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\FluidRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property VitalSignPhase $phase
 * @property Carbon|null $recorded_at
 * @property FluidDirection $direction
 * @property FluidKind $kind
 * @property int $volume_ml
 * @property FluidColor|null $color
 * @property FluidOdor|null $odor
 * @property string|null $note
 * @property int $created_by
 * @property User|null $creator
 */
class FluidRecord extends Model
{
    /** @use HasFactory<FluidRecordFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'phase',
        'recorded_at',
        'direction',
        'kind',
        'volume_ml',
        'color',
        'odor',
        'note',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phase' => VitalSignPhase::class,
            'recorded_at' => 'datetime',
            'direction' => FluidDirection::class,
            'kind' => FluidKind::class,
            'volume_ml' => 'integer',
            'color' => FluidColor::class,
            'odor' => FluidOdor::class,
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
