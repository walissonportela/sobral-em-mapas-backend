<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('categories', 'is_active')) {
            Schema::table('categories', function (Blueprint $table) {
                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->after('name');
            });
        }

        if (!Schema::hasColumn('subcategories', 'is_active')) {
            Schema::table('subcategories', function (Blueprint $table) {
                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->after('name');
            });
        }

        if (!Schema::hasColumn('layers', 'is_active')) {
            Schema::table('layers', function (Blueprint $table) {
                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('layers', 'is_active')) {
            Schema::table('layers', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('subcategories', 'is_active')) {
            Schema::table('subcategories', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('categories', 'is_active')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};