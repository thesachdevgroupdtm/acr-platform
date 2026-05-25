<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Phase 4.5d — seed the 6 FAQs that previously lived hardcoded in
 * src/components/HomeFAQ.tsx (HOME_FAQS array). Content copied
 * verbatim so the customer page reads identically after a future
 * migration to API-backed FAQs.
 *
 * Run via:  php artisan db:seed --class=FaqSeeder
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Is my manufacturer warranty valid if I service here?',
                'answer'   => "Absolutely. We use 100% Genuine OEM parts and manufacturer-approved synthetic oils, keeping your factory warranty fully intact under the 'Right to Repair' guidelines. Detailed service records are added to your vehicle's warranty book on every visit.",
            ],
            [
                'question' => 'Do you offer pickup and drop-off service?',
                'answer'   => 'Yes — complimentary pickup and drop-off across Delhi NCR. Our team collects your car from your home or office, services it at one of our four centers, and returns it sanitized. Routine services are typically same-day.',
            ],
            [
                'question' => 'How do you handle insurance claims?',
                'answer'   => 'We coordinate cashless claims directly with all major insurance providers. Our team handles the paperwork, surveyor coordination, and approvals end-to-end. Most claims are processed within 4 to 7 working days.',
            ],
            [
                'question' => 'Are your prices transparent?',
                'answer'   => 'Every estimate is itemized — labour, parts, and taxes shown separately. You approve before any work begins. No hidden charges, no surprise bills. The final invoice matches the quoted estimate exactly.',
            ],
            [
                'question' => 'What brands do you service?',
                'answer'   => 'All major brands — Maruti Suzuki, Hyundai, Honda, Toyota, Tata, Mahindra, Kia — plus premium marques including BMW, Mercedes-Benz, Audi, Volvo, Jeep, and Land Rover. Our technicians are certified for multi-brand expertise.',
            ],
            [
                'question' => 'How long does a typical service take?',
                'answer'   => 'Routine work like an oil change or battery replacement: 2 to 3 hours. A general service: same day. Major repairs or full detailing: 1 to 3 days depending on scope. We share an accurate timeline with the estimate.',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer'     => $faq['answer'],
                    'sort_order' => $index,
                    'is_active'  => true,
                ]
            );
        }
    }
}
