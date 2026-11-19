<?php

namespace App\Models;

use App\Enums\SikiSection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $siki_intervention_id
 * @property SikiSection $section
 * @property string $text
 * @property int $sort
 */
class SikiItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'siki_intervention_id',
        'section',
        'text',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => SikiSection::class,
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SikiIntervention, $this>
     */
    public function intervention(): BelongsTo
    {
        return $this->belongsTo(SikiIntervention::class, 'siki_intervention_id');
    }
}
