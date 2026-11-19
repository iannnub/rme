<?php

namespace App\Models;

use App\Enums\SpecimenPreservative;
use App\Enums\SpecimenPurpose;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\SpecimenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $case_id
 * @property bool $has_specimen
 * @property string|null $specimen_type
 * @property string|null $origin_organ
 * @property SpecimenPurpose|null $purpose
 * @property SpecimenPreservative|null $preservative
 * @property int $created_by
 */
class Specimen extends Model
{
    /** @use HasFactory<SpecimenFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'has_specimen',
        'specimen_type',
        'origin_organ',
        'purpose',
        'preservative',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_specimen' => 'boolean',
            'purpose' => SpecimenPurpose::class,
            'preservative' => SpecimenPreservative::class,
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
}
