<?php

namespace Database\Factories;

use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Models\CaseSignoff;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseSignoff>
 */
class CaseSignoffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'section' => SignoffSection::PreOp,
            'kind' => SignoffKind::Signoff,
            'role_label' => 'perawat',
            'user_id' => User::factory(),
            'nip_snapshot' => '199001012015011001',
            'sip_snapshot' => null,
            'signed_at' => now(),
            'revoked_at' => null,
            'revoke_reason' => null,
        ];
    }

    public function revoked(string $reason = 'Data diubah setelah pengesahan'): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }
}
