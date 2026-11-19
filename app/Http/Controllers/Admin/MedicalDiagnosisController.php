<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreMedicalDiagnosisRequest;
use App\Http\Requests\Admin\UpdateMedicalDiagnosisRequest;
use App\Models\MedicalDiagnosis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class MedicalDiagnosisController extends BaseMasterController
{
    protected function modelClass(): string
    {
        return MedicalDiagnosis::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.master.medical-diagnoses';
    }

    protected function pageTitle(): string
    {
        return 'Master Diagnosis Medis';
    }

    protected function pageDescription(): string
    {
        return 'Kelola daftar diagnosis medis pra-operasi untuk pilihan rencana tindakan.';
    }

    protected function itemLabel(): string
    {
        return 'Diagnosis medis';
    }

    protected function searchColumns(): array
    {
        return ['name'];
    }

    protected function orderByColumn(): string
    {
        return 'name';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nama Diagnosis Medis'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama Diagnosis Medis', 'required' => true, 'placeholder' => 'contoh: Apendisitis Akut', 'maxLength' => 255],
        ];
    }

    protected function transformItem(Model $item): array
    {
        /** @var MedicalDiagnosis $item */
        return [
            'id' => $item->id,
            'name' => $item->name,
            'is_active' => $item->is_active,
        ];
    }

    public function store(StoreMedicalDiagnosisRequest $request): RedirectResponse
    {
        return $this->handleStore($request->validated());
    }

    public function update(UpdateMedicalDiagnosisRequest $request, MedicalDiagnosis $medicalDiagnosis): RedirectResponse
    {
        return $this->handleUpdate($medicalDiagnosis, $request->validated());
    }

    public function toggleActive(MedicalDiagnosis $medicalDiagnosis): RedirectResponse
    {
        return $this->handleToggleActive($medicalDiagnosis);
    }

    public function destroy(MedicalDiagnosis $medicalDiagnosis): RedirectResponse
    {
        return $this->handleDestroy($medicalDiagnosis);
    }
}
