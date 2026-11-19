<?php

namespace App\Models;

use App\Enums\CaseStatus;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\SurgeryCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property string $case_number
 * @property CaseStatus $status
 * @property Carbon $admitted_at
 * @property string $origin_ward
 * @property string|null $origin_class
 * @property Carbon $scheduled_at
 * @property int|null $medical_diagnosis_id
 * @property string|null $medical_diagnosis_other
 * @property int|null $surgery_type_id
 * @property string|null $surgery_type_other
 * @property string|null $surgery_site
 * @property string|null $operation_plan_actual
 * @property int|null $anesthesia_type_id
 * @property int|null $room_id
 * @property int|null $surgeon_id
 * @property int|null $anesthesiologist_id
 * @property int|null $circulating_nurse_id
 * @property int|null $scrub_nurse_id
 * @property int|null $anesthesia_nurse_id
 * @property Carbon|null $team_assigned_at
 * @property Carbon|null $ready_at
 * @property Carbon|null $entered_or_at
 * @property Carbon|null $anesthesia_start_at
 * @property Carbon|null $incision_at
 * @property Carbon|null $surgery_end_at
 * @property Carbon|null $left_or_at
 * @property Carbon|null $completed_at
 * @property array<string, bool>|null $stage_readiness_flags
 * @property int $created_by
 * @property PreopAssessment|null $preopAssessment
 * @property SscSignIn|null $sscSignIn
 * @property SscTimeOut|null $sscTimeOut
 * @property SscSignOut|null $sscSignOut
 * @property IntraopRecord|null $intraopRecord
 * @property PostopSummary|null $postopSummary
 * @property Handover|null $handover
 */
class SurgeryCase extends Model
{
    /** @use HasFactory<SurgeryCaseFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CaseStatus::PreOp->value,
    ];

    protected $fillable = [
        'patient_id',
        'case_number',
        'admitted_at',
        'origin_ward',
        'origin_class',
        'scheduled_at',
        'medical_diagnosis_id',
        'medical_diagnosis_other',
        'surgery_type_id',
        'surgery_type_other',
        'surgery_site',
        'operation_plan_actual',
        'anesthesia_type_id',
        'room_id',
        'surgeon_id',
        'anesthesiologist_id',
        'circulating_nurse_id',
        'scrub_nurse_id',
        'anesthesia_nurse_id',
        'team_assigned_at',
        'ready_at',
        'entered_or_at',
        'anesthesia_start_at',
        'incision_at',
        'surgery_end_at',
        'left_or_at',
        'completed_at',
        'stage_readiness_flags',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'admitted_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'team_assigned_at' => 'datetime',
            'ready_at' => 'datetime',
            'entered_or_at' => 'datetime',
            'anesthesia_start_at' => 'datetime',
            'incision_at' => 'datetime',
            'surgery_end_at' => 'datetime',
            'left_or_at' => 'datetime',
            'completed_at' => 'datetime',
            'stage_readiness_flags' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    protected function clinicalLogAttributes(): array
    {
        return [...$this->getFillable(), 'status'];
    }

    public function resolveAuditCaseId(): ?int
    {
        return $this->id;
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * @return BelongsTo<MedicalDiagnosis, $this>
     */
    public function medicalDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MedicalDiagnosis::class, 'medical_diagnosis_id');
    }

    /**
     * @return BelongsTo<SurgeryType, $this>
     */
    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class, 'surgery_type_id');
    }

    /**
     * @return BelongsTo<AnesthesiaType, $this>
     */
    public function anesthesiaType(): BelongsTo
    {
        return $this->belongsTo(AnesthesiaType::class, 'anesthesia_type_id');
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surgeon_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anesthesiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anesthesiologist_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function circulatingNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'circulating_nurse_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function scrubNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scrub_nurse_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anesthesiaNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anesthesia_nurse_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CaseSignoff, $this>
     */
    public function signoffs(): HasMany
    {
        return $this->hasMany(CaseSignoff::class, 'case_id');
    }

    /**
     * @return HasMany<AuditActivity, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditActivity::class, 'case_id');
    }

    /**
     * @return HasOne<PreopAssessment, $this>
     */
    public function preopAssessment(): HasOne
    {
        return $this->hasOne(PreopAssessment::class, 'case_id');
    }

    /**
     * @return HasMany<SupportingExam, $this>
     */
    public function supportingExams(): HasMany
    {
        return $this->hasMany(SupportingExam::class, 'case_id');
    }

    /**
     * @return HasMany<VitalSign, $this>
     */
    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class, 'case_id');
    }

    /**
     * @return HasMany<CaseNursingDiagnosis, $this>
     */
    public function caseNursingDiagnoses(): HasMany
    {
        return $this->hasMany(CaseNursingDiagnosis::class, 'case_id');
    }

    /**
     * @return HasMany<InstrumentCheck, $this>
     */
    public function instrumentChecks(): HasMany
    {
        return $this->hasMany(InstrumentCheck::class, 'case_id');
    }

    /**
     * @return HasOne<SscSignIn, $this>
     */
    public function sscSignIn(): HasOne
    {
        return $this->hasOne(SscSignIn::class, 'case_id');
    }

    /**
     * @return HasOne<SscTimeOut, $this>
     */
    public function sscTimeOut(): HasOne
    {
        return $this->hasOne(SscTimeOut::class, 'case_id');
    }

    /**
     * @return HasOne<SscSignOut, $this>
     */
    public function sscSignOut(): HasOne
    {
        return $this->hasOne(SscSignOut::class, 'case_id');
    }

    /**
     * @return HasOne<IntraopRecord, $this>
     */
    public function intraopRecord(): HasOne
    {
        return $this->hasOne(IntraopRecord::class, 'case_id');
    }

    /**
     * @return HasMany<Medication, $this>
     */
    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class, 'case_id');
    }

    /**
     * @return HasMany<FluidRecord, $this>
     */
    public function fluidRecords(): HasMany
    {
        return $this->hasMany(FluidRecord::class, 'case_id');
    }

    /**
     * @return HasMany<NursingAction, $this>
     */
    public function nursingActions(): HasMany
    {
        return $this->hasMany(NursingAction::class, 'case_id');
    }

    /**
     * @return HasMany<InstrumentCount, $this>
     */
    public function instrumentCounts(): HasMany
    {
        return $this->hasMany(InstrumentCount::class, 'case_id');
    }

    /**
     * @return HasMany<Specimen, $this>
     */
    public function specimens(): HasMany
    {
        return $this->hasMany(Specimen::class, 'case_id');
    }

    /**
     * @return HasMany<PostopObservation, $this>
     */
    public function postopObservations(): HasMany
    {
        return $this->hasMany(PostopObservation::class, 'case_id');
    }

    /**
     * @return HasOne<PostopSummary, $this>
     */
    public function postopSummary(): HasOne
    {
        return $this->hasOne(PostopSummary::class, 'case_id');
    }

    /**
     * @return HasOne<Handover, $this>
     */
    public function handover(): HasOne
    {
        return $this->hasOne(Handover::class, 'case_id');
    }

    public function hasRoomAndTeamAssigned(): bool
    {
        return $this->room_id !== null
            && $this->surgeon_id !== null
            && $this->anesthesiologist_id !== null
            && $this->circulating_nurse_id !== null
            && $this->scrub_nurse_id !== null
            && $this->anesthesia_nurse_id !== null;
    }

    public function diagnosisDisplay(): string
    {
        if ($this->medicalDiagnosis !== null) {
            return $this->medicalDiagnosis->name;
        }

        return $this->medical_diagnosis_other ?: '-';
    }

    public function surgeryTypeDisplay(): string
    {
        if ($this->surgeryType !== null) {
            return $this->surgeryType->name;
        }

        return $this->surgery_type_other ?: '-';
    }

    public function readinessFlag(string $key): bool
    {
        $flags = $this->stage_readiness_flags ?? [];

        return ($flags[$key] ?? false) === true;
    }
}
