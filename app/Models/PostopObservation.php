<?php

namespace App\Models;

use App\Enums\AirwayPatency;
use App\Enums\AnalgesicResponse;
use App\Enums\Consciousness;
use App\Enums\DressingCondition;
use App\Enums\GeneralCondition;
use App\Enums\PainCharacter;
use App\Enums\PatientResponse;
use App\Enums\SutureCondition;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\PostopObservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property Carbon $recorded_at
 * @property int $gcs_eye
 * @property int $gcs_verbal
 * @property int $gcs_motor
 * @property GeneralCondition $general_condition
 * @property PatientResponse $response
 * @property AirwayPatency $airway_patency
 * @property string|null $wound_location
 * @property DressingCondition|null $dressing
 * @property bool $bleeding_seepage
 * @property bool $swelling_hematoma
 * @property SutureCondition|null $suture_condition
 * @property int $pain_nrs
 * @property string|null $pain_location
 * @property PainCharacter|null $pain_character
 * @property string|null $pain_duration
 * @property AnalgesicResponse|null $analgesic_response
 * @property Consciousness|null $anesthesia_recovery
 * @property bool $nausea_vomiting
 * @property bool $shivering_hypothermia
 * @property list<string>|null $complications
 * @property string|null $notes
 * @property array<string, mixed>|null $clinical_details
 * @property int $created_by
 * @property User|null $creator
 */
class PostopObservation extends Model
{
    /** @use HasFactory<PostopObservationFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    /** @var list<string> */
    public const COMPLICATION_OPTIONS = [
        'perdarahan',
        'hematoma',
        'infeksi_luka',
        'gangguan_napas',
        'hipo_hipertensi',
        'mual_muntah',
        'alergi_obat',
    ];

    protected $fillable = [
        'case_id',
        'recorded_at',
        'gcs_eye',
        'gcs_verbal',
        'gcs_motor',
        'general_condition',
        'response',
        'airway_patency',
        'wound_location',
        'dressing',
        'bleeding_seepage',
        'swelling_hematoma',
        'suture_condition',
        'pain_nrs',
        'pain_location',
        'pain_character',
        'pain_duration',
        'analgesic_response',
        'anesthesia_recovery',
        'nausea_vomiting',
        'shivering_hypothermia',
        'complications',
        'notes',
        'clinical_details',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'gcs_eye' => 'integer',
            'gcs_verbal' => 'integer',
            'gcs_motor' => 'integer',
            'general_condition' => GeneralCondition::class,
            'response' => PatientResponse::class,
            'airway_patency' => AirwayPatency::class,
            'dressing' => DressingCondition::class,
            'bleeding_seepage' => 'boolean',
            'swelling_hematoma' => 'boolean',
            'suture_condition' => SutureCondition::class,
            'pain_nrs' => 'integer',
            'pain_character' => PainCharacter::class,
            'analgesic_response' => AnalgesicResponse::class,
            'anesthesia_recovery' => Consciousness::class,
            'nausea_vomiting' => 'boolean',
            'shivering_hypothermia' => 'boolean',
            'complications' => 'array',
            'clinical_details' => 'array',
        ];
    }

    public function resolveAuditCaseId(): ?int
    {
        return $this->case_id;
    }

    public function gcsTotal(): int
    {
        return $this->gcs_eye + $this->gcs_verbal + $this->gcs_motor;
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
