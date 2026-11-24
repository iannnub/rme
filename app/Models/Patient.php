<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $rm_number
 * @property string $name
 * @property Carbon $birth_date
 * @property Gender $gender
 * @property string|null $identity_number
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $guardian_name
 * @property string|null $guardian_relation
 * @property string|null $guardian_phone
 * @property int|null $created_by
 */
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'rm_number',
        'name',
        'birth_date',
        'gender',
        'identity_number',
        'address',
        'phone',
        'guardian_name',
        'guardian_relation',
        'guardian_phone',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
        ];
    }

    /**
     * @return HasMany<SurgeryCase, $this>
     */
    public function surgeryCases(): HasMany
    {
        return $this->hasMany(SurgeryCase::class, 'patient_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ageYears(): int
    {
        return $this->birth_date->age;
    }

    public function ageLabel(): string
    {
        $years = $this->ageYears();
        if ($years >= 1) {
            return "{$years} tahun";
        }

        $months = (int) $this->birth_date->diffInMonths(now());
        if ($months >= 1) {
            return "{$months} bulan";
        }

        $days = max(0, (int) $this->birth_date->diffInDays(now()));

        return "{$days} hari";
    }
}
