<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('rbac_logs');
    }

    public function down(): void
    {
        // The old access log was a legacy import artifact and is intentionally not recreated.
    }
};
