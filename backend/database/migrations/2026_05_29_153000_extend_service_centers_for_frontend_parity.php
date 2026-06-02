<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * B5-partial — extend service_centers with the 5 fields the frontend
 * was reading from src/data/businessData.ts LOCATIONS:
 *   - rating          (decimal 2,1) — display rating, e.g. 4.9
 *   - reviews_count   (string)      — display string with comma formatting, e.g. "1,250"
 *   - features        (json)        — array of short capability tags
 *   - image           (string)      — hero/card image URL
 *   - google_maps_url (string)      — external maps deep link
 *
 * All nullable + guarded by Schema::hasColumn so the migration is
 * re-runnable on environments that already have any of them. After
 * the schema change we seed the 5 new columns on the 4 existing rows
 * (moti-nagar / gurugram / noida / okhla) using the verbatim TS data
 * so the frontend renders identically post-migration — zero data loss
 * per D-B5-7. The seed is UPDATE-only and matches by slug, so it's
 * safe to re-run.
 *
 * No columns are renamed or dropped. Existing data on the 14 prior
 * columns is untouched.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_centers', function (Blueprint $table) {
            if (! Schema::hasColumn('service_centers', 'rating')) {
                $table->decimal('rating', 2, 1)->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('service_centers', 'reviews_count')) {
                $table->string('reviews_count', 16)->nullable()->after('rating');
            }
            if (! Schema::hasColumn('service_centers', 'features')) {
                $table->json('features')->nullable()->after('reviews_count');
            }
            if (! Schema::hasColumn('service_centers', 'image')) {
                $table->string('image', 500)->nullable()->after('features');
            }
            if (! Schema::hasColumn('service_centers', 'google_maps_url')) {
                $table->string('google_maps_url', 500)->nullable()->after('image');
            }
        });

        // Seed the 4 existing rows from the legacy businessData.ts LOCATIONS
        // constant. Match by slug (the existing rows have the same slugs the
        // TS used as their `id` field). Idempotent: re-running this migration
        // will harmlessly re-update with the same values.
        $seed = [
            'moti-nagar' => [
                'rating'         => 4.9,
                'reviews_count'  => '1,250',
                'features'       => ['Collision Repair', 'Mechanical Service', 'Cashless Insurance'],
                'image'          => 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&q=80&w=1200',
                'google_maps_url' => 'https://maps.app.goo.gl/moti-nagar',
            ],
            'gurugram' => [
                'rating'         => 4.8,
                'reviews_count'  => '980',
                'features'       => ['Luxury Car Service', 'Detailing', 'Paint Protection'],
                'image'          => 'https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&q=80&w=1200',
                'google_maps_url' => 'https://maps.app.goo.gl/gurugram',
            ],
            'noida' => [
                'rating'         => 4.9,
                'reviews_count'  => '1,100',
                'features'       => ['Body Shop', 'AC Repair', 'Wheel Alignment'],
                'image'          => 'https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&q=80&w=1200',
                'google_maps_url' => 'https://maps.app.goo.gl/noida',
            ],
            'okhla' => [
                'rating'         => 4.7,
                'reviews_count'  => '850',
                'features'       => ['Express Service', 'Genuine Parts', 'Fleet Maintenance'],
                'image'          => 'https://images.unsplash.com/photo-1517524206127-48bbd363f3d7?auto=format&fit=crop&q=80&w=1200',
                'google_maps_url' => 'https://maps.app.goo.gl/okhla',
            ],
        ];

        foreach ($seed as $slug => $cols) {
            $cols['features'] = json_encode($cols['features']);
            DB::table('service_centers')
                ->where('slug', $slug)
                ->update($cols);
        }
    }

    public function down(): void
    {
        Schema::table('service_centers', function (Blueprint $table) {
            $cols = ['google_maps_url', 'image', 'features', 'reviews_count', 'rating'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('service_centers', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
