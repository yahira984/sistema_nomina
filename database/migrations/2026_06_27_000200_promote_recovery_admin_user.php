<?php

use App\Support\SecurityPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $email = (string) config('security.recovery_admin_email');

        if ($email === '') {
            return;
        }

        DB::table('users')
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->update([
                'role' => SecurityPermissions::ADMIN,
                'permissions' => json_encode([]),
                'approved_at' => now(),
                'disabled_at' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
