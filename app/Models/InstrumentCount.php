<?php

namespace App\Models;

use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\InstrumentCountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $case_id
 * @property InstrumentCountItem $item
 * @property int $initial_count
 * @property int $additional_count
 * @property int|null $final_count
 * @property InstrumentCountStatus|null $status
 * @property int $created_by
 */
class InstrumentCount extends Model
{
    /** @use HasFactory<InstrumentCountFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'item',
        'initial_count',
        'additional_count',
        'final_count',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item' => InstrumentCountItem::class,
            'initial_count' => 'integer',
            'additional_count' => 'integer',
            'final_count' => 'integer',
            'status' => InstrumentCountStatus::class,
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

    public static function computeStatus(int $initial, int $additional, ?int $final): ?InstrumentCountStatus
    {
        if ($final === null) {
            return null;
        }

        return ($initial + $additional) === $final
            ? InstrumentCountStatus::Sesuai
            : InstrumentCountStatus::TidakSesuai;
    }
}
