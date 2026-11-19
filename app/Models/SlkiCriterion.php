<?php

namespace App\Models;

use App\Enums\SlkiExpectation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $nursing_diagnosis_id
 * @property string|null $outcome_code
 * @property string $outcome_name
 * @property SlkiExpectation $expectation
 * @property string $criterion_text
 * @property int $target_score
 * @property int $sort
 */
class SlkiCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'nursing_diagnosis_id',
        'outcome_code',
        'outcome_name',
        'expectation',
        'criterion_text',
        'target_score',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expectation' => SlkiExpectation::class,
            'target_score' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NursingDiagnosis, $this>
     */
    public function nursingDiagnosis(): BelongsTo
    {
        return $this->belongsTo(NursingDiagnosis::class, 'nursing_diagnosis_id');
    }
}
