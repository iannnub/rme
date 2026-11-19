<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Http\Requests\Patients\StorePatientRequest;
use App\Http\Requests\Patients\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Patient::class);

        $search = trim((string) $request->string('q'));

        $patients = Patient::query()
            ->withCount('surgeryCases')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('rm_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Patient $patient): array => [
                'id' => $patient->id,
                'rm_number' => $patient->rm_number,
                'name' => $patient->name,
                'birth_date' => $patient->birth_date->format('Y-m-d'),
                'birth_date_label' => $patient->birth_date->format('d M Y'),
                'age_label' => $patient->ageLabel(),
                'gender' => $patient->gender->value,
                'gender_label' => $patient->gender->label(),
                'address' => $patient->address,
                'phone' => $patient->phone,
                'surgery_cases_count' => $patient->surgery_cases_count ?? 0,
            ]);

        return Inertia::render('patients/index', [
            'patients' => $patients,
            'filters' => [
                'q' => $search,
            ],
            'genders' => array_map(
                fn (Gender $gender): array => ['value' => $gender->value, 'label' => $gender->label()],
                Gender::cases(),
            ),
            'canManage' => $request->user()?->can('create', Patient::class) ?? false,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('create', Patient::class);

        $q = trim((string) $request->string('q'));

        $patients = Patient::query()
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($subQuery) use ($q): void {
                    $subQuery->where('rm_number', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Patient $patient): array => [
                'id' => $patient->id,
                'rm_number' => $patient->rm_number,
                'name' => $patient->name,
                'birth_date' => $patient->birth_date->format('d M Y'),
                'age_label' => $patient->ageLabel(),
                'gender_label' => $patient->gender->label(),
                'phone' => $patient->phone,
            ]);

        return response()->json(['patients' => $patients]);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        Patient::query()->create([
            ...$request->validated(),
            'created_by' => $user->id,
        ]);

        return back()->with('success', 'Data pasien berhasil ditambahkan.');
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return back()->with('success', 'Data pasien berhasil diperbarui.');
    }
}
