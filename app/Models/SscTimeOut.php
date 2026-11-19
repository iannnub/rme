<?php

namespace App\Models;

use App\Enums\SscStatus;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\SscTimeOutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property SscStatus $status
 * @property bool $intro_circulating
 * @property bool $intro_scrub
 * @property bool $intro_anesthesia_nurse
 * @property bool $intro_surgeon
 * @property bool $intro_anesthesiologist
 * @property bool $identity_procedure_site_confirmed
 * @property string|null $antibiotic_prophylaxis
 * @property string|null $antibiotic_name
 * @property Carbon|null $antibiotic_given_at
 * @property string|null $antibiotic_confirmed_by
 * @property string|null $surgeon_critical_steps
 * @property string|null $surgeon_critical_steps_text
 * @property int|null $surgeon_estimated_duration_min
 * @property int|null $surgeon_estimated_blood_loss_ml
 * @property string|null $anesthesia_special_condition
 * @property string|null $anesthesia_risk
 * @property string|null $anesthesia_note
 * @property bool $nurse_sterility_confirmed
 * @property bool $nurse_indicator_checked
 * @property string|null $equipment_problem
 * @property string|null $other_concern
 * @property string|null $nurse_note
 * @property string|null $imaging_needed
 * @property string|null $imaging_displayed
 * @property string|null $imaging_matches
 * @property Carbon|null $signed_at
 * @property int|null $filled_by
 * @property int $created_by
 * @property User|null $filler
 */
class SscTimeOut extends Model
{
    /** @use HasFactory<SscTimeOutFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'status',
        'intro_circulating',
        'intro_scrub',
        'intro_anesthesia_nurse',
        'intro_surgeon',
        'intro_anesthesiologist',
        'identity_procedure_site_confirmed',
        'antibiotic_prophylaxis',
        'antibiotic_name',
        'antibiotic_given_at',
        'antibiotic_confirmed_by',
        'surgeon_critical_steps',
        'surgeon_critical_steps_text',
        'surgeon_estimated_duration_min',
        'surgeon_estimated_blood_loss_ml',
        'anesthesia_special_condition',
        'anesthesia_risk',
        'anesthesia_note',
        'nurse_sterility_confirmed',
        'nurse_indicator_checked',
        'equipment_problem',
        'other_concern',
        'nurse_note',
        'imaging_needed',
        'imaging_displayed',
        'imaging_matches',
        'signed_at',
        'filled_by',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SscStatus::class,
            'intro_circulating' => 'boolean',
            'intro_scrub' => 'boolean',
            'intro_anesthesia_nurse' => 'boolean',
            'intro_surgeon' => 'boolean',
            'intro_anesthesiologist' => 'boolean',
            'identity_procedure_site_confirmed' => 'boolean',
            'antibiotic_given_at' => 'datetime',
            'surgeon_estimated_duration_min' => 'integer',
            'surgeon_estimated_blood_loss_ml' => 'integer',
            'nurse_sterility_confirmed' => 'boolean',
            'nurse_indicator_checked' => 'boolean',
            'signed_at' => 'datetime',
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
    public function filler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by');
    }
}
