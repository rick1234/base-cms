<?php

namespace App\Livewire\Admin\Events;

use App\Models\Cms\Event;
use App\Models\Cms\EventPart;
use App\Models\Cms\EventScheduleGroup;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class EventScheduleEditor extends Component
{
    public int $eventId;

    /**
     * @var list<array<string, mixed>>
     */
    public array $groups = [];

    public ?string $message = null;

    public string $messageLevel = 'success';

    public int $nextTemporaryId = -1;

    public function mount(int $eventId): void
    {
        $this->ensureAuthorized();

        $this->eventId = $eventId;
        $this->loadSchedule();
    }

    public function addGroup(): void
    {
        $this->groups[] = $this->blankGroup(__('Nieuwe set'), count($this->groups) + 1);
        $this->message = null;
    }

    public function removeGroup(int $index): void
    {
        if (! isset($this->groups[$index])) {
            return;
        }

        array_splice($this->groups, $index, 1);
        $this->reindexGroups();
        $this->message = null;
    }

    public function toggleGroup(int $index): void
    {
        if (! isset($this->groups[$index])) {
            return;
        }

        $this->groups[$index]['is_collapsed'] = ! (bool) ($this->groups[$index]['is_collapsed'] ?? false);
    }

    public function addItem(int $groupIndex): void
    {
        if (! isset($this->groups[$groupIndex])) {
            return;
        }

        $this->groups[$groupIndex]['is_collapsed'] = false;
        $items = $this->groups[$groupIndex]['items'] ?? [];
        $items[] = $this->blankItem(count($items) + 1);
        $this->groups[$groupIndex]['items'] = $items;
        $this->message = null;
    }

    public function removeItem(int $groupIndex, int $itemIndex): void
    {
        if (! isset($this->groups[$groupIndex]['items'][$itemIndex])) {
            return;
        }

        array_splice($this->groups[$groupIndex]['items'], $itemIndex, 1);
        $this->reindexItems($groupIndex);
        $this->message = null;
    }

    public function sortGroup(int $targetId, int $draggedId, string $position): void
    {
        $this->groups = $this->moveStateRow($this->groups, $targetId, $draggedId, $position);
        $this->reindexGroups();
    }

    public function sortItem(int $targetId, int $draggedId, string $position): void
    {
        foreach ($this->groups as $groupIndex => $group) {
            $items = $group['items'] ?? [];

            if (! $this->containsStateIds($items, $targetId, $draggedId)) {
                continue;
            }

            $this->groups[$groupIndex]['items'] = $this->moveStateRow($items, $targetId, $draggedId, $position);
            $this->reindexItems($groupIndex);

            return;
        }
    }

    public function save(): void
    {
        $this->ensureAuthorized();

        $data = Validator::make(['groups' => $this->groups], [
            'groups' => ['array'],
            'groups.*.id' => ['nullable', 'integer', 'exists:event_schedule_groups,id'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.is_collapsed' => ['boolean'],
            'groups.*.items' => ['array'],
            'groups.*.items.*.id' => ['nullable', 'integer', 'exists:event_parts,id'],
            'groups.*.items.*.title' => ['nullable', 'string', 'max:255'],
            'groups.*.items.*.content' => ['nullable', 'string'],
            'groups.*.items.*.date' => ['nullable', 'date'],
            'groups.*.items.*.starts_at' => ['nullable', 'date_format:H:i'],
            'groups.*.items.*.ends_at' => ['nullable', 'date_format:H:i'],
        ])->validate();

        DB::transaction(function () use ($data): void {
            $event = $this->event();
            $seenGroupIds = [];
            $seenPartIds = [];

            foreach (array_values($data['groups'] ?? []) as $groupIndex => $groupData) {
                $group = $this->resolveGroup($event, (int) ($groupData['id'] ?? 0));
                $creatingGroup = ! $group->exists;

                if ($creatingGroup) {
                    $group->event_id = $event->id;
                    $group->created_by = auth()->id();
                }

                $group->fill([
                    'name' => $groupData['name'],
                    'sort_order' => $groupIndex + 1,
                    'is_collapsed' => (bool) ($groupData['is_collapsed'] ?? false),
                    'updated_by' => auth()->id(),
                ])->save();

                $seenGroupIds[] = $group->id;

                foreach (array_values($groupData['items'] ?? []) as $itemIndex => $itemData) {
                    $id = (int) ($itemData['id'] ?? 0);

                    if ($this->isBlankItem($itemData)) {
                        if ($id > 0) {
                            $event->parts()->whereKey($id)->delete();
                        }

                        continue;
                    }

                    $part = $this->resolvePart($event, $id);
                    $creatingPart = ! $part->exists;

                    if ($creatingPart) {
                        $part->event_id = $event->id;
                        $part->created_by = auth()->id();
                    }

                    $part->fill([
                        'event_schedule_group_id' => $group->id,
                        'title' => $itemData['title'] ?? null,
                        'content' => $itemData['content'] ?? null,
                        'starts_at' => $this->dateTime($itemData['date'] ?? null, $itemData['starts_at'] ?? null),
                        'ends_at' => $this->dateTime($itemData['date'] ?? null, $itemData['ends_at'] ?? null),
                        'sort_order' => $itemIndex + 1,
                        'updated_by' => auth()->id(),
                    ])->save();

                    $seenPartIds[] = $part->id;
                }
            }

            $event->parts()
                ->when($seenPartIds !== [], fn ($query) => $query->whereNotIn('id', $seenPartIds))
                ->delete();

            $event->scheduleGroups()
                ->when($seenGroupIds !== [], fn ($query) => $query->whereNotIn('id', $seenGroupIds))
                ->delete();
        });

        $this->loadSchedule();
        $this->messageLevel = 'success';
        $this->message = __('Tijdschema opgeslagen.');
    }

    public function render(): View
    {
        return view('livewire.admin.events.event-schedule-editor');
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }

    private function event(): Event
    {
        return Event::query()
            ->with(['scheduleGroups.parts', 'parts'])
            ->findOrFail($this->eventId);
    }

    private function loadSchedule(): void
    {
        $event = $this->event();
        $this->nextTemporaryId = -1;

        $this->groups = $event->scheduleGroups
            ->values()
            ->map(fn (EventScheduleGroup $group): array => [
                'state_id' => $group->id,
                'id' => $group->id,
                'name' => $group->name,
                'is_collapsed' => (bool) $group->is_collapsed,
                'sort_order' => $group->sort_order,
                'items' => $group->parts
                    ->values()
                    ->map(fn (EventPart $part): array => $this->partToArray($part))
                    ->all(),
            ])
            ->all();

        $ungroupedParts = $event->parts
            ->whereNull('event_schedule_group_id')
            ->values();

        if ($ungroupedParts->isNotEmpty()) {
            $this->groups[] = [
                ...$this->blankGroup($this->defaultGroupName($event), count($this->groups) + 1),
                'items' => $ungroupedParts
                    ->map(fn (EventPart $part): array => $this->partToArray($part))
                    ->all(),
            ];
        }
    }

    private function resolveGroup(Event $event, int $id): EventScheduleGroup
    {
        if ($id <= 0) {
            return new EventScheduleGroup;
        }

        return $event->scheduleGroups()->whereKey($id)->firstOrFail();
    }

    private function resolvePart(Event $event, int $id): EventPart
    {
        if ($id <= 0) {
            return new EventPart;
        }

        return $event->parts()->whereKey($id)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function partToArray(EventPart $part): array
    {
        return [
            'state_id' => $part->id,
            'id' => $part->id,
            'title' => $part->title,
            'content' => $part->content,
            'date' => optional($part->starts_at)->format('Y-m-d'),
            'starts_at' => optional($part->starts_at)->format('H:i'),
            'ends_at' => optional($part->ends_at)->format('H:i'),
            'sort_order' => $part->sort_order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankGroup(string $name, int $sortOrder): array
    {
        return [
            'state_id' => $this->temporaryId(),
            'id' => null,
            'name' => $name,
            'is_collapsed' => false,
            'sort_order' => $sortOrder,
            'items' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankItem(int $sortOrder): array
    {
        return [
            'state_id' => $this->temporaryId(),
            'id' => null,
            'title' => null,
            'content' => null,
            'date' => optional($this->event()->starts_at)->format('Y-m-d'),
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => $sortOrder,
        ];
    }

    private function temporaryId(): int
    {
        return $this->nextTemporaryId--;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function moveStateRow(array $rows, int $targetId, int $draggedId, string $position): array
    {
        $rows = array_values($rows);
        $draggedIndex = $this->stateIndex($rows, $draggedId);
        $targetIndex = $this->stateIndex($rows, $targetId);

        if ($draggedIndex === null || $targetIndex === null) {
            return $rows;
        }

        $dragged = $rows[$draggedIndex];
        array_splice($rows, $draggedIndex, 1);

        $targetIndex = $this->stateIndex($rows, $targetId);

        if ($targetIndex === null) {
            $rows[] = $dragged;

            return array_values($rows);
        }

        $insertAt = $position === 'after' ? $targetIndex + 1 : $targetIndex;
        array_splice($rows, $insertAt, 0, [$dragged]);

        return array_values($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function stateIndex(array $rows, int $stateId): ?int
    {
        foreach ($rows as $index => $row) {
            if ((int) ($row['state_id'] ?? 0) === $stateId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function containsStateIds(array $rows, int $targetId, int $draggedId): bool
    {
        return $this->stateIndex($rows, $targetId) !== null
            && $this->stateIndex($rows, $draggedId) !== null;
    }

    private function reindexGroups(): void
    {
        foreach ($this->groups as $index => $group) {
            $this->groups[$index]['sort_order'] = $index + 1;
        }
    }

    private function reindexItems(int $groupIndex): void
    {
        foreach (($this->groups[$groupIndex]['items'] ?? []) as $index => $item) {
            $this->groups[$groupIndex]['items'][$index]['sort_order'] = $index + 1;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isBlankItem(array $item): bool
    {
        return blank($item['title'] ?? null)
            && blank($item['content'] ?? null)
            && blank($item['date'] ?? null)
            && blank($item['starts_at'] ?? null)
            && blank($item['ends_at'] ?? null);
    }

    private function dateTime(mixed $date, mixed $time): ?Carbon
    {
        if (blank($date) && blank($time)) {
            return null;
        }

        $date = filled($date)
            ? (string) $date
            : optional($this->event()->starts_at)->format('Y-m-d');

        return Carbon::parse(($date ?: now()->toDateString()).' '.((string) ($time ?: '00:00')));
    }

    private function defaultGroupName(Event $event): string
    {
        return $event->locale === 'nl' ? 'Programma' : 'Schedule';
    }
}
