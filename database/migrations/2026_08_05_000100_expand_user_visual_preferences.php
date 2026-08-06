<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            if (!Schema::hasColumn('user_preferences', 'default_view')) {
                $table->string('default_view', 20)->default('list')->after('density');
            }
            if (!Schema::hasColumn('user_preferences', 'quick_access')) {
                $table->json('quick_access')->nullable()->after('saved_filters');
            }
            if (!Schema::hasColumn('user_preferences', 'onboarding_completed')) {
                $table->boolean('onboarding_completed')->default(false)->after('quick_access');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $columns = array_filter(
                ['default_view', 'quick_access', 'onboarding_completed'],
                fn (string $column) => Schema::hasColumn('user_preferences', $column)
            );
            if ($columns) $table->dropColumn($columns);
        });
    }
};
