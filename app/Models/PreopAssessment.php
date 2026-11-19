<?php

namespace App\Models;

use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\ReadinessStatus;
use App\Models\Concerns\LogsClinicalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property string|null $chief_complaint
 * @property string|null $current_illness_history
 * @property string|null $past_illness_history
 * @property string|null $surgery_history
 * @property AllergyStatus $allergy_status
 * @property string|null $allergy_detail
 * @property string|null $medication_history
 * @property string|null $anesthesia_history
 * @property bool $fasting_status
 * @property Carbon|null $fasting_since
 * @property string|null $skin_condition
 * @property AnxietyLevel $anxiety_level
 * @property string|null $surgery_knowledge
 * @property string|null $family_support
 * @property string|null $mental_readiness
 * @property array<string, mixed>|null $preparation
 * @property string|null $implementation_actions
 * @property string|null $implementation_education
 * @property string|null $implementation_monitoring
 * @property string|null $implementation_response
 * @property string|null $evaluation_condition
 * @property string|null $evaluation_completeness
 * @property ReadinessStatus $readiness_status
 * @property string|null $evaluation_notes
 * @property int $created_by
 */
class PreopAssessment extends Model
{
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'chief_complaint',
        'current_illness_history',
        'past_illness_history',
        'surgery_history',
        'allergy_status',
        'allergy_detail',
        'medication_history',
        'anesthesia_history',
        'fasting_status',
        'fasting_since',
        'skin_condition',
        'anxiety_level',
        'surgery_knowledge',
        'family_support',
        'mental_readiness',
        'preparation',
        'implementation_actions',
        'implementation_education',
        'implementation_monitoring',
        'implementation_response',
        'evaluation_condition',
        'evaluation_completeness',
        'readiness_status',
        'evaluation_notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allergy_status' => AllergyStatus::class,
            'fasting_status' => 'boolean',
            'fasting_since' => 'datetime',
            'anxiety_level' => AnxietyLevel::class,
            'preparation' => 'array',
            'readiness_status' => ReadinessStatus::class,
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

    public function isPreparationComplete(): bool
    {
        $prep = $this->preparation ?? [];
        $required = [
            'identity_verified',
            'informed_consent_surgery',
            'anesthesia_consent',
            'fasting_verified',
            'allergy_assessed',
            'lab_available',
            'iv_access',
            'operation_area_prepared',
            'jewelry_removed',
        ];

        foreach ($required as $key) {
            if (($prep[$key] ?? false) !== true) {
                return false;
            }
        }

        if (array_key_exists('site_marked', $prep) && $prep['site_marked'] !== null) {
            if ($prep['site_marked'] !== true) {
                return false;
            }
        }

        return true;
    }
}
