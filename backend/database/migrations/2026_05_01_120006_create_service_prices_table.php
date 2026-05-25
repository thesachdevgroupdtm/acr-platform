<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->foreignId('brand_id')
                ->constrained('car_brands')
                ->cascadeOnDelete();
            $table->foreignId('model_id')
                ->constrained('car_models')
                ->cascadeOnDelete();
            $table->foreignId('fuel_type_id')
                ->constrained('fuel_types')
                ->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(
                ['service_id', 'brand_id', 'model_id', 'fuel_type_id'],
                'svcprice_full_unique'
            );
            $table->index(['brand_id', 'model_id', 'fuel_type_id'], 'svcprice_vehicle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
