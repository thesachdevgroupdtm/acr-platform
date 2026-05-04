<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5a — service_centers (FK target for orders.service_center_id).
 *
 * Per /PHASE2_CONTRACT.md decision D-2.5a-2. The service_centers
 * table is technically a Phase 2.6 deliverable, pulled forward here
 * because orders.service_center_id needs an FK target. Phase 2.6
 * may extend this table (e.g. opening hours, photos), but the
 * canonical row count and slugs are locked.
 *
 * Seeds 4 active centers from src/data/businessData.ts LOCATIONS:
 * Moti Nagar, Gurugram, Noida, Okhla. Slugs are URL-safe.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_centers', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->text('address');
            $table->string('phone', 15);
            $table->string('email')->nullable();
            $table->string('city', 80);
            $table->string('state', 80)->default('Delhi NCR');
            $table->string('pincode', 10);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('service_centers')->insert([
            [
                'slug'       => 'moti-nagar',
                'name'       => 'Moti Nagar',
                'address'    => '63, Rama Rd, Block B, Najafgarh Road Industrial Area, New Delhi, Delhi 110015',
                'phone'      => '9870400861',
                'email'      => 'info@autocarrepair.in',
                'city'       => 'Delhi',
                'state'      => 'Delhi NCR',
                'pincode'    => '110015',
                'is_active'  => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug'       => 'gurugram',
                'name'       => 'Gurugram',
                'address'    => 'Plot No. 123, Sector 14, Gurugram, Haryana 122001',
                'phone'      => '9870400861',
                'email'      => 'info@autocarrepair.in',
                'city'       => 'Gurugram',
                'state'      => 'Delhi NCR',
                'pincode'    => '122001',
                'is_active'  => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug'       => 'noida',
                'name'       => 'Noida',
                'address'    => 'C-45, Sector 63, Noida, Uttar Pradesh 201301',
                'phone'      => '9870400861',
                'email'      => 'info@autocarrepair.in',
                'city'       => 'Noida',
                'state'      => 'Delhi NCR',
                'pincode'    => '201301',
                'is_active'  => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug'       => 'okhla',
                'name'       => 'Okhla',
                'address'    => 'Phase III, Okhla Industrial Estate, New Delhi, Delhi 110020',
                'phone'      => '9870400861',
                'email'      => 'info@autocarrepair.in',
                'city'       => 'Delhi',
                'state'      => 'Delhi NCR',
                'pincode'    => '110020',
                'is_active'  => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_centers');
    }
};
