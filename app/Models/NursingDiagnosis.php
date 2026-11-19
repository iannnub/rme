<?php

namespace App\Models;

use App\Enums\NursingPhase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property NursingPhase $phase
 * @property bool $is_active
 */
class NursingDiagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phase',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phase' => NursingPhase::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SlkiCriterion, $this>
     */
    public function slkiCriteria(): HasMany
    {
        return $this->hasMany(SlkiCriterion::class, 'nursing_diagnosis_id')->orderBy('sort');
    }

    /**
     * @return HasMany<SikiIntervention, $this>
     */
    public function sikiInterventions(): HasMany
    {
        return $this->hasMany(SikiIntervention::class, 'nursing_diagnosis_id')->orderBy('sort');
    }

    /**
     * @return HasMany<CaseNursingDiagnosis, $this>
     */
    public function caseNursingDiagnoses(): HasMany
    {
        return $this->hasMany(CaseNursingDiagnosis::class, 'nursing_diagnosis_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePhase(Builder $query, NursingPhase|string $phase): Builder
    {
        $phaseValue = $phase instanceof NursingPhase ? $phase->value : $phase;

        return $query->where('phase', $phaseValue);
    }
}
