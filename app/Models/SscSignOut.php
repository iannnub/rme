<?php

namespace App\Models;

use App\Enums\SignOutStatus;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\SscSignOutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property SignOutStatus $status
 * @property bool|null $q1_procedure_confirmed
 * @property bool|null $q2_counts_correct
 * @property string|null $q3_specimen_labeled
 * @property bool|null $q4_equipment_problem
 * @property bool|null $q5_recovery_discussed
 * @property string|null $extra_6_procedure_matches
 * @property string|null $extra_7_changes_recorded
 * @property string|null $extra_8_discrepancy_followup
 * @property string|null $extra_9_specimen_matches
 * @property string|null $extra_10_equipment_issue_recorded
 * @property string|null $extra_11_complication_communicated
 * @property string|null $extra_12_postop_monitoring_discussed
 * @property string|null $extra_13_devices_instructions
 * @property string|null $recovery_notes
 * @property string|null $issues_and_actions
 * @property Carbon|null $signed_at
 * @property int|null $filled_by
 * @property int $created_by
 * @property User|null $filler
 */
class SscSignOut extends Model
{
    /** @use HasFactory<SscSignOutFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'status',
        'q1_procedure_confirmed',
        'q2_counts_correct',
        'q3_specimen_labeled',
        'q4_equipment_problem',
        'q5_recovery_discussed',
        'extra_6_procedure_matches',
        'extra_7_changes_recorded',
        'extra_8_discrepancy_followup',
        'extra_9_specimen_matches',
        'extra_10_equipment_issue_recorded',
        'extra_11_complication_communicated',
        'extra_12_postop_monitoring_discussed',
        'extra_13_devices_instructions',
        'recovery_notes',
        'issues_and_actions',
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
            'status' => SignOutStatus::class,
            'q1_procedure_confirmed' => 'boolean',
            'q2_counts_correct' => 'boolean',
            'q4_equipment_problem' => 'boolean',
            'q5_recovery_discussed' => 'boolean',
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
