<?php

namespace Database\Factories;

use App\Enums\ExamType;
use App\Models\SupportingExam;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportingExam>
 */
class SupportingExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'exam_type' => ExamType::Hemoglobin,
            'result' => '13.2 g/dL',
            'exam_date' => now()->toDateString(),
            'created_by' => User::factory(),
        ];
    }
}
