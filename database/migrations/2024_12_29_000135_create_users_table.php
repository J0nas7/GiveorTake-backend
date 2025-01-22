<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\MigrationHelper;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('GT_Users', function (Blueprint $table) {
            $prefix = 'User_';

            $table->bigIncrements($prefix . 'ID');
            $table->tinyInteger($prefix . 'Status');
            $table->string($prefix . 'Email', 255)->unique();
            $table->string($prefix . 'Password', 255);
            $table->string($prefix . 'Remember_Token', 255)->nullable();
            $table->string($prefix . 'FirstName', 100); // Add first name column
            $table->string($prefix . 'Surname', 100);  // Add surname column
            $table->string($prefix . 'ImageSrc', 255)->nullable();

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields
        });
    }

    public function down()
    {
        Schema::dropIfExists('GT_Users');
    }
}
