<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_nursing_diagnosis_id
 * @property int $siki_item_id
 * @property bool $is_done
 * @property Carbon|null $done_at
 * @property int|null $done_by
 */
class CaseInterventionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_nursing_diagnosis_id',
        'siki_item_id',
        'is_done',
        'done_at',
        'done_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaseNursingDiagnosis, $this>
     */
    public function caseNursingDiagnosis(): BelongsTo
    {
        return $this->belongsTo(CaseNursingDiagnosis::class, 'case_nursing_diagnosis_id');
    }

    /**
     * @return BelongsTo<SikiItem, $this>
     */
    public function sikiItem(): BelongsTo
    {
        return $this->belongsTo(SikiItem::class, 'siki_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
