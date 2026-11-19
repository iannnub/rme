<?php

namespace App\Models;

use App\Enums\CountVerification;
use App\Enums\SkinIntegrity;
use App\Enums\SurgicalPosition;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\IntraopRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $case_id
 * @property SurgicalPosition|null $position
 * @property list<string>|null $pressure_protection
 * @property list<string>|null $skin_antiseptic
 * @property string|null $grounding_plate_site
 * @property SkinIntegrity|null $skin_condition_pre
 * @property SkinIntegrity|null $skin_condition_post
 * @property bool $draping_done
 * @property list<string>|null $warming
 * @property float|null $room_temperature_c
 * @property CountVerification|null $count_verification
 * @property list<string>|null $count_discrepancy_actions
 * @property bool $has_complication
 * @property string|null $complication_description
 * @property string|null $complication_handling
 * @property int $created_by
 */
class IntraopRecord extends Model
{
    /** @use HasFactory<IntraopRecordFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    /** @var list<string> */
    public const PRESSURE_PROTECTION_OPTIONS = [
        'ganjal_kepala_leher',
        'bantalan_tumit_siku',
        'safety_belt',
        'armboard',
    ];

    /** @var list<string> */
    public const SKIN_ANTISEPTIC_OPTIONS = [
        'povidone_iodine',
        'alkohol_70',
        'chlorhexidine',
    ];

    /** @var list<string> */
    public const WARMING_OPTIONS = [
        'blanket_warmer',
        'fluid_warmer',
    ];

    /** @var list<string> */
    public const COUNT_DISCREPANCY_ACTIONS = [
        'pencarian_ulang',
        'lapor_operator',
        'xray_lapangan',
    ];

    protected $fillable = [
        'case_id',
        'position',
        'pressure_protection',
        'skin_antiseptic',
        'grounding_plate_site',
        'skin_condition_pre',
        'skin_condition_post',
        'draping_done',
        'warming',
        'room_temperature_c',
        'count_verification',
        'count_discrepancy_actions',
        'has_complication',
        'complication_description',
        'complication_handling',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => SurgicalPosition::class,
            'pressure_protection' => 'array',
            'skin_antiseptic' => 'array',
            'skin_condition_pre' => SkinIntegrity::class,
            'skin_condition_post' => SkinIntegrity::class,
            'draping_done' => 'boolean',
            'warming' => 'array',
            'room_temperature_c' => 'float',
            'count_verification' => CountVerification::class,
            'count_discrepancy_actions' => 'array',
            'has_complication' => 'boolean',
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
