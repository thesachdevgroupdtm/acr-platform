<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Section;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // ── ABOUT US ─────────────────────────────────────────────
        $about = Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title'           => 'About Us',
                'seo_title'       => 'About Auto Car Repair — Multi-Brand Service Network',
                'seo_description' => "Learn about India's fastest-growing self-owned multi-brand car service network.",
                'is_active'       => true,
            ]
        );
        $about->sections()->delete();
        $about->sections()->createMany([
            [
                'type'     => 'hero',
                'position' => 1,
                'content'  => [
                    'heading' => 'About Auto Car Repair',
                    'subheading' => "India's fastest-growing self-owned multi-brand car service network.",
                ],
            ],
            [
                'type'     => 'rich_text',
                'position' => 2,
                'content'  => [
                    'html' => '<p>Auto Car Repair is fully self-owned, never outsourced. We guarantee transparent pricing, OEM parts, and dealership-quality work for every make and model.</p>',
                ],
            ],
        ]);

        // ── CONTACT ─────────────────────────────────────────────
        $contact = Page::updateOrCreate(
            ['slug' => 'contact-us'],
            [
                'title'     => 'Contact Us',
                'seo_title' => 'Contact Auto Car Repair',
                'is_active' => true,
            ]
        );
        $contact->sections()->delete();
        $contact->sections()->createMany([
            [
                'type'     => 'hero',
                'position' => 1,
                'content'  => ['heading' => 'Talk to us', 'subheading' => 'We respond within 1 working hour.'],
            ],
            [
                'type'     => 'contact_form',
                'position' => 2,
                'content'  => ['fields' => ['name', 'email', 'phone', 'message']],
            ],
        ]);

        // ── INSURANCE ───────────────────────────────────────────
        Page::updateOrCreate(
            ['slug' => 'insurance'],
            [
                'title'     => 'Insurance',
                'seo_title' => 'Cashless Insurance Claims — Auto Car Repair',
                'is_active' => true,
            ]
        );
    }
}
