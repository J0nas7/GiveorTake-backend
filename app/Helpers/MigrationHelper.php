<?php

namespace App\Helpers;

use Illuminate\Database\Schema\Blueprint;

class MigrationHelper
{
    /**
     * Add common dateTime fields to a table.
     *
     * @param Blueprint $table
     * @param string $prefix
     */
    public static function addDateTimeFields(Blueprint $table, string $prefix)
    {
        $table->dateTime($prefix . 'CreatedAt')->nullable();
        $table->dateTime($prefix . 'UpdatedAt')->nullable();
        $table->dateTime($prefix . 'DeletedAt')->nullable();
    }
}
