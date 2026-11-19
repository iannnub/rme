<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $nursing_diagnosis_id
 * @property string|null $code
 * @property string $name
 * @property int $sort
 */
class SikiIntervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'nursing_diagnosis_id',
        'code',
        'name',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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

    /**
     * @return HasMany<SikiItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SikiItem::class, 'siki_intervention_id')->orderBy('sort');
    }
}
