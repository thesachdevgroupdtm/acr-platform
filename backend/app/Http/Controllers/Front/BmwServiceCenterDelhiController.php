<?php

namespace App\Http\Controllers\Front;

use App\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constant;

class BmwServiceCenterDelhiController extends MainController
{
    public function index()
    {
        $return_data = [];

        // Global Settings
        $return_data['settings'] = $this->data;
        $return_data['site_title'] = 'BMW Service Center Delhi';

        // Service Categories
        $return_data['scategories'] = ServiceCategory::select(
                'id', 'slug', 'title', 'image', 'icon_image', 'description'
            )
            ->where([
                ['is_archive', Constant::NOT_ARCHIVE],
                ['status', Constant::ACTIVE]
            ])
            ->orderBy('order_by', 'asc')
            ->get();

        // =========================
        // ✅ SEO META
        // =========================
        $return_data['meta_title'] = 'BMW Service Center in Delhi | Expert BMW Repair & Maintenance';
        $return_data['meta_description'] = 'Looking for expert BMW service in Delhi? Get professional repair, maintenance & detailing with 200+ happy customers. Book now!';
        $return_data['meta_keywords'] = 'BMW service Delhi, BMW repair Delhi, BMW maintenance, BMW workshop Delhi, car service center Delhi, BMW specialist Delhi';

        // =========================
        // ✅ OPEN GRAPH (for social sharing)
        // =========================
        $return_data['og_title'] = $return_data['meta_title'];
        $return_data['og_description'] = $return_data['meta_description'];
        $return_data['og_url'] = url('/bmw-service-center-delhi');
        $return_data['og_image'] = asset('front/images/resources/BMW-png.png');
        $return_data['og_type'] = 'website';
        $return_data['og_site_name'] = 'Auto Car Repair';
        
        // Also keep the array format for backward compatibility
        $return_data['og'] = [
            'type' => 'website',
            'title' => $return_data['meta_title'],
            'description' => $return_data['meta_description'],
            'url' => url('/bmw-service-center-delhi'),
            'image' => asset('front/images/resources/BMW-png.png'),
            'site_name' => 'Auto Car Repair',
            'locale' => 'en_IN'
        ];

        // =========================
        // ✅ TWITTER CARD
        // =========================
        $return_data['twitter_card'] = 'summary_large_image';
        $return_data['twitter_title'] = $return_data['meta_title'];
        $return_data['twitter_description'] = $return_data['meta_description'];
        $return_data['twitter_image'] = asset('front/images/resources/BMW-png.png');
        $return_data['twitter_site'] = '@Auto_carrepair';
        $return_data['twitter_creator'] = '@Auto_carrepair';
        
        // Keep array format
        $return_data['twitter'] = [
            'card' => 'summary_large_image',
            'title' => $return_data['meta_title'],
            'description' => $return_data['meta_description'],
            'image' => asset('front/images/resources/BMW-png.png'),
            'site' => '@Auto_carrepair',
            'creator' => '@Auto_carrepair'
        ];

        // =========================
        // ✅ FULL SCHEMA (UPDATED)
        // =========================
        $schema = [
            "@context" => "https://schema.org",
            "@type" => ["AutoRepair", "LocalBusiness"],
            "name" => "BMW Service Center Delhi",
            "image" => asset('front/images/resources/BMW-png.png'),
            "@id" => url('/bmw-service-center-delhi'),
            "url" => url('/bmw-service-center-delhi'),
            "telephone" => "+91-9870400861",
            "priceRange" => "₹50000",

            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "59, Najafgarh Rd Industrial Area, Rama Rd",
                "addressLocality" => "New Delhi",
                "addressRegion" => "Delhi",
                "postalCode" => "110015",
                "addressCountry" => "IN"
            ],

            "geo" => [
                "@type" => "GeoCoordinates",
                "latitude" => "28.6510",
                "longitude" => "77.1420"
            ],

            "openingHoursSpecification" => [[
                "@type" => "OpeningHoursSpecification",
                "dayOfWeek" => ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
                "opens" => "08:00",
                "closes" => "20:00"
            ]],

            "sameAs" => [
                "https://www.facebook.com/ACRautocarrepair",
                "https://www.instagram.com/autocarrepair_/",
                "https://share.google/oRB26bd8oAPskQosq"
            ],

            "brand" => [
                "@type" => "Brand",
                "name" => "BMW"
            ],

            "serviceArea" => [
                "@type" => "Place",
                "name" => "Delhi, Noida, Gurgaon, Okhla"
            ],

            "paymentAccepted" => ["Cash","Credit Card","Debit Card","UPI","Net Banking"],

            "aggregateRating" => [
                "@type" => "AggregateRating",
                "ratingValue" => "4.5",
                "reviewCount" => "200"
            ],

            "review" => [[
                "@type" => "Review",
                "author" => [
                    "@type" => "Person",
                    "name" => "Rahul Sharma"
                ],
                "reviewRating" => [
                    "@type" => "Rating",
                    "ratingValue" => "5"
                ],
                "reviewBody" => "Good work by ACR Moti Nagar Team, very friendly team, experienced staff and polite behavior."
            ]],

            "makesOffer" => [
                "@type" => "Offer",
                "itemOffered" => [
                    "@type" => "Service",
                    "name" => "BMW Car Service",
                    "serviceType" => "BMW Repair, Maintenance & Diagnostics"
                ]
            ],

            "hasOfferCatalog" => [
                "@type" => "OfferCatalog",
                "name" => "BMW Car Services",
                "itemListElement" => [
                    [
                        "@type" => "Offer",
                        "name" => "BMW Periodic Service",
                        "itemOffered" => [
                            "@type" => "Service",
                            "name" => "BMW Periodic Maintenance Service"
                        ]
                    ],
                    [
                        "@type" => "Offer",
                        "name" => "BMW Engine Repair",
                        "itemOffered" => [
                            "@type" => "Service",
                            "name" => "BMW Engine Diagnostics & Repair"
                        ]
                    ],
                    [
                        "@type" => "Offer",
                        "name" => "BMW AC Repair",
                        "itemOffered" => [
                            "@type" => "Service",
                            "name" => "BMW Air Conditioning Service"
                        ]
                    ],
                    [
                        "@type" => "Offer",
                        "name" => "BMW Brake Service",
                        "itemOffered" => [
                            "@type" => "Service",
                            "name" => "BMW Brake Inspection & Replacement"
                        ]
                    ],
                    [
                        "@type" => "Offer",
                        "name" => "BMW Denting & Painting",
                        "itemOffered" => [
                            "@type" => "Service",
                            "name" => "BMW Body Repair & Painting"
                        ]
                    ],
                    [
                        "@type" => "Offer",
                        "name" => "BMW Car Detailing",
                        "itemOffered" => [
                            "@type" => "Service",
                            "name" => "BMW Interior & Exterior Detailing"
                        ]
                    ],
                    [
                        "@type" => "Offer",
                        "name" => "BMW Battery Replacement",
                        "itemOffered" => [
                            "@type" => "Service",
                            "name" => "BMW Battery Check & Replacement"
                        ]
                    ]
                ]
            ]
        ];

        $return_data['schema'] = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return view(
            'front.bmw-service-center-delhi.index',
            array_merge($this->data, $return_data)
        );
    }
}