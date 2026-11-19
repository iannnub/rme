<?php

namespace App\Models;

use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\InstrumentCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property int $instrument_id
 * @property bool $is_available
 * @property bool $is_sterile
 * @property bool $is_functional
 * @property bool $is_rusty
 * @property string|null $note
 * @property int $checked_by
 * @property Carbon $checked_at
 * @property Instrument|null $instrument
 * @property User|null $checker
 */
class InstrumentCheck extends Model
{
    /** @use HasFactory<InstrumentCheckFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    protected $fillable = [
        'case_id',
        'instrument_id',
        'is_available',
        'is_sterile',
        'is_functional',
        'is_rusty',
        'note',
        'checked_by',
        'checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_sterile' => 'boolean',
            'is_functional' => 'boolean',
            'is_rusty' => 'boolean',
            'checked_at' => 'datetime',
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
     * @return BelongsTo<Instrument, $this>
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'instrument_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function hasIssue(): bool
    {
        return ! $this->is_available
            || ! $this->is_sterile
            || ! $this->is_functional
            || $this->is_rusty;
    }

    public function isResolved(): bool
    {
        if (! $this->hasIssue()) {
            return true;
        }

        return $this->note !== null && trim($this->note) !== '';
    }
}
