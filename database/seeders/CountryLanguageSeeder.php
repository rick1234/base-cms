<?php

namespace Database\Seeders;

use App\Actions\Admin\Localization\SyncLocalizationData;
use Illuminate\Database\Seeder;

class CountryLanguageSeeder extends Seeder
{
    public function run(SyncLocalizationData $syncLocalizationData): void
    {
        $syncLocalizationData->handle();
    }
}
