<?php

namespace App\Models;

use Database\Factories\SurgeryTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $specialty
 * @property int|null $default_medical_diagnosis_id
 * @property string|null $default_procedure_name
 * @property bool $is_active
 * @property MedicalDiagnosis|null $defaultMedicalDiagnosis
 */
class SurgeryType extends Model
{
    /** @use HasFactory<SurgeryTypeFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'specialty',
        'default_medical_diagnosis_id',
        'default_procedure_name',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('master')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<MedicalDiagnosis, $this>
     */
    public function defaultMedicalDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MedicalDiagnosis::class, 'default_medical_diagnosis_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
