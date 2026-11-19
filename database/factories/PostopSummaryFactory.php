<?php

namespace Database\Factories;

use App\Models\PostopSummary;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostopSummary>
 */
class PostopSummaryFactory extends Factory
{
    protected $model = PostopSummary::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'ready_to_transfer' => true,
            'pre_transfer_condition_notes' => 'Kondisi umum baik, kesadaran compos mentis (GCS 15), TTV stabil, luka bersih kering, layak dipindahkan.',
            'assessed_at' => now(),
            'assessed_by' => User::factory()->perawat(),
            'created_by' => User::factory()->perawat(),
        ];
    }
}
