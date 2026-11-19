<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_nursing_diagnosis_id
 * @property int $slki_criterion_id
 * @property int $score
 * @property Carbon|null $scored_at
 */
class CaseOutcomeScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_nursing_diagnosis_id',
        'slki_criterion_id',
        'score',
        'scored_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'scored_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaseNursingDiagnosis, $this>
     */
    public function caseNursingDiagnosis(): BelongsTo
    {
        return $this->belongsTo(CaseNursingDiagnosis::class, 'case_nursing_diagnosis_id');
    }

    /**
     * @return BelongsTo<SlkiCriterion, $this>
     */
    public function slkiCriterion(): BelongsTo
    {
        return $this->belongsTo(SlkiCriterion::class, 'slki_criterion_id');
    }
}
