<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spreadsheet_imports', function (Blueprint $table) {
            $table->id();
            $table->string('tab_name');
            $table->longText('data');

            //Добавить morph to для ресурсов
            $table->nullableMorphs('resource');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(app()->isLocal() === true)
            Schema::dropIfExists('spreadsheet_imports');
    }
};
