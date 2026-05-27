<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AttachmentManager extends Component
{
    use WithFileUploads;

    private const MAX_UPLOADS = 20;

    public string $module;

    public int $recordId;

    /**
     * @var list<TemporaryUploadedFile>
     */
    public array $incomingUploads = [];

    /**
     * @var list<TemporaryUploadedFile>
     */
    public array $queuedUploads = [];

    /**
     * @var list<string>
     */
    public array $queuedNames = [];

    /**
     * @var array<int, array{name: string|null}>
     */
    public array $attachmentForms = [];

    public int $fileInputKey = 0;

    public ?int $draggedAttachmentId = null;

    public bool $capacityVisible = false;

    public ?string $message = null;

    public function mount(string $module, int $recordId): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
        abort_unless(array_key_exists($module, config('cms_attachments', [])), 404);

        $this->module = $module;
        $this->recordId = $recordId;

        $this->record();
        $this->syncAttachmentForms();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'queuedUploads' => ['array', 'max:'.$this->maxUploads()],
            'queuedUploads.*' => ['file', 'max:'.$this->maxUploadKilobytes()],
            'queuedNames' => ['array'],
            'queuedNames.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updatedIncomingUploads(): void
    {
        $this->ensureAuthorized();

        $newUploads = $this->normalizedUploads($this->incomingUploads);
        $availableSlots = $this->maxUploads() - count($this->queuedUploads);

        if ($availableSlots < count($newUploads)) {
            $this->addError('incomingUploads', trans('validation.custom.incomingUploads.max', ['max' => $this->maxUploads()]));
            $this->resetIncomingUploads();

            return;
        }

        $this->validate([
            'incomingUploads' => ['array', 'max:'.max(0, $availableSlots)],
            'incomingUploads.*' => ['file', 'max:'.$this->maxUploadKilobytes()],
        ]);

        foreach ($newUploads as $upload) {
            $this->queuedUploads[] = $upload;
            $this->queuedNames[] = $this->defaultNameForUpload($upload);
        }

        $this->resetIncomingUploads();
        $this->resetValidation(['incomingUploads', 'incomingUploads.*']);
    }

    public function removeQueuedUpload(int $index): void
    {
        $this->ensureAuthorized();

        if (! array_key_exists($index, $this->queuedUploads)) {
            return;
        }

        array_splice($this->queuedUploads, $index, 1);
        array_splice($this->queuedNames, $index, 1);

        $this->fileInputKey++;
        $this->resetValidation();
    }

    public function toggleCapacity(): void
    {
        $this->capacityVisible = ! $this->capacityVisible;
    }

    public function uploadAttachments(): void
    {
        $this->ensureAuthorized();
        $this->validate();

        foreach ($this->queuedUploads as $index => $upload) {
            $this->storeAttachment($upload, $this->queuedNames[$index] ?? null);
        }

        $count = count($this->queuedUploads);
        $this->queuedUploads = [];
        $this->queuedNames = [];
        $this->fileInputKey++;
        $this->message = trans_choice('{1} Bijlage geupload.|[2,*] Bijlagen geupload.', $count);
        $this->syncAttachmentForms();
    }

    public function saveAttachment(int $attachmentId): void
    {
        $this->ensureAuthorized();

        $attachment = $this->findAttachment($attachmentId);

        $this->validate([
            "attachmentForms.{$attachmentId}.name" => ['nullable', 'string', 'max:255'],
        ]);

        $attachment->fill([
            'name' => $this->attachmentForms[$attachmentId]['name'] ?? $attachment->name,
            'updated_by' => auth()->id(),
        ])->save();

        $this->message = __('Bijlage opgeslagen.');
        $this->syncAttachmentForms();
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->ensureAuthorized();

        $attachment = $this->findAttachment($attachmentId);
        $this->deleteStoredFile((string) $attachment->getAttribute('url'));
        $attachment->forceFill(['updated_by' => auth()->id()])->save();
        $attachment->delete();

        $this->message = __('Bijlage verwijderd.');
        $this->syncAttachmentForms();
    }

    public function moveAttachment(int $targetAttachmentId, ?int $draggedAttachmentId = null, string $position = 'before'): void
    {
        $this->ensureAuthorized();
        $draggedAttachmentId ??= $this->draggedAttachmentId;

        if (! $draggedAttachmentId || $draggedAttachmentId === $targetAttachmentId) {
            $this->draggedAttachmentId = null;

            return;
        }

        $ids = $this->attachments()
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->values()
            ->all();

        $ids = $this->sortedIdsAfterMove($ids, $draggedAttachmentId, $targetAttachmentId, $position);

        if ($ids === null) {
            $this->draggedAttachmentId = null;

            return;
        }

        $attachmentModelClass = $this->attachmentModelClass();

        foreach ($ids as $index => $id) {
            $attachmentModelClass::query()
                ->whereKey($id)
                ->update([
                    'sort_order' => $index + 1,
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
        }

        $this->draggedAttachmentId = null;
        $this->message = __('Bijlage volgorde opgeslagen.');
        $this->syncAttachmentForms();
    }

    public function render(): View
    {
        return view('livewire.admin.attachment-manager', [
            'attachments' => $this->attachments(),
            'uploadCapacity' => $this->uploadCapacity(),
        ]);
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    private function normalizedUploads(mixed $uploads): array
    {
        if ($uploads instanceof TemporaryUploadedFile) {
            return [$uploads];
        }

        if (! is_array($uploads)) {
            return [];
        }

        return array_values(array_filter($uploads, fn (mixed $upload): bool => $upload instanceof TemporaryUploadedFile));
    }

    private function resetIncomingUploads(): void
    {
        $this->incomingUploads = [];
        $this->fileInputKey++;
    }

    private function storeAttachment(TemporaryUploadedFile $file, ?string $name): Model
    {
        $attachmentModelClass = $this->attachmentModelClass();
        $path = $file->storeAs($this->storageDirectory(), $this->storedFileName($file), 'public');

        return $attachmentModelClass::query()->create([
            $this->foreignKey() => $this->recordId,
            'name' => filled($name) ? $name : $this->defaultNameForUpload($file),
            'type' => $file->getClientMimeType(),
            'url' => 'storage/'.str_replace('\\', '/', $path),
            'sort_order' => $this->nextSortOrder(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    private function findAttachment(int $attachmentId): Model
    {
        return $this->record()
            ->{$this->relationName()}()
            ->whereKey($attachmentId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Model>
     */
    private function attachments(): Collection
    {
        return $this->record()
            ->{$this->relationName()}()
            ->get()
            ->map(fn (Model $attachment): Model => $attachment);
    }

    private function syncAttachmentForms(): void
    {
        $this->attachmentForms = $this->attachments()
            ->mapWithKeys(fn (Model $attachment): array => [
                $attachment->getKey() => [
                    'name' => $attachment->getAttribute('name'),
                ],
            ])
            ->all();
    }

    private function nextSortOrder(): int
    {
        return (int) $this->record()->{$this->relationName()}()->max('sort_order') + 1;
    }

    private function storedFileName(TemporaryUploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'upload';

        return $name.'-'.Str::random(8).($extension ? '.'.$extension : '');
    }

    private function defaultNameForUpload(TemporaryUploadedFile $file): string
    {
        return pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: $file->getClientOriginalName();
    }

    private function deleteStoredFile(string $url): void
    {
        if (Str::startsWith($url, 'storage/')) {
            Storage::disk('public')->delete(Str::after($url, 'storage/'));
        }
    }

    /**
     * @return array{max_files: int, max_file_size: string, max_batch_size: string, max_upload_time: int, formats: string}
     */
    private function uploadCapacity(): array
    {
        $phpFileMax = $this->iniKilobytes('upload_max_filesize');
        $phpBatchMax = $this->iniKilobytes('post_max_size');
        $livewireTemporaryMax = $this->maxKilobytesFromRules(FileUploadConfiguration::rules());
        $perFileCandidates = array_filter(
            [$this->maxUploadKilobytes(), $livewireTemporaryMax, $phpFileMax, $phpBatchMax],
            fn (?int $kilobytes): bool => $kilobytes !== null && $kilobytes > 0,
        );
        $maxFileKilobytes = min($perFileCandidates ?: [$this->maxUploadKilobytes()]);
        $batchCandidates = [$maxFileKilobytes * $this->maxUploads()];

        if ($phpBatchMax !== null && $phpBatchMax > 0) {
            $batchCandidates[] = $phpBatchMax;
        }

        return [
            'max_files' => $this->maxUploads(),
            'max_file_size' => $this->formatKilobytes($maxFileKilobytes),
            'max_batch_size' => $this->formatKilobytes(min($batchCandidates)),
            'max_upload_time' => FileUploadConfiguration::maxUploadTime(),
            'formats' => __('PDF, Word, Excel, afbeeldingen en overige veilige bestanden'),
        ];
    }

    private function maxUploads(): int
    {
        $serverMaxFiles = (int) ini_get('max_file_uploads');

        return $serverMaxFiles > 0 ? min(self::MAX_UPLOADS, $serverMaxFiles) : self::MAX_UPLOADS;
    }

    private function maxUploadKilobytes(): int
    {
        return (int) $this->definition('max_upload_kilobytes');
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function maxKilobytesFromRules(array $rules): ?int
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && preg_match('/^max:(\d+)$/', $rule, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function iniKilobytes(string $key): ?int
    {
        $value = ini_get($key);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $this->sizeToKilobytes($value);
    }

    private function sizeToKilobytes(string $value): ?int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return null;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        $bytes = match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };

        return max(1, (int) ceil($bytes / 1024));
    }

    private function formatKilobytes(int $kilobytes): string
    {
        if ($kilobytes < 1024) {
            return $kilobytes.' KB';
        }

        $megabytes = $kilobytes / 1024;
        $formatted = fmod($megabytes, 1.0) === 0.0
            ? (string) (int) $megabytes
            : number_format($megabytes, 1, app()->getLocale() === 'nl' ? ',' : '.', app()->getLocale() === 'nl' ? '.' : ',');

        return $formatted.' MB';
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>|null
     */
    private function sortedIdsAfterMove(array $ids, int $draggedId, int $targetId, string $position): ?array
    {
        $from = array_search($draggedId, $ids, true);

        if ($from === false || ! in_array($targetId, $ids, true)) {
            return null;
        }

        array_splice($ids, $from, 1);
        $to = array_search($targetId, $ids, true);

        if ($to === false) {
            return null;
        }

        if ($position === 'after') {
            $to++;
        }

        array_splice($ids, $to, 0, [$draggedId]);

        return array_values($ids);
    }

    private function record(): Model
    {
        $modelClass = $this->modelClass();

        return $modelClass::query()->findOrFail($this->recordId);
    }

    /**
     * @return class-string<Model>
     */
    private function modelClass(): string
    {
        return (string) $this->definition('model');
    }

    /**
     * @return class-string<Model>
     */
    private function attachmentModelClass(): string
    {
        return (string) $this->definition('attachment_model');
    }

    private function relationName(): string
    {
        return (string) $this->definition('relation');
    }

    private function foreignKey(): string
    {
        return (string) $this->definition('foreign_key');
    }

    private function storageDirectory(): string
    {
        return (string) $this->definition('storage_directory');
    }

    private function definition(string $key): mixed
    {
        return config("cms_attachments.{$this->module}.{$key}");
    }

    private function ensureAuthorized(): void
    {
        abort_unless(auth()->user()?->can('access-admin'), 403);
    }
}
