<?php

namespace Database\Seeders;

use App\Models\SeoPage;
use Illuminate\Database\Seeder;

/**
 * Phase 4.5b — sample SEO pages.
 *
 * Idempotent (`updateOrCreate` keyed by slug). Seeds 4 realistic
 * brand/city/maintenance pages so /explore has content to render
 * and the integration tests have known fixtures to query against.
 *
 * Each page also gets a SEO record with meta_title /
 * meta_description / og_* / schema_type — exercises the full
 * polymorphic SEO surface end-to-end.
 */
class SeoPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug'     => 'audi-service-delhi',
                'title'    => 'Audi Service in Delhi — Authorized Multi-Brand Workshop',
                'excerpt'  => 'Comprehensive Audi service in Delhi NCR by ACR-trained technicians. Diagnostics, periodic service, and warranty-friendly repairs.',
                'category' => 'Brand Service',
                'tags'     => ['audi', 'delhi', 'service'],
                'cta_title'       => 'Book Your Audi Service Today',
                'cta_button_text' => 'Book Now',
                'cta_button_url'  => '/services',
                'body' => <<<'HTML'
<p>Looking for a trusted <strong>Audi service center in Delhi</strong>? ACR Mechanics offers factory-grade diagnostics, periodic maintenance, and accident repairs for the entire Audi range.</p>
<h2>What We Service</h2>
<ul><li>A3, A4, A6, A8 sedan range</li><li>Q3, Q5, Q7, Q8 SUV range</li><li>RS performance models</li></ul>
<h3>Why ACR for Audi</h3>
<p>OEM diagnostics tools, genuine spare parts, and warranty-compatible service records.</p>
HTML,
                'seo' => [
                    'meta_title'       => 'Audi Service in Delhi | ACR',
                    'meta_description' => 'Authorized Audi service in Delhi NCR. Periodic, diagnostics, and warranty-friendly repairs by trained technicians. Book online.',
                    'og_title'         => 'Audi Service in Delhi',
                    'og_description'   => 'Trained Audi technicians, OEM tools, warranty-friendly service.',
                    'og_image'         => 'https://acr-mechanics.in/og-audi.jpg',
                    'schema_type'      => 'Article',
                    'priority'         => 0.8,
                    'changefreq'       => 'monthly',
                ],
            ],
            [
                'slug'     => 'bmw-service-cost-guide',
                'title'    => 'BMW Service Cost Guide — Delhi NCR',
                'excerpt'  => 'Transparent BMW service pricing for periodic maintenance, brake jobs, and battery replacement in Delhi NCR.',
                'category' => 'Brand Service',
                'tags'     => ['bmw', 'pricing', 'delhi'],
                'cta_title'       => 'Get a BMW Service Quote',
                'cta_button_text' => 'Request Quote',
                'cta_button_url'  => '/contact',
                'body' => <<<'HTML'
<p><strong>BMW service costs in Delhi NCR</strong> vary by model, mileage, and service tier. ACR offers transparent line-item pricing with no surprise add-ons.</p>
<h2>Typical Service Tiers</h2>
<ul><li>Minor service: ₹6,000 – ₹12,000</li><li>Major service: ₹15,000 – ₹35,000</li><li>Brake job (axle pair): ₹12,000 – ₹25,000</li></ul>
<p>All quotes include OEM filters and synthetic oil where applicable.</p>
HTML,
                'seo' => [
                    'meta_title'       => 'BMW Service Cost Guide | ACR Delhi NCR',
                    'meta_description' => 'Transparent BMW service prices for Delhi NCR. Minor, major, brake, and battery service costs explained.',
                    'og_image'         => 'https://acr-mechanics.in/og-bmw.jpg',
                    'schema_type'      => 'Article',
                    'priority'         => 0.7,
                    'changefreq'       => 'monthly',
                ],
            ],
            [
                'slug'     => 'monsoon-car-care-tips',
                'title'    => 'Monsoon Car Care — 7 Tips From Our Master Technicians',
                'excerpt'  => 'Protect your car during the Delhi NCR monsoon. Tyres, brakes, electrical, and AC checks every owner should do before the rains.',
                'category' => 'Maintenance Tips',
                'tags'     => ['monsoon', 'maintenance', 'tyres'],
                'cta_title'       => 'Schedule a Pre-Monsoon Checkup',
                'cta_button_text' => 'Schedule Now',
                'cta_button_url'  => '/services',
                'body' => <<<'HTML'
<p>The Delhi NCR <strong>monsoon</strong> stresses brakes, electricals, and tyres harder than any other season. A 30-minute checkup before the rains saves thousands later.</p>
<h2>The 7 Essentials</h2>
<ol><li>Tyre tread depth and pressure</li><li>Brake pad thickness</li><li>Wiper blade condition</li><li>Battery terminal cleaning</li><li>AC drain inspection</li><li>Underbody rust check</li><li>Headlight aim and fogging</li></ol>
HTML,
                'seo' => [
                    'meta_title'       => 'Monsoon Car Care Tips | ACR Delhi NCR',
                    'meta_description' => '7 essential pre-monsoon car care checks for Delhi NCR drivers. Brakes, tyres, electrical, AC.',
                    'og_image'         => 'https://acr-mechanics.in/og-monsoon.jpg',
                    'schema_type'      => 'Article',
                    'priority'         => 0.6,
                    'changefreq'       => 'yearly',
                ],
            ],
            [
                'slug'     => 'best-car-ac-service-gurugram',
                'title'    => 'Best Car AC Service in Gurugram — ACR Mechanics',
                'excerpt'  => 'AC gas top-up, leak detection, compressor service, and odour removal for all car brands in Gurugram.',
                'category' => 'City Service',
                'tags'     => ['gurugram', 'ac', 'service'],
                'cta_title'       => 'Book AC Service in Gurugram',
                'cta_button_text' => 'Book Now',
                'cta_button_url'  => '/services',
                'body' => <<<'HTML'
<p>ACR's <strong>Gurugram center</strong> handles AC gas top-up, leak detection, and full compressor service for all major brands.</p>
<h2>What's Included</h2>
<ul><li>Vacuum + gas refill</li><li>UV dye leak detection</li><li>Cabin filter replacement</li><li>Odour treatment</li></ul>
<h3>Turnaround</h3>
<p>Same-day service for gas top-up; 48-hour turnaround for compressor work with OEM parts.</p>
HTML,
                'seo' => [
                    'meta_title'       => 'Best Car AC Service in Gurugram | ACR',
                    'meta_description' => 'Car AC gas top-up, leak detection, and compressor service in Gurugram. Same-day for top-up, 48hr for major work.',
                    'og_image'         => 'https://acr-mechanics.in/og-ac.jpg',
                    'schema_type'      => 'Article',
                    'priority'         => 0.7,
                    'changefreq'       => 'monthly',
                ],
            ],
        ];

        foreach ($pages as $row) {
            $seoData = $row['seo'];
            unset($row['seo']);

            $page = SeoPage::updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'is_published' => true,
                    'published_at' => $row['published_at'] ?? now(),
                ])
            );

            $page->setSeoData($seoData);
        }
    }
}
