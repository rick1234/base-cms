<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $unusedTables = [
        'role_category_role',
        'role_categories',
        'user_category_roles',
        'user_sessions',
        'login_cookies',
        'password_reset_records',
        'user_tokens',
    ];

    public function up(): void
    {
        foreach ($this->unusedTables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        //
    }
};
