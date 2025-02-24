<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\MigrationHelper;

class CreateOrganisationsProjectsTable extends Migration
{
    public function up()
    {
        // Organisations table
        Schema::create('GT_Organisations', function (Blueprint $table) {
            $prefix = 'Organisation_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('User_ID')->unsigned(); // Foreign key to Users table
            $table->string($prefix . 'Name', 255); // Organisation name
            $table->string($prefix . 'Description', 500)->nullable(); // Organisation description
            
            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('User_ID')->references('User_ID')->on('GT_Users')->onDelete('cascade');
        });

        // Projects table
        Schema::create('GT_Projects', function (Blueprint $table) {
            $prefix = 'Project_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Organisation_ID')->unsigned(); // Foreign key to Organisations table
            $table->string($prefix . 'Name', 255); // Project name
            $table->text($prefix . 'Description')->nullable(); // Project description
            $table->enum($prefix . 'Status', ['Planned', 'Active', 'Completed', 'On Hold'])->default('Planned'); // Status
            $table->date($prefix . 'Start_Date')->nullable(); // Start date
            $table->date($prefix . 'End_Date')->nullable(); // End date
            
            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('Organisation_ID')->references('Organisation_ID')->on('GT_Organisations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('GT_Projects');
        Schema::dropIfExists('GT_Organisations');
    }
}
