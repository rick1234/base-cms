<?php

namespace App\Actions\Admin\Forms;

use App\Models\Cms\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateForm
{
    public function handle(Form $form, ?Authenticatable $actor = null): Form
    {
        return DB::transaction(function () use ($form, $actor): Form {
            $form->load([
                'categories',
                'recipients',
                'messages',
                'blocks.rows.fields.options',
            ]);

            $copy = $form->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
            $copy->name = $form->name.' copy';
            $copy->slug = Str::slug($form->slug ?: $form->name).'-copy-'.Str::lower(Str::random(4));
            $copy->status = 'draft';
            $copy->created_by = $actor?->getAuthIdentifier();
            $copy->updated_by = $actor?->getAuthIdentifier();
            $copy->save();

            $copy->categories()->sync(
                $form->categories
                    ->mapWithKeys(fn ($category, int $index): array => [$category->id => ['sort_order' => $index + 1]])
                    ->all()
            );

            foreach ($form->recipients as $recipient) {
                $newRecipient = $recipient->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                $newRecipient->form_id = $copy->id;
                $newRecipient->created_by = $actor?->getAuthIdentifier();
                $newRecipient->updated_by = $actor?->getAuthIdentifier();
                $newRecipient->save();
            }

            foreach ($form->messages as $message) {
                $newMessage = $message->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                $newMessage->form_id = $copy->id;
                $newMessage->created_by = $actor?->getAuthIdentifier();
                $newMessage->updated_by = $actor?->getAuthIdentifier();
                $newMessage->save();
            }

            foreach ($form->blocks as $block) {
                $newBlock = $block->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                $newBlock->form_id = $copy->id;
                $newBlock->created_by = $actor?->getAuthIdentifier();
                $newBlock->updated_by = $actor?->getAuthIdentifier();
                $newBlock->save();

                foreach ($block->rows as $row) {
                    $newRow = $row->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                    $newRow->block_id = $newBlock->id;
                    $newRow->created_by = $actor?->getAuthIdentifier();
                    $newRow->updated_by = $actor?->getAuthIdentifier();
                    $newRow->save();

                    foreach ($row->fields as $field) {
                        $newField = $field->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                        $newField->row_id = $newRow->id;
                        $newField->created_by = $actor?->getAuthIdentifier();
                        $newField->updated_by = $actor?->getAuthIdentifier();
                        $newField->save();

                        foreach ($field->options as $option) {
                            $newOption = $option->replicate(['uuid', 'legacy_id', 'created_at', 'updated_at']);
                            $newOption->field_id = $newField->id;
                            $newOption->created_by = $actor?->getAuthIdentifier();
                            $newOption->updated_by = $actor?->getAuthIdentifier();
                            $newOption->save();
                        }
                    }
                }
            }

            return $copy->refresh();
        });
    }
}
