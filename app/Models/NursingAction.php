<?php

namespace App\Models;

use App\Enums\NursingPhase;
use App\Models\Concerns\LogsClinicalActivity;
use Database\Factories\NursingActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $case_id
 * @property NursingPhase $phase
 * @property Carbon $performed_at
 * @property string $performed_by_role
 * @property string $action
 * @property string|null $evaluation
 * @property int $created_by
 * @property User|null $creator
 */
class NursingAction extends Model
{
    /** @use HasFactory<NursingActionFactory> */
    use HasFactory, LogsClinicalActivity, SoftDeletes;

    /**
     * 7 template tindakan keperawatan intra-operatif sesuai form PDF RME OKA.
     *
     * @var list<array{role: string, action: string, evaluation: string}>
     */
    public const INTRAOP_TEMPLATES = [
        [
            'role' => 'sirkuler',
            'action' => 'Memverifikasi identitas pasien, kelengkapan dokumen consent, dan kelengkapan persiapan pra-bedah.',
            'evaluation' => 'Data pasien sesuai, dokumen lengkap.',
        ],
        [
            'role' => 'sirkuler',
            'action' => 'Membantu perpindahan posisi pasien ke meja operasi dan mengatur posisi pembedahan secara aman.',
            'evaluation' => 'Posisi aman, fiksasi terpasang baik.',
        ],
        [
            'role' => 'instrumen',
            'action' => 'Menyiapkan set instrumen steril, bahan medis habis pakai (BMHP), dan melakukan perhitungan awal alat/kasa.',
            'evaluation' => 'Set instrumen steril & lengkap.',
        ],
        [
            'role' => 'instrumen',
            'action' => 'Melakukan desinfeksi lapangan operasi dan membantu prosedur draping steril.',
            'evaluation' => 'Area operasi steril ter-draping.',
        ],
        [
            'role' => 'sirkuler',
            'action' => 'Memantau prinsip aseptik-steril selama operasi berlangsung dan mengelola lalu lintas kamar operasi.',
            'evaluation' => 'Prinsip steril terjaga ketat.',
        ],
        [
            'role' => 'instrumen',
            'action' => 'Melakukan penghitungan ulang instrumen, kasa, dan jarum sebelum penutupan rongga/insisi operasi.',
            'evaluation' => 'Hitungan awal dan akhir sesuai.',
        ],
        [
            'role' => 'instrumen',
            'action' => 'Membersihkan kulit sekitar area insisi, menutup luka dengan kasa steril, dan merapikan alat intra op.',
            'evaluation' => 'Luka tertutup rapi dan bersih.',
        ],
    ];

    protected $fillable = [
        'case_id',
        'phase',
        'performed_at',
        'performed_by_role',
        'action',
        'evaluation',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phase' => NursingPhase::class,
            'performed_at' => 'datetime',
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
