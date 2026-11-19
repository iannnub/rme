<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

abstract class BaseMasterController extends Controller
{
    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    abstract protected function routePrefix(): string;

    abstract protected function pageTitle(): string;

    abstract protected function pageDescription(): string;

    abstract protected function itemLabel(): string;

    /**
     * @return list<string>
     */
    abstract protected function searchColumns(): array;

    abstract protected function orderByColumn(): string;

    /**
     * @return list<array{key: string, label: string}>
     */
    abstract protected function columns(): array;

    /**
     * @return list<array{name: string, label: string, required: bool, placeholder: string, maxLength: int}>
     */
    abstract protected function fields(): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function transformItem(Model $item): array;

    public function index(Request $request): Response
    {
        $modelClass = $this->modelClass();
        $this->authorize('viewAny', $modelClass);

        $search = trim((string) $request->string('q'));
        $statusFilter = (string) $request->string('status');
        $searchColumns = $this->searchColumns();

        $items = $modelClass::query()
            ->when($search !== '', function ($query) use ($search, $searchColumns): void {
                $query->where(function ($subQuery) use ($search, $searchColumns): void {
                    foreach ($searchColumns as $index => $column) {
                        if ($index === 0) {
                            $subQuery->where($column, 'like', "%{$search}%");
                        } else {
                            $subQuery->orWhere($column, 'like', "%{$search}%");
                        }
                    }
                });
            })
            ->when(in_array($statusFilter, ['aktif', 'nonaktif'], true), function ($query) use ($statusFilter): void {
                $query->where('is_active', $statusFilter === 'aktif');
            })
            ->orderBy($this->orderByColumn())
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Model $item): array => $this->transformItem($item));

        return Inertia::render('admin/master/index', [
            'title' => $this->pageTitle(),
            'description' => $this->pageDescription(),
            'itemLabel' => $this->itemLabel(),
            'routePrefix' => $this->routePrefix(),
            'columns' => $this->columns(),
            'fields' => $this->fields(),
            'items' => $items,
            'filters' => [
                'q' => $search,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function handleStore(array $validated): RedirectResponse
    {
        $modelClass = $this->modelClass();
        $this->authorize('create', $modelClass);

        $modelClass::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', $this->itemLabel().' berhasil ditambahkan.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function handleUpdate(Model $model, array $validated): RedirectResponse
    {
        $this->authorize('update', $model);

        $model->update($validated);

        return back()->with('success', $this->itemLabel().' berhasil diperbarui.');
    }

    protected function handleToggleActive(Model $model): RedirectResponse
    {
        $this->authorize('toggleActive', $model);

        $nextActive = ! (bool) $model->getAttribute('is_active');
        $model->update(['is_active' => $nextActive]);

        $statusText = $nextActive ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', $this->itemLabel()." berhasil {$statusText}.");
    }

    protected function handleDestroy(Model $model): RedirectResponse
    {
        $this->authorize('delete', $model);

        return back();
    }
}
