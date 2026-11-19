<?php

namespace App\Models;

use App\Enums\NursingPhase;
use App\Models\Concerns\LogsClinicalActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $case_id
 * @property int|null $nursing_diagnosis_id
 * @property NursingPhase $phase
 * @property string|null $custom_text
 * @property string|null $notes
 * @property int $created_by
 */
class CaseNursingDiagnosis extends Model
{
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'nursing_diagnosis_id',
        'phase',
        'custom_text',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phase' => NursingPhase::class,
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
     * @return BelongsTo<NursingDiagnosis, $this>
     */
    public function nursingDiagnosis(): BelongsTo
    {
        return $this->belongsTo(NursingDiagnosis::class, 'nursing_diagnosis_id');
    }

    /**
     * @return HasMany<CaseInterventionItem, $this>
     */
    public function interventionItems(): HasMany
    {
        return $this->hasMany(CaseInterventionItem::class, 'case_nursing_diagnosis_id');
    }

    /**
     * @return HasMany<CaseOutcomeScore, $this>
     */
    public function outcomeScores(): HasMany
    {
        return $this->hasMany(CaseOutcomeScore::class, 'case_nursing_diagnosis_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayName(): string
    {
        if ($this->nursingDiagnosis !== null) {
            $prefix = $this->nursingDiagnosis->code ? "[{$this->nursingDiagnosis->code}] " : '';

            return $prefix.$this->nursingDiagnosis->name;
        }

        return $this->custom_text ?: 'Diagnosis Keperawatan Lainnya';
    }
}
