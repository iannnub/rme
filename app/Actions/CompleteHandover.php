<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\HandoverDestination;
use App\Models\Handover;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Services\HandoverSummaryBuilder;
use App\Support\AuditContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteHandover
{
    public function __construct(
        private readonly HandoverSummaryBuilder $summaryBuilder,
        private readonly AdvanceCaseStatus $advanceCaseStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor, bool $completeCase = false): Handover
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah data serah terimanya.'],
            ]);
        }

        $case->loadMissing(['postopObservations', 'postopSummary']);

        $hasObservation = $case->postopObservations->isNotEmpty()
            || $case->readinessFlag('postop_observation_completed');

        $summary = $case->postopSummary;
        $preTransferRecorded = ($summary !== null
            && $summary->ready_to_transfer
            && $summary->pre_transfer_condition_notes !== null
            && trim($summary->pre_transfer_condition_notes) !== '')
            || $case->readinessFlag('pre_transfer_condition_recorded');

        if (! $hasObservation || ! $preTransferRecorded) {
            throw ValidationException::withMessages([
                'handover' => ['Serah terima hanya dapat dilakukan jika minimal 1 observasi pasca-operasi dan kondisi pasien sebelum dipindahkan (layak pindah) telah tercatat.'],
            ]);
        }

        $destination = HandoverDestination::from((string) $data['destination']);
        $destinationDetail = is_string($data['destination_detail'] ?? null) ? trim($data['destination_detail']) : '';
        if ($destination === HandoverDestination::Lainnya && $destinationDetail === '') {
            throw ValidationException::withMessages([
                'destination_detail' => ['Detail tujuan pemindahan wajib diisi jika tujuan dipilih Lainnya.'],
            ]);
        }

        $ongoingIssues = is_string($data['ongoing_issues'] ?? null) && trim($data['ongoing_issues']) !== ''
            ? trim($data['ongoing_issues'])
            : null;
        $followUp = is_string($data['follow_up_instructions'] ?? null) && trim($data['follow_up_instructions']) !== ''
            ? trim($data['follow_up_instructions'])
            : null;

        $handedOverAt = is_string($data['handed_over_at'] ?? null) && trim($data['handed_over_at']) !== ''
            ? Carbon::parse($data['handed_over_at'], 'Asia/Jakarta')
            : now('Asia/Jakarta');

        $snapshot = $this->summaryBuilder->build($case, $ongoingIssues, $followUp);

        return DB::transaction(function () use (
            $case,
            $destination,
            $destinationDetail,
            $data,
            $handedOverAt,
            $ongoingIssues,
            $followUp,
            $snapshot,
            $actor,
            $completeCase,
        ): Handover {
            $handover = AuditContext::run(function () use (
                $case,
                $destination,
                $destinationDetail,
                $data,
                $handedOverAt,
                $ongoingIssues,
                $followUp,
                $snapshot,
                $actor,
            ): Handover {
                $payload = [
                    'destination' => $destination->value,
                    'destination_detail' => $destinationDetail !== '' ? $destinationDetail : null,
                    'documents_complete' => (bool) ($data['documents_complete'] ?? false),
                    'handed_by_id' => $actor->id,
                    'received_by_name' => trim((string) $data['received_by_name']),
                    'received_by_id' => is_numeric($data['received_by_id'] ?? null) ? (int) $data['received_by_id'] : null,
                    'handed_over_at' => $handedOverAt,
                    'ongoing_issues' => $ongoingIssues,
                    'follow_up_instructions' => $followUp,
                    'summary_snapshot' => $snapshot,
                ];

                $existing = $case->handover;
                if ($existing !== null) {
                    $existing->update($payload);

                    return $existing->fresh() ?? $existing;
                }

                return Handover::query()->create([
                    ...$payload,
                    'case_id' => $case->id,
                    'created_by' => $actor->id,
                ]);
            }, 'Menyimpan formulir serah terima pasien', $case->id, $actor);

            if ($completeCase) {
                $freshCase = $case->fresh(['postopObservations', 'postopSummary', 'handover']) ?? $case;
                if ($freshCase->status === CaseStatus::PascaOp) {
                    $freshCase = $this->advanceCaseStatus->execute($freshCase, CaseStatus::SerahTerima, $actor, 'Kondisi layak pindah, lanjut ke serah terima');
                }
                if ($freshCase->status === CaseStatus::SerahTerima) {
                    $this->advanceCaseStatus->execute($freshCase, CaseStatus::Selesai, $actor, 'Serah terima pasien selesai');
                }
            }

            return $handover;
        });
    }
}
