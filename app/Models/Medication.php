<?php

namespace App\Models;

use App\Enums\VitalSignPhase;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\MedicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property VitalSignPhase $phase
 * @property string $name
 * @property string $dose
 * @property string|null $route
 * @property Carbon $given_at
 * @property string|null $note
 * @property int $created_by
 * @property User|null $creator
 */
class Medication extends Model
{
    /** @use HasFactory<MedicationFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'phase',
        'name',
        'dose',
        'route',
        'given_at',
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
            'given_at' => 'datetime',
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
