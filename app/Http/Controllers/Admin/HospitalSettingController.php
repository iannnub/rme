<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateHospitalSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HospitalSettingController extends Controller
{
    public function edit(): Response
    {
        $this->authorize('viewAny', Setting::class);

        return Inertia::render('settings/hospital', [
            'hospital' => Setting::hospitalProfile(),
        ]);
    }

    public function update(UpdateHospitalSettingRequest $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        foreach ($request->validated() as $key => $value) {
            Setting::setValue($key, $value);
        }

        return to_route('hospital-settings.edit')->with('success', 'Data rumah sakit berhasil diperbarui.');
    }
}
