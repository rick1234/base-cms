<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_product_options', function (Blueprint $table): void {
            if (! Schema::hasColumn('catalog_product_options', 'label_translations')) {
                $table->json('label_translations')->nullable()->after('label');
            }

            if (! Schema::hasColumn('catalog_product_options', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->index()->after('label_translations');
            }

            if (! Schema::hasColumn('catalog_product_options', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (! Schema::hasTable('catalog_product_option_values')) {
            Schema::create('catalog_product_option_values', function (Blueprint $table): void {
                $table->id();
                $table->char('uuid', 36)->nullable()->unique();
                $table->unsignedBigInteger('legacy_id')->nullable()->index();
                $table->unsignedBigInteger('catalog_product_option_id')->index();
                $table->string('value')->nullable();
                $table->json('value_translations')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasColumn('catalog_product_options', 'value')) {
            DB::table('catalog_product_options')
                ->orderBy('id')
                ->get()
                ->each(function (object $option): void {
                    $locale = (string) ($option->locale ?: config('cms.default_locale', app()->getLocale()));
                    $labelTranslations = [];

                    if (filled($option->label)) {
                        $labelTranslations[$locale] = $option->label;
                    }

                    DB::table('catalog_product_options')
                        ->where('id', $option->id)
                        ->update([
                            'label_translations' => $labelTranslations === [] ? null : json_encode($labelTranslations),
                            'sort_order' => $option->sort_order ?: $option->id,
                        ]);

                    if (blank($option->value)) {
                        return;
                    }

                    DB::table('catalog_product_option_values')->insert([
                        'catalog_product_option_id' => $option->id,
                        'value' => $option->value,
                        'value_translations' => json_encode([$locale => $option->value]),
                        'sort_order' => 1,
                        'created_by' => $option->created_by ?? null,
                        'updated_by' => $option->updated_by ?? null,
                        'created_at' => $option->created_at ?? now(),
                        'updated_at' => $option->updated_at ?? now(),
                    ]);
                });
        }

        if (Schema::hasColumn('catalog_product_options', 'locale')) {
            Schema::table('catalog_product_options', function (Blueprint $table): void {
                $table->dropIndex('catalog_product_options_locale_index');
                $table->dropColumn('locale');
            });
        }

        if (Schema::hasColumn('catalog_product_options', 'value')) {
            Schema::table('catalog_product_options', function (Blueprint $table): void {
                $table->dropColumn('value');
            });
        }
    }

    public function down(): void
    {
        Schema::table('catalog_product_options', function (Blueprint $table): void {
            if (! Schema::hasColumn('catalog_product_options', 'locale')) {
                $table->string('locale', 8)->default('nl')->index()->after('catalog_product_id');
            }

            if (! Schema::hasColumn('catalog_product_options', 'value')) {
                $table->longText('value')->nullable()->after('label');
            }
        });

        Schema::dropIfExists('catalog_product_option_values');

        Schema::table('catalog_product_options', function (Blueprint $table): void {
            $table->dropColumn(array_filter([
                Schema::hasColumn('catalog_product_options', 'label_translations') ? 'label_translations' : null,
                Schema::hasColumn('catalog_product_options', 'sort_order') ? 'sort_order' : null,
                Schema::hasColumn('catalog_product_options', 'deleted_at') ? 'deleted_at' : null,
            ]));
        });
    }
};
