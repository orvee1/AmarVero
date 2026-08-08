<?php

namespace App\Livewire\Admin\Content;

use App\Enums\ContentStatus;
use App\Livewire\Admin\Content\Concerns\InteractsWithContentForms;
use App\Models\AnnouncementBar;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementBarIndex extends Component
{
    use InteractsWithContentForms, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public bool $showForm = false;

    public ?int $editingId = null;

    /**
     * @var array<string, mixed>
     */
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', AnnouncementBar::class);

        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', AnnouncementBar::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $announcementBarId): void
    {
        $announcementBar = AnnouncementBar::query()->findOrFail($announcementBarId);

        Gate::authorize('update', $announcementBar);

        $this->editingId = $announcementBar->id;
        $this->form = [
            'name' => $announcementBar->name,
            'message' => $announcementBar->message,
            'link_label' => $announcementBar->link_label,
            'link_url' => $announcementBar->link_url,
            'status' => $this->contentStatusValue($announcementBar),
            'starts_at' => $this->dateTimeInput($announcementBar->starts_at),
            'ends_at' => $this->dateTimeInput($announcementBar->ends_at),
            'sort_order' => $announcementBar->sort_order,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $announcementBar = $this->editingId
            ? AnnouncementBar::query()->findOrFail($this->editingId)
            : new AnnouncementBar;

        Gate::authorize($announcementBar->exists ? 'update' : 'create', $announcementBar->exists ? $announcementBar : AnnouncementBar::class);

        $validated = $this->validate($this->rules())['form'];

        $announcementBar->fill(array_merge([
            'name' => $this->nullableString($validated['name']),
            'message' => $this->nullableString($validated['message']),
            'link_label' => $this->nullableString($validated['link_label'] ?? null),
            'link_url' => $this->nullableString($validated['link_url'] ?? null),
            'sort_order' => $this->integerValue($validated['sort_order'] ?? 0),
        ], $this->scheduledContentFields($validated)))->save();

        $this->resetForm();
        $this->showForm = false;

        Flux::toast(variant: 'success', text: __('Announcement bar saved.'));
    }

    public function delete(int $announcementBarId): void
    {
        $announcementBar = AnnouncementBar::query()->findOrFail($announcementBarId);

        Gate::authorize('delete', $announcementBar);

        $announcementBar->delete();

        if ($this->editingId === $announcementBarId) {
            $this->resetForm();
            $this->showForm = false;
        }

        Flux::toast(variant: 'success', text: __('Announcement bar deleted.'));
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', AnnouncementBar::class);

        $announcementBars = AnnouncementBar::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('message', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.content.announcement-bar-index', [
            'announcementBars' => $announcementBars,
            'statuses' => ContentStatus::cases(),
        ])->layout('components.layouts.admin', [
            'title' => __('Announcement bars'),
            'breadcrumbs' => [
                __('Admin') => route('admin.dashboard'),
                __('Content') => null,
                __('Announcement bars') => null,
            ],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.message' => ['required', 'string', 'max:255'],
            'form.link_label' => ['nullable', 'string', 'max:80'],
            'form.link_url' => ['nullable', 'string', 'max:255'],
            'form.status' => ['required', Rule::in($this->contentStatusValues())],
            'form.starts_at' => ['nullable', 'date'],
            'form.ends_at' => ['nullable', 'date', 'after_or_equal:form.starts_at'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'message' => '',
            'link_label' => '',
            'link_url' => '',
            'status' => ContentStatus::Draft->value,
            'starts_at' => '',
            'ends_at' => '',
            'sort_order' => 0,
        ];

        $this->resetValidation();
    }

    protected function contentStatusValue(AnnouncementBar $announcementBar): string
    {
        $status = $announcementBar->getAttribute('status');

        return $status instanceof ContentStatus ? $status->value : (string) $status;
    }
}
