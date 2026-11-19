<?php

use App\Models\AnesthesiaType;
use App\Models\Instrument;
use App\Models\MedicalDiagnosis;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SurgeryType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

test('admin dapat mengelola seluruh 5 master data beserta pencarian dan toggle aktif', function () {
    $admin = User::factory()->admin()->create();

    $cases = [
        [
            'url' => '/admin/master/ruang',
            'model' => Room::class,
            'create' => ['code' => 'OK 9', 'name' => 'Kamar Operasi 9'],
            'update' => ['code' => 'OK 9', 'name' => 'Kamar Operasi 9 Bedah'],
            'uniqueField' => 'code',
        ],
        [
            'url' => '/admin/master/alat',
            'model' => Instrument::class,
            'create' => ['name' => 'C-Arm Radiologi', 'category' => 'Radiologi'],
            'update' => ['name' => 'C-Arm Radiologi', 'category' => 'Elektromedik'],
            'uniqueField' => 'name',
        ],
        [
            'url' => '/admin/master/jenis-operasi',
            'model' => SurgeryType::class,
            'create' => ['name' => 'Tonsilektomi', 'specialty' => 'THT'],
            'update' => ['name' => 'Tonsilektomi', 'specialty' => 'Bedah THT-KL'],
            'uniqueField' => 'name',
        ],
        [
            'url' => '/admin/master/jenis-anestesi',
            'model' => AnesthesiaType::class,
            'create' => ['code' => 'TIVA', 'name' => 'Total Intravenous Anesthesia'],
            'update' => ['code' => 'TIVA', 'name' => 'Anestesi Intravena Total'],
            'uniqueField' => 'code',
        ],
        [
            'url' => '/admin/master/diagnosis-medis',
            'model' => MedicalDiagnosis::class,
            'create' => ['name' => 'Katarak Senilis'],
            'update' => ['name' => 'Katarak Senilis Matur'],
            'uniqueField' => 'name',
        ],
    ];

    foreach ($cases as $case) {
        $this->actingAs($admin)->get($case['url'])->assertOk();

        $this->actingAs($admin)
            ->post($case['url'], $case['create'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        /** @var class-string<Model> $modelClass */
        $modelClass = $case['model'];
        $record = $modelClass::query()->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->post($case['url'], $case['create'])
            ->assertSessionHasErrors($case['uniqueField']);

        $this->actingAs($admin)
            ->put("{$case['url']}/{$record->id}", $case['update'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->patch("{$case['url']}/{$record->id}/toggle-active")
            ->assertSessionHas('success');

        expect((bool) $record->fresh()->getAttribute('is_active'))->toBeFalse();

        $this->actingAs($admin)
            ->delete("{$case['url']}/{$record->id}")
            ->assertForbidden();

        expect($modelClass::query()->find($record->id))->not->toBeNull();
    }

    expect(Activity::query()->where('log_name', 'master')->count())->toBeGreaterThanOrEqual(10);
});

test('admin dapat memperbarui pengaturan rumah sakit', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/pengaturan/rumah-sakit')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/hospital'));

    $this->actingAs($admin)
        ->put('/pengaturan/rumah-sakit', [
            'hospital_name' => 'RS Pendidikan Uji Coba',
            'hospital_address' => 'Jl. Merdeka No. 10, Jember',
            'hospital_phone' => '(0331) 123456',
            'hospital_email' => 'info@rsujicoba.test',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(Setting::getValue('hospital_name'))->toBe('RS Pendidikan Uji Coba');
    expect(Setting::getValue('hospital_address'))->toBe('Jl. Merdeka No. 10, Jember');
});
