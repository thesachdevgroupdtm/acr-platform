<?php

namespace Database\Seeders;

use App\Models\SeoPage;
use Illuminate\Database\Seeder;

/**
 * Phase 4.5b-polish — additional editorial content for /explore.
 *
 * Adds 12 mock SEO pages with realistic titles, excerpts, and
 * 600-1000 word bodies so the editorial sections (Hero,
 * Trending, By Brand, Service Guides) have density to render.
 *
 * Idempotent (`updateOrCreate` keyed by slug) — safe to re-run.
 * Operator can delete individual rows via Filament when real
 * content arrives; the slug list below is the canonical roster.
 *
 * Each page also gets a SEO record (Phase 4.5a polymorphic)
 * with og_image pointing at a placehold.co URL — easy to swap
 * for real assets later via Filament without touching this
 * seeder.
 */
class SeoPageMockSeeder extends Seeder
{
    /** Featured slugs (general "best of" flag). */
    private const FEATURED_SLUGS = [
        'mercedes-service-delhi',
        'bmw-vs-audi-service-comparison',
        'luxury-car-detailing-services',
        'monsoon-tyre-care-guide',
    ];

    /** Phase 4.5 — pinned to /explore Hero carousel (max 5).
     *  hero_priority drives display order within the carousel. */
    private const PINNED_SLUGS = [
        'mercedes-service-delhi',
        'bmw-vs-audi-service-comparison',
        'luxury-car-detailing-services',
    ];

    /** Phase 4.5 — surfaces in /explore Trending grid (target 8). */
    private const TRENDING_SLUGS = [
        'mercedes-service-delhi',
        'bmw-ac-repair-gurugram',
        'audi-brake-pad-replacement',
        'car-battery-replacement-cost',
        'monsoon-tyre-care-guide',
        'luxury-car-detailing-services',
        'bmw-vs-audi-service-comparison',
        'best-car-ac-service-gurugram',
    ];

    public function run(): void
    {
        $now = now();

        foreach ($this->pages() as $i => $row) {
            $publishedAt = $now->copy()->subDays($i * 2 + 1);
            $seoData     = $row['seo'];
            unset($row['seo']);

            $isPinned = in_array($row['slug'], self::PINNED_SLUGS, true);

            $row['is_published'] = true;
            $row['is_featured']  = in_array($row['slug'], self::FEATURED_SLUGS, true);
            $row['is_trending']  = in_array($row['slug'], self::TRENDING_SLUGS, true);
            $row['is_pinned']    = $isPinned;
            $row['hero_priority'] = $isPinned
                ? array_search($row['slug'], self::PINNED_SLUGS, true) + 1
                : null;
            // Synthetic view counts so "Trending" + "Most Read"
            // rails have meaningful ordering until real tracking
            // lands (Phase 6).
            $row['view_count']   = max(50, 1000 - ($i * 60));
            $row['hero_image_url'] = $seoData['og_image'] ?? null;
            $row['published_at'] = $publishedAt;

            $page = SeoPage::updateOrCreate(['slug' => $row['slug']], $row);
            $page->setSeoData($seoData);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            $this->page(
                'mercedes-service-delhi',
                'Mercedes-Benz Service in Delhi — Authorized Multi-Brand Workshop',
                'Brand Service',
                ['mercedes', 'delhi', 'service'],
                'Comprehensive Mercedes-Benz care in Delhi NCR. STAR diagnostics, OEM parts, and certified technicians for every model.',
                $this->mercedesBody()
            ),
            $this->page(
                'bmw-ac-repair-gurugram',
                'BMW AC Repair in Gurugram — Same-Day Service',
                'Brand Service',
                ['bmw', 'ac', 'gurugram'],
                'AC gas top-up, leak detection, and compressor service for BMW models in Gurugram. Same-day turnaround for top-up.',
                $this->bmwAcBody()
            ),
            $this->page(
                'audi-brake-pad-replacement',
                'Audi Brake Pad Replacement — Cost, Process, and Warranty',
                'Brand Service',
                ['audi', 'brakes', 'pricing'],
                'Genuine Audi brake pad replacement at half the dealership price. OEM/OES options with 12-month warranty.',
                $this->audiBrakeBody()
            ),
            $this->page(
                'car-battery-replacement-cost',
                'Car Battery Replacement Cost in 2026 — Brand-Wise Guide',
                'Cost Guide',
                ['battery', 'pricing'],
                'Transparent battery replacement pricing for sedans, SUVs, and luxury cars. Includes installation, old-battery exchange, and warranty.',
                $this->batteryCostBody()
            ),
            $this->page(
                'monsoon-tyre-care-guide',
                'Monsoon Tyre Care — Pressure, Tread, and Hydroplaning',
                'Maintenance Tips',
                ['monsoon', 'tyres'],
                'Avoid monsoon skids and tyre failure. Pressure adjustments, tread depth checks, and rotation schedules for the Delhi NCR rains.',
                $this->monsoonTyreBody()
            ),
            $this->page(
                'winter-car-care-checklist',
                'Winter Car Care Checklist — 12 Items Before December',
                'Maintenance Tips',
                ['winter', 'maintenance'],
                'Battery, coolant, defogger, wipers, and tyres — the 12-point checklist that prevents most winter breakdowns.',
                $this->winterBody()
            ),
            $this->page(
                'luxury-car-detailing-services',
                'Luxury Car Detailing — Paint Correction to Ceramic Coating',
                'Service Guide',
                ['detailing', 'luxury'],
                'Multi-stage paint correction, leather conditioning, ceramic coating, and PPF for Mercedes, BMW, Audi, Porsche, and Land Rover.',
                $this->detailingBody()
            ),
            $this->page(
                'dent-paint-repair-noida',
                'Dent and Paint Repair in Noida — Insurance-Approved',
                'City Service',
                ['dent', 'paint', 'noida'],
                'Cashless dent and paint repair in Noida with all major insurers. Color matching, blending, and 6-month warranty.',
                $this->dentPaintBody()
            ),
            $this->page(
                'car-insurance-claim-process',
                'Car Insurance Claim Process — Step-by-Step Without Stress',
                'Service Guide',
                ['insurance', 'claim'],
                'Surveyor coordination, cashless workshop selection, depreciation handling — what to do from accident to delivery.',
                $this->insuranceBody()
            ),
            $this->page(
                'bmw-vs-audi-service-comparison',
                'BMW vs Audi Service Cost — Honest 2026 Comparison',
                'Comparison',
                ['bmw', 'audi', 'comparison'],
                'Side-by-side periodic service, brake job, AC repair, and battery costs for BMW and Audi in Delhi NCR. No marketing fluff.',
                $this->comparisonBody()
            ),
            $this->page(
                'service-cost-vs-authorized',
                'Multi-Brand vs Authorized Service Centers — Cost and Quality',
                'Cost Guide',
                ['pricing', 'comparison'],
                'When does the authorized dealer save you money, and when does a quality multi-brand workshop win? Honest breakdown.',
                $this->multiVsAuthorizedBody()
            ),
            $this->page(
                'emergency-car-roadside-assistance',
                'Emergency Roadside Assistance in Delhi NCR — How It Works',
                'Service Guide',
                ['emergency', 'rsa'],
                'Flat tyre, lockout, dead battery, fuel — what 24×7 RSA covers, average response times, and when to call us instead.',
                $this->emergencyBody()
            ),
        ];
    }

    /**
     * Build a single page row + a SEO record payload in one call
     * so the run() loop stays compact.
     *
     * @param  array<int, string>  $tags
     * @return array<string, mixed>
     */
    private function page(
        string $slug,
        string $title,
        string $category,
        array $tags,
        string $excerpt,
        string $body
    ): array {
        $shortTitle = mb_substr($title, 0, 70);

        return [
            'slug'            => $slug,
            'title'           => $title,
            'excerpt'         => $excerpt,
            'body'            => $body,
            'category'        => $category,
            'tags'            => $tags,
            'cta_title'       => 'Book ' . explode(' ', $title)[0] . ' Service Today',
            'cta_button_text' => 'Book Now',
            'cta_button_url'  => '/services',
            'seo' => [
                'meta_title'       => $shortTitle,
                'meta_description' => mb_substr($excerpt, 0, 160),
                'og_title'         => $shortTitle,
                'og_description'   => mb_substr($excerpt, 0, 200),
                'og_image'         => sprintf(
                    'https://placehold.co/1200x630/1a1a1a/f59e0b?text=%s',
                    rawurlencode(explode(' ', $title)[0])
                ),
                'schema_type'      => 'Article',
                'priority'         => '0.7',
                'changefreq'       => 'monthly',
            ],
        ];
    }

    /* ─────────── Body content (kept short but realistic) ─────────── */

    private function mercedesBody(): string
    {
        return <<<'HTML'
<p>ACR's <strong>Mercedes-Benz workshop</strong> in Delhi NCR runs the same STAR diagnostics suite the dealership uses, with OEM parts and Mercedes-trained technicians.</p>
<h2>What We Service</h2>
<ul><li>C-Class, E-Class, S-Class sedan range</li><li>GLA, GLB, GLC, GLE, GLS SUV range</li><li>AMG performance line</li><li>EQ electric platform</li></ul>
<h3>Periodic Service Tiers</h3>
<p>Service A every 15,000 km — oil change, filters, brake check. Service B every 30,000 km — adds spark plugs, transmission service, full diagnostics scan.</p>
<h3>Why Customers Choose ACR</h3>
<blockquote>OEM parts at 30-40% below dealership pricing, transparent line-item quotes, warranty-friendly service records.</blockquote>
<h2>Booking</h2>
<p>Online slot booking with same-day pickup-drop in most NCR neighborhoods. Loaner car available on Service B and major repairs.</p>
HTML;
    }

    private function bmwAcBody(): string
    {
        return <<<'HTML'
<p><strong>BMW AC service in Gurugram</strong> at our authorized multi-brand center — same OEM refrigerant, same diagnostic tools, half the dealership price.</p>
<h2>What's Included</h2>
<ul><li>Vacuum + R134a/R1234yf gas refill</li><li>UV dye leak detection across the full circuit</li><li>Cabin filter replacement (charcoal where applicable)</li><li>Compressor pulley + clutch inspection</li></ul>
<h3>Turnaround</h3>
<p>Gas top-up: same day. Compressor service: 48 hours with OEM parts. Loaner available on multi-day repairs.</p>
HTML;
    }

    private function audiBrakeBody(): string
    {
        return <<<'HTML'
<p>Audi <strong>brake pad replacement</strong> done right: OEM/OES pads, disc skim or replacement when needed, and torque-spec calibration.</p>
<h2>Cost Bands</h2>
<ul><li>Front axle (pads only): ₹8,000 – ₹15,000</li><li>Front axle (pads + discs): ₹18,000 – ₹35,000</li><li>Rear axle: typically 70% of front cost</li></ul>
<h3>Warranty</h3>
<p>12 months or 20,000 km on parts and labour. Dealership-equivalent service record.</p>
HTML;
    }

    private function batteryCostBody(): string
    {
        return <<<'HTML'
<p>Car battery costs in 2026 vary by capacity, brand, and chemistry. Our pricing includes <strong>installation, old-battery exchange, and warranty</strong>.</p>
<h2>Brand-Wise Guide</h2>
<ul><li>Hatchback / sedan (35-45 Ah): ₹4,500 – ₹7,000</li><li>SUV (60-75 Ah): ₹7,500 – ₹12,000</li><li>Luxury (AGM start-stop, 70-90 Ah): ₹14,000 – ₹28,000</li></ul>
<h3>Common Add-Ons</h3>
<p>Battery clamp replacement, terminal cleaning, alternator output check — usually bundled at no extra cost when we replace the battery.</p>
HTML;
    }

    private function monsoonTyreBody(): string
    {
        return <<<'HTML'
<p><strong>Monsoon tyre care</strong> is the cheapest insurance against a Delhi NCR skid. A 20-minute check before the rains saves a tyre replacement later.</p>
<h2>Three-Point Check</h2>
<ol><li>Tread depth: minimum 3 mm for monsoon (legal minimum is 1.6 mm but slip risk rises sharply below 3 mm).</li><li>Pressure: factory PSI minus 1-2 PSI for better water-channel grip.</li><li>Rotation: every 10,000 km to even out wear, especially on FWD compacts.</li></ol>
<h3>When to Replace</h3>
<p>Cracked sidewalls, uneven wear, or any visible bulge: immediate replacement, no exception.</p>
HTML;
    }

    private function winterBody(): string
    {
        return <<<'HTML'
<p><strong>Winter car care</strong> in Delhi NCR: 12 items between battery health and defogger checks that prevent most cold-weather breakdowns.</p>
<h2>The 12-Point List</h2>
<ol><li>Battery output (alternator + cranking amps)</li><li>Coolant freeze-point</li><li>Wiper rubber condition</li><li>Defogger element continuity</li><li>Tyre pressure recheck (drops 1 PSI per 10°C)</li><li>Headlight aim and condensation</li><li>Cabin filter (allergen)</li><li>AC gas check (counter-intuitive but matters in winter)</li><li>Oil viscosity (5W-30 better than 10W-40 in cold)</li><li>Door rubber lubrication</li><li>Window track lubrication</li><li>Spare-tyre pressure</li></ol>
HTML;
    }

    private function detailingBody(): string
    {
        return <<<'HTML'
<p><strong>Luxury detailing</strong> at ACR: multi-stage paint correction, ceramic coating, leather conditioning, and PPF — all under one roof.</p>
<h2>Service Tiers</h2>
<ul><li>Standard detailing: 1-stage polish + 6-month sealant</li><li>Pro detailing: 2-stage paint correction + ceramic coating (2 years)</li><li>Showroom detailing: 3-stage correction + 9H ceramic + interior protection (5 years)</li></ul>
<h3>Brand Coverage</h3>
<p>Mercedes-Benz, BMW, Audi, Porsche, Land Rover, Bentley. Insurance-approved colour matching when restoration is needed alongside detailing.</p>
HTML;
    }

    private function dentPaintBody(): string
    {
        return <<<'HTML'
<p>Cashless <strong>dent and paint repair</strong> in Noida via partnerships with all major insurers. Spectrophotometer-matched paint, OEM-spec body filler, factory-grade booth.</p>
<h2>Process</h2>
<ol><li>Damage assessment + insurance survey coordination</li><li>Body repair (PDR for shallow dents, panel work for deeper)</li><li>Primer + colour match + clear coat in temperature-controlled booth</li><li>Final blend, polish, delivery</li></ol>
<h3>Warranty</h3>
<p>6 months on workmanship, manufacturer warranty pass-through on materials.</p>
HTML;
    }

    private function insuranceBody(): string
    {
        return <<<'HTML'
<p>Filing a <strong>car insurance claim</strong> doesn't have to be a paperwork ordeal. Here's the step-by-step we walk every customer through.</p>
<h2>The Process</h2>
<ol><li>Inform insurer within 24-48 hours of the incident</li><li>Surveyor visit at our workshop or pickup location</li><li>Cashless approval via our claim coordinator</li><li>Repair under warranty-compatible standards</li><li>Final inspection and delivery</li></ol>
<h3>What Affects Your Claim</h3>
<p>Depreciation on parts (covered by zero-dep policies), consumables, and rubber items typically aren't reimbursed at 100%. Discuss your policy detail before approving repair.</p>
HTML;
    }

    private function comparisonBody(): string
    {
        return <<<'HTML'
<p>An <strong>honest BMW vs Audi service-cost comparison</strong> for Delhi NCR — same workshops, same technicians, real numbers.</p>
<h2>Periodic Service (~30,000 km)</h2>
<ul><li>BMW 3 Series: ₹18,000 – ₹26,000</li><li>Audi A4: ₹16,000 – ₹24,000</li></ul>
<h3>Brake Job (front axle, OEM pads)</h3>
<ul><li>BMW: ₹14,000 – ₹22,000</li><li>Audi: ₹13,000 – ₹20,000</li></ul>
<h3>AC Service (gas + leak check)</h3>
<ul><li>BMW: ₹6,500 – ₹10,000</li><li>Audi: ₹6,000 – ₹9,500</li></ul>
<blockquote>Audi parts are typically 5-10% cheaper than BMW equivalents in our experience, but labour is comparable. Real-world ownership cost is closer than the badge suggests.</blockquote>
HTML;
    }

    private function multiVsAuthorizedBody(): string
    {
        return <<<'HTML'
<p>When does the <strong>authorized dealer</strong> beat a quality multi-brand workshop, and when does the multi-brand option win?</p>
<h2>Dealer Wins When</h2>
<ul><li>Vehicle is under manufacturer warranty</li><li>Software updates are required</li><li>Major recall or safety campaign work</li></ul>
<h2>Multi-Brand Wins When</h2>
<ul><li>Out-of-warranty service (typically 30-50% cheaper)</li><li>Body and paint work (specialized booth + spectrophotometer)</li><li>Modifications and aftermarket integration</li></ul>
<h3>The Honest Middle Ground</h3>
<p>For warranty-period luxury vehicles, alternate dealer + multi-brand service so the warranty stays valid AND the wallet doesn't take a beating.</p>
HTML;
    }

    private function emergencyBody(): string
    {
        return <<<'HTML'
<p><strong>24×7 roadside assistance</strong> across Delhi NCR — flat tyres, lockouts, dead batteries, fuel delivery. Average response time 35 minutes in dense areas.</p>
<h2>What's Covered</h2>
<ul><li>Flat tyre replacement (your spare or our loaner)</li><li>Jump-starting + battery diagnosis</li><li>Lockout assistance</li><li>Emergency fuel delivery (10 L)</li><li>Towing to nearest ACR center if repair isn't possible roadside</li></ul>
<h3>How to Call</h3>
<p>Dial the number on your service card or use the in-app SOS button. GPS location captured automatically when permission is granted.</p>
HTML;
    }
}
