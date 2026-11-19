<?php

namespace App\Models;

use App\Enums\BreathingPattern;
use App\Enums\Consciousness;
use App\Enums\OxygenDevice;
use App\Enums\VitalSignPhase;
use App\Models\Concerns\LogsClinicalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property VitalSignPhase $phase
 * @property Carbon $recorded_at
 * @property int|null $bp_systolic
 * @property int|null $bp_diastolic
 * @property int|null $pulse
 * @property float|null $temperature
 * @property int|null $respiratory_rate
 * @property int|null $spo2
 * @property float|null $weight_kg
 * @property float|null $height_cm
 * @property Consciousness $consciousness
 * @property BreathingPattern|null $breathing_pattern
 * @property OxygenDevice|null $oxygen_device
 * @property int|null $oxygen_flow_lpm
 * @property string|null $notes
 * @property int $created_by
 */
class VitalSign extends Model
{
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'phase',
        'recorded_at',
        'bp_systolic',
        'bp_diastolic',
        'pulse',
        'temperature',
        'respiratory_rate',
        'spo2',
        'weight_kg',
        'height_cm',
        'consciousness',
        'breathing_pattern',
        'oxygen_device',
        'oxygen_flow_lpm',
        'notes',
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
            'bp_systolic' => 'integer',
            'bp_diastolic' => 'integer',
            'pulse' => 'integer',
            'temperature' => 'float',
            'respiratory_rate' => 'integer',
            'spo2' => 'integer',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'consciousness' => Consciousness::class,
            'breathing_pattern' => BreathingPattern::class,
            'oxygen_device' => OxygenDevice::class,
            'oxygen_flow_lpm' => 'integer',
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
