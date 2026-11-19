<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NurseType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->string('q'));
        $roleFilter = (string) $request->string('role');
        $statusFilter = (string) $request->string('status');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('sip', 'like', "%{$search}%");
                });
            })
            ->when(Role::tryFrom($roleFilter) !== null, function ($query) use ($roleFilter): void {
                $query->where('role', $roleFilter);
            })
            ->when(in_array($statusFilter, ['aktif', 'nonaktif'], true), function ($query) use ($statusFilter): void {
                $query->where('is_active', $statusFilter === 'aktif');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'nurse_type' => $user->nurse_type?->value,
                'nurse_type_label' => $user->nurse_type?->label(),
                'nip' => $user->nip,
                'sip' => $user->sip,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'q' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
            ],
            'roles' => array_map(
                fn (Role $role): array => ['value' => $role->value, 'label' => $role->label()],
                Role::cases(),
            ),
            'nurseTypes' => array_map(
                fn (NurseType $type): array => ['value' => $type->value, 'label' => $type->label()],
                NurseType::cases(),
            ),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'],
            'nurse_type' => $validated['nurse_type'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'sip' => $validated['sip'] ?? null,
            'password' => $validated['password'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Akun pengguna berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update($request->validated());

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $nextActive = ! $user->is_active;
        $user->update(['is_active' => $nextActive]);

        $message = $nextActive
            ? 'Akun pengguna berhasil diaktifkan.'
            : 'Akun pengguna berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $user->update([
            'password' => $request->validated()['password'],
        ]);

        return back()->with('success', 'Kata sandi pengguna berhasil direset.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        return back();
    }
}
