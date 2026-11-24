<?php

use App\Actions\ManageCaseNursingDiagnosis;
use App\Enums\CaseStatus;
use App\Enums\NurseType;
use App\Enums\NursingPhase;
use App\Models\AuditActivity;
use App\Models\CaseInterventionItem;
use App\Models\CaseNursingDiagnosis;
use App\Models\NursingDiagnosis;
use App\Models\SikiItem;
use App\Models\SurgeryCase;
use App\Models\User;
use Database\Seeders\MasterSeeder;

beforeEach(function () {
    $this->seed(MasterSeeder::class);
});

test('endpoint toggle intervensi SIKI menolak akses lintas kasus pada seluruh fase dengan 404 tanpa mengubah status', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $caseA = SurgeryCase::factory()->atStatus(CaseStatus::PreOp)->create(['created_by' => $perawat->id]);
    $caseB = SurgeryCase::factory()->atStatus(CaseStatus::IntraOp)->create(['created_by' => $perawat->id]);
    $casePostB = SurgeryCase::factory()->atStatus(CaseStatus::PascaOp)->create(['created_by' => $perawat->id]);

    $sikiItem = SikiItem::query()->firstOrFail();

    $preDiagA = CaseNursingDiagnosis::query()->create([
        'case_id' => $caseA->id,
        'phase' => NursingPhase::Pre,
        'custom_text' => 'Ansietas Pre-Op Kasus A',
        'created_by' => $perawat->id,
    ]);
    $preItemA = CaseInterventionItem::query()->create([
        'case_nursing_diagnosis_id' => $preDiagA->id,
        'siki_item_id' => $sikiItem->id,
        'is_done' => false,
    ]);

    $intraDiagB = CaseNursingDiagnosis::query()->create([
        'case_id' => $caseB->id,
        'phase' => NursingPhase::Intra,
        'custom_text' => 'Risiko Hipotermi Kasus B',
        'created_by' => $perawat->id,
    ]);
    $intraItemB = CaseInterventionItem::query()->create([
        'case_nursing_diagnosis_id' => $intraDiagB->id,
        'siki_item_id' => $sikiItem->id,
        'is_done' => false,
    ]);

    $postDiagB = CaseNursingDiagnosis::query()->create([
        'case_id' => $casePostB->id,
        'phase' => NursingPhase::Post,
        'custom_text' => 'Nyeri Akut Pasca-Op Kasus C',
        'created_by' => $perawat->id,
    ]);
    $postItemB = CaseInterventionItem::query()->create([
        'case_nursing_diagnosis_id' => $postDiagB->id,
        'siki_item_id' => $sikiItem->id,
        'is_done' => false,
    ]);

    // Pre-Op endpoint targeting Case B's item via Case A URL
    $this->actingAs($perawat)
        ->post(route('cases.preop.toggle-intervention', [$caseA, $intraItemB]))
        ->assertNotFound();

    // Intra-Op endpoint targeting Case A's item via Case B URL
    $this->actingAs($perawat)
        ->post(route('cases.intraop.toggle-intervention', [$caseB, $preItemA]))
        ->assertNotFound();

    // Post-Op endpoint targeting Case A's item via Case C URL
    $this->actingAs($perawat)
        ->post(route('cases.postop.toggle-intervention', [$casePostB, $preItemA]))
        ->assertNotFound();

    // Post-Op endpoint targeting Case B's item via Case C URL
    $this->actingAs($perawat)
        ->post(route('cases.postop.toggle-intervention', [$casePostB, $intraItemB]))
        ->assertNotFound();

    expect($preItemA->fresh()?->is_done)->toBeFalse();
    expect($intraItemB->fresh()?->is_done)->toBeFalse();
    expect($postItemB->fresh()?->is_done)->toBeFalse();
});

test('endpoint diagnosis dan intervensi menolak akses lintas fase dalam kasus yang sama dengan 404', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()->atStatus(CaseStatus::PascaOp)->create(['created_by' => $perawat->id]);
    $sikiItem = SikiItem::query()->firstOrFail();

    $preDiag = CaseNursingDiagnosis::query()->create([
        'case_id' => $case->id,
        'phase' => NursingPhase::Pre,
        'custom_text' => 'Diagnosis Pre-Op Uji Fase',
        'created_by' => $perawat->id,
    ]);
    $preItem = CaseInterventionItem::query()->create([
        'case_nursing_diagnosis_id' => $preDiag->id,
        'siki_item_id' => $sikiItem->id,
        'is_done' => false,
    ]);

    $intraDiag = CaseNursingDiagnosis::query()->create([
        'case_id' => $case->id,
        'phase' => NursingPhase::Intra,
        'custom_text' => 'Diagnosis Intra-Op Uji Fase',
        'created_by' => $perawat->id,
    ]);
    $intraItem = CaseInterventionItem::query()->create([
        'case_nursing_diagnosis_id' => $intraDiag->id,
        'siki_item_id' => $sikiItem->id,
        'is_done' => false,
    ]);

    $this->actingAs($perawat)
        ->post(route('cases.intraop.toggle-intervention', [$case, $preItem]))
        ->assertNotFound();

    $this->actingAs($perawat)
        ->post(route('cases.postop.toggle-intervention', [$case, $intraItem]))
        ->assertNotFound();

    $this->actingAs($perawat)
        ->delete(route('cases.intraop.remove-diagnosis', [$case, $preDiag]))
        ->assertNotFound();

    $this->actingAs($perawat)
        ->delete(route('cases.preop.remove-diagnosis', [$case, $intraDiag]))
        ->assertNotFound();

    expect($preItem->fresh()?->is_done)->toBeFalse();
    expect($intraItem->fresh()?->is_done)->toBeFalse();
    expect(CaseNursingDiagnosis::query()->whereKey($preDiag->id)->exists())->toBeTrue();
    expect(CaseNursingDiagnosis::query()->whereKey($intraDiag->id)->exists())->toBeTrue();
});

test('toggle intervensi SIKI mencatat AuditActivity klinis dengan case_id, pelaku, serta nilai lama dan baru', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()->atStatus(CaseStatus::PreOp)->create(['created_by' => $perawat->id]);

    $masterPreDiag = NursingDiagnosis::query()
        ->where('phase', NursingPhase::Pre->value)
        ->whereHas('sikiInterventions.items')
        ->firstOrFail();

    $action = app(ManageCaseNursingDiagnosis::class);
    $caseDiag = $action->addDiagnosis($case, [
        'nursing_diagnosis_id' => $masterPreDiag->id,
        'phase' => NursingPhase::Pre->value,
    ], $perawat);

    $item = CaseInterventionItem::query()->where('case_nursing_diagnosis_id', $caseDiag->id)->firstOrFail();
    expect($item->is_done)->toBeFalse();

    $this->actingAs($perawat)
        ->post(route('cases.preop.toggle-intervention', [$case, $item]))
        ->assertRedirect();

    $item->refresh();
    expect($item->is_done)->toBeTrue();

    $audit = AuditActivity::query()
        ->where('log_name', 'clinical')
        ->where('subject_type', CaseInterventionItem::class)
        ->where('subject_id', $item->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit?->case_id)->toBe($case->id);
    expect($audit?->causer_id)->toBe($perawat->id);
    expect($audit?->properties['old']['is_done'] ?? null)->toBeFalse();
    expect($audit?->properties['attributes']['is_done'] ?? null)->toBeTrue();
});

test('header HTTP tidak mengekspos X-Powered-By dan meta SEO hanya terbit pada halaman login publik', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();

    $loginResponse = $this->get('/login')->assertOk();
    expect($loginResponse->headers->has('X-Powered-By'))->toBeFalse();

    $loginHtml = $loginResponse->getContent();
    expect($loginHtml)->toContain('<meta name="robots" content="index, follow">')
        ->toContain('property="og:image"')
        ->toContain('application/ld+json');

    $dashboardResponse = $this->actingAs($perawat)->get('/dashboard')->assertOk();
    expect($dashboardResponse->headers->has('X-Powered-By'))->toBeFalse();

    $dashboardHtml = $dashboardResponse->getContent();
    expect($dashboardHtml)->toContain('<meta name="robots" content="noindex, nofollow">')
        ->not->toContain('application/ld+json')
        ->not->toContain('property="og:image"');
});
