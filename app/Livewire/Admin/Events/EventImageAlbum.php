<?php

namespace App\Livewire\Admin\Events;

use App\Models\Cms\Event;
use App\Models\Cms\EventImage;
use App\Support\Admin\Events\EventMediaManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class EventImageAlbum extends Component
{
    use WithFileUploads;

    private const MAX_UPLOADS = 20;

    private const MAX_UPLOAD_KILOBYTES = 20480;

    public Event $event;

    /**
     * @var list<TemporaryUploadedFile>
     */
    public array $uploads = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $imageForms = [];

    public ?int $draggedImageId = null;

    public ?int $editingImageId = null;

    public bool $capacityVisible = false;

    public ?string $message = null;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->syncImageForms();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'uploads' => ['array', 'max:'.self::MAX_UPLOADS],
            'uploads.*' => ['image', 'max:'.self::MAX_UPLOAD_KILOBYTES],
        ];
    }

    public function uploadImages(EventMediaManager $mediaManager): void
    {
        $this->validate();

        foreach ($this->uploads as $upload) {
            $defaultText = $this->defaultTextForUpload($upload);

            $mediaManager->storeImage($this->event, $upload, $defaultText, auth()->user(), [
                'alt_text' => $defaultText,
                'title_text' => $defaultText,
            ]);
        }

        $count = count($this->uploads);
        $this->uploads = [];
        $this->message = trans_choice('{1} Image uploaded.|[2,*] Images uploaded.', $count);
        $this->syncImageForms();
    }

    public function toggleCapacity(): void
    {
        $this->capacityVisible = ! $this->capacityVisible;
    }

    public function editImage(int $imageId): void
    {
        $this->findImage($imageId);
        $this->editingImageId = $imageId;
    }

    public function closeImageEditor(): void
    {
        $this->editingImageId = null;
        $this->resetValidation();
    }

    public function saveImage(int $imageId): void
    {
        $image = $this->findImage($imageId);

        $this->validate([
            "imageForms.{$imageId}.caption" => ['nullable', 'string', 'max:255'],
            "imageForms.{$imageId}.alt_text" => ['nullable', 'string', 'max:255'],
            "imageForms.{$imageId}.title_text" => ['nullable', 'string', 'max:255'],
            "imageForms.{$imageId}.description" => ['nullable', 'string', 'max:1000'],
            "imageForms.{$imageId}.credit" => ['nullable', 'string', 'max:255'],
            "imageForms.{$imageId}.is_decorative" => ['boolean'],
        ]);

        $data = $this->imageForms[$imageId] ?? [];
        $isDecorative = (bool) ($data['is_decorative'] ?? false);

        $image->fill([
            'caption' => $data['caption'] ?? null,
            'alt_text' => $isDecorative ? null : ($data['alt_text'] ?? null),
            'title_text' => $data['title_text'] ?? null,
            'description' => $data['description'] ?? null,
            'credit' => $data['credit'] ?? null,
            'is_decorative' => $isDecorative,
            'updated_by' => auth()->id(),
        ])->save();

        $this->message = __('Image SEO options saved.');
        $this->editingImageId = null;
        $this->syncImageForms();
    }

    public function deleteImage(int $imageId, EventMediaManager $mediaManager): void
    {
        $mediaManager->deleteMedia($this->findImage($imageId), auth()->user());

        $this->message = __('Image deleted.');
        $this->editingImageId = $this->editingImageId === $imageId ? null : $this->editingImageId;
        $this->syncImageForms();
    }

    public function moveImage(int $targetImageId, ?int $draggedImageId = null, string $position = 'before'): void
    {
        $draggedImageId ??= $this->draggedImageId;

        if (! $draggedImageId || $draggedImageId === $targetImageId) {
            $this->draggedImageId = null;

            return;
        }

        $ids = $this->images()
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->values()
            ->all();

        $ids = $this->sortedIdsAfterMove($ids, $draggedImageId, $targetImageId, $position);

        if ($ids === null) {
            $this->draggedImageId = null;

            return;
        }

        app(EventMediaManager::class)->updateSortOrder(EventImage::class, $ids, auth()->user());

        $this->draggedImageId = null;
        $this->message = __('Image order saved.');
        $this->syncImageForms();
    }

    public function render(): View
    {
        return view('livewire.admin.events.event-image-album', [
            'images' => $this->images(),
            'editingImage' => $this->editingImage(),
            'uploadCapacity' => $this->uploadCapacity(),
        ]);
    }

    private function findImage(int $imageId): EventImage
    {
        return $this->event
            ->images()
            ->whereKey($imageId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, EventImage>
     */
    private function images(): Collection
    {
        return $this->event
            ->images()
            ->get();
    }

    private function editingImage(): ?EventImage
    {
        if (! $this->editingImageId) {
            return null;
        }

        return $this->images()->firstWhere('id', $this->editingImageId);
    }

    private function syncImageForms(): void
    {
        $this->imageForms = $this->images()
            ->mapWithKeys(fn (EventImage $image): array => [
                $image->id => [
                    'caption' => $image->caption,
                    'alt_text' => $image->alt_text,
                    'title_text' => $image->title_text,
                    'description' => $image->description,
                    'credit' => $image->credit,
                    'is_decorative' => $image->is_decorative,
                ],
            ])
            ->all();
    }

    /**
     * @return array{max_files: int, max_file_size: string, max_batch_size: string, max_upload_time: int, formats: string}
     */
    private function uploadCapacity(): array
    {
        $serverMaxFiles = (int) ini_get('max_file_uploads');
        $maxFiles = $serverMaxFiles > 0 ? min(self::MAX_UPLOADS, $serverMaxFiles) : self::MAX_UPLOADS;
        $livewireTemporaryMax = $this->maxKilobytesFromRules(FileUploadConfiguration::rules()) ?? self::MAX_UPLOAD_KILOBYTES;
        $phpFileMax = $this->iniKilobytes('upload_max_filesize');
        $phpBatchMax = $this->iniKilobytes('post_max_size');
        $perFileCandidates = array_filter(
            [self::MAX_UPLOAD_KILOBYTES, $livewireTemporaryMax, $phpFileMax, $phpBatchMax],
            fn (?int $kilobytes): bool => $kilobytes !== null && $kilobytes > 0,
        );
        $maxFileKilobytes = min($perFileCandidates ?: [self::MAX_UPLOAD_KILOBYTES]);
        $batchCandidates = [$maxFileKilobytes * $maxFiles];

        if ($phpBatchMax !== null && $phpBatchMax > 0) {
            $batchCandidates[] = $phpBatchMax;
        }

        return [
            'max_files' => $maxFiles,
            'max_file_size' => $this->formatKilobytes($maxFileKilobytes),
            'max_batch_size' => $this->formatKilobytes(min($batchCandidates)),
            'max_upload_time' => FileUploadConfiguration::maxUploadTime(),
            'formats' => __('JPG, PNG, GIF en WebP'),
        ];
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

    private function defaultTextForUpload(TemporaryUploadedFile $upload): string
    {
        return str(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME))
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
    }
}
