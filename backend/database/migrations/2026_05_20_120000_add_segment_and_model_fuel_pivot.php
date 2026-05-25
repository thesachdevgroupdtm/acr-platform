<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            if (! Schema::hasColumn('car_models', 'segment')) {
                $table->string('segment')->nullable()->after('slug');
                $table->index('segment');
            }
        });

        if (! Schema::hasTable('car_model_fuel_types')) {
            Schema::create('car_model_fuel_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('car_model_id')->constrained('car_models')->cascadeOnDelete();
                $table->foreignId('fuel_type_id')->constrained('fuel_types')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['car_model_id', 'fuel_type_id'], 'car_model_fuel_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_fuel_types');
        Schema::table('car_models', function (Blueprint $table) {
            if (Schema::hasColumn('car_models', 'segment')) {
                $table->dropIndex(['segment']);
                $table->dropColumn('segment');
            }
        });
    }
};
