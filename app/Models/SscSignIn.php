<?php

namespace App\Models;

use App\Enums\AllergyStatus;
use App\Enums\BloodReadiness;
use App\Enums\IvAccessStatus;
use App\Enums\RiskPresence;
use App\Enums\SiteMarkingStatus;
use App\Enums\SscConsentStatus;
use App\Enums\SscMatchStatus;
use App\Enums\SscReadiness;
use App\Enums\SscStatus;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\SscSignInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property SscStatus $status
 * @property SscMatchStatus|null $identity_confirmed
 * @property SscMatchStatus|null $procedure_confirmed
 * @property SscMatchStatus|null $site_confirmed
 * @property SscConsentStatus|null $consent_surgery
 * @property SscConsentStatus|null $consent_anesthesia
 * @property SiteMarkingStatus|null $site_marking
 * @property array<string, string|null>|null $readiness
 * @property bool|null $pulse_ox_attached
 * @property bool|null $pulse_ox_functional
 * @property int|null $spo2
 * @property int|null $pulse
 * @property string|null $pulse_ox_note
 * @property AllergyStatus|null $allergy_status
 * @property string|null $allergy_type
 * @property string|null $allergy_agent
 * @property string|null $allergy_reaction
 * @property string|null $allergy_followup
 * @property RiskPresence|null $difficult_airway_risk
 * @property RiskPresence|null $aspiration_risk
 * @property string|null $anesthesiologist_assessment
 * @property string|null $anticipation_plan
 * @property SscReadiness|null $airway_equipment_ready
 * @property RiskPresence|null $bleeding_risk
 * @property int|null $estimated_blood_loss_ml
 * @property IvAccessStatus|null $iv_access
 * @property SscReadiness|null $fluids_ready
 * @property BloodReadiness|null $blood_ready
 * @property string|null $bleeding_note
 * @property string|null $notes_followup
 * @property Carbon|null $signed_at
 * @property int|null $filled_by
 * @property int $created_by
 * @property User|null $filler
 * @property User|null $creator
 */
class SscSignIn extends Model
{
    /** @use HasFactory<SscSignInFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    /**
     * 7 item kesiapan alat dan obat anestesi (bagian C).
     *
     * @var list<string>
     */
    public const READINESS_KEYS = [
        'anesthesia_machine',
        'oxygen_source',
        'anesthesia_drugs',
        'airway_aids',
        'suction',
        'monitoring_device',
        'emergency_drugs',
    ];

    protected $fillable = [
        'case_id',
        'status',
        'identity_confirmed',
        'procedure_confirmed',
        'site_confirmed',
        'consent_surgery',
        'consent_anesthesia',
        'site_marking',
        'readiness',
        'pulse_ox_attached',
        'pulse_ox_functional',
        'spo2',
        'pulse',
        'pulse_ox_note',
        'allergy_status',
        'allergy_type',
        'allergy_agent',
        'allergy_reaction',
        'allergy_followup',
        'difficult_airway_risk',
        'aspiration_risk',
        'anesthesiologist_assessment',
        'anticipation_plan',
        'airway_equipment_ready',
        'bleeding_risk',
        'estimated_blood_loss_ml',
        'iv_access',
        'fluids_ready',
        'blood_ready',
        'bleeding_note',
        'notes_followup',
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
            'identity_confirmed' => SscMatchStatus::class,
            'procedure_confirmed' => SscMatchStatus::class,
            'site_confirmed' => SscMatchStatus::class,
            'consent_surgery' => SscConsentStatus::class,
            'consent_anesthesia' => SscConsentStatus::class,
            'site_marking' => SiteMarkingStatus::class,
            'readiness' => 'array',
            'pulse_ox_attached' => 'boolean',
            'pulse_ox_functional' => 'boolean',
            'spo2' => 'integer',
            'pulse' => 'integer',
            'allergy_status' => AllergyStatus::class,
            'difficult_airway_risk' => RiskPresence::class,
            'aspiration_risk' => RiskPresence::class,
            'airway_equipment_ready' => SscReadiness::class,
            'bleeding_risk' => RiskPresence::class,
            'estimated_blood_loss_ml' => 'integer',
            'iv_access' => IvAccessStatus::class,
            'fluids_ready' => SscReadiness::class,
            'blood_ready' => BloodReadiness::class,
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Cek apakah ada item pemeriksaan Sign In yang bernilai bermasalah.
     *
     * @param  array<string, mixed>  $data
     */
    public static function hasProblematicValues(array $data): bool
    {
        if (($data['identity_confirmed'] ?? null) === SscMatchStatus::TidakSesuai->value) {
            return true;
        }
        if (($data['procedure_confirmed'] ?? null) === SscMatchStatus::TidakSesuai->value) {
            return true;
        }
        if (($data['site_confirmed'] ?? null) === SscMatchStatus::TidakSesuai->value) {
            return true;
        }
        if (($data['consent_surgery'] ?? null) === SscConsentStatus::BelumAda->value) {
            return true;
        }
        if (($data['consent_anesthesia'] ?? null) === SscConsentStatus::BelumAda->value) {
            return true;
        }
        if (($data['site_marking'] ?? null) === SiteMarkingStatus::TidakAda->value) {
            return true;
        }

        $readiness = is_array($data['readiness'] ?? null) ? $data['readiness'] : [];
        foreach (self::READINESS_KEYS as $key) {
            if (($readiness[$key] ?? null) === SscReadiness::TidakSiap->value) {
                return true;
            }
        }

        if (($data['pulse_ox_attached'] ?? null) === false || ($data['pulse_ox_functional'] ?? null) === false) {
            return true;
        }

        if (($data['airway_equipment_ready'] ?? null) === SscReadiness::TidakSiap->value) {
            return true;
        }
        if (($data['iv_access'] ?? null) === IvAccessStatus::TidakMemadai->value) {
            return true;
        }
        if (($data['fluids_ready'] ?? null) === SscReadiness::TidakSiap->value) {
            return true;
        }
        if (($data['blood_ready'] ?? null) === BloodReadiness::TidakSiap->value) {
            return true;
        }

        return false;
    }

    public function hasProblematicItems(): bool
    {
        return self::hasProblematicValues([
            'identity_confirmed' => $this->identity_confirmed?->value,
            'procedure_confirmed' => $this->procedure_confirmed?->value,
            'site_confirmed' => $this->site_confirmed?->value,
            'consent_surgery' => $this->consent_surgery?->value,
            'consent_anesthesia' => $this->consent_anesthesia?->value,
            'site_marking' => $this->site_marking?->value,
            'readiness' => $this->readiness ?? [],
            'pulse_ox_attached' => $this->pulse_ox_attached,
            'pulse_ox_functional' => $this->pulse_ox_functional,
            'airway_equipment_ready' => $this->airway_equipment_ready?->value,
            'iv_access' => $this->iv_access?->value,
            'fluids_ready' => $this->fluids_ready?->value,
            'blood_ready' => $this->blood_ready?->value,
        ]);
    }
}
