@extends('front.layout.main')

@section('content')

<!-- Google Conversion Tracking -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11126859496"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'AW-11126859496');

  // All your conversions
  gtag('event', 'conversion', {'send_to': 'AW-11126859496/rYR_CLSk7LUZEOjN2bkp'});
  gtag('event', 'conversion', {'send_to': 'AW-11126859496/fU5yCJvzwbUZEOjN2bkp'});
  gtag('event', 'conversion', {'send_to': 'AW-11126859496/4YfLCIXDh7cZEOjN2bkp'});
</script>

<style>
    /* Container with background image and overlay */
    .thank-you-container {
        min-height: 70vh;
        background-image: url('https://autocarrepair.in/uploads/content/121747806127.webp');
        background-size: cover;
        background-position: center;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
        position: relative;
    }
    .thank-you-container::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 0;
    }

    /* Content box */
    .thank-you-box {
        position: relative;
        background: #fff;
        max-width: 600px;
        width: 100%;
        padding: 40px 30px;
        border-radius: 15px;
        box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        text-align: center;
        z-index: 1;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .thank-you-box h1 {
        font-size: 42px;
        color: #222;
        margin-bottom: 20px;
        font-weight: 700;
    }
    .thank-you-box p {
        font-size: 18px;
        color: #555;
        margin-bottom: 30px;
        line-height: 1.5;
    }

    .thank-you-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .thank-you-buttons a,
    .thank-you-buttons button {
        background-color: var(--karoons-base, #E23B33);
        color: white;
        padding: 14px 36px;
        border: none;
        border-radius: 30px;
        font-size: 16px;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.3s ease;
        display: inline-block;
        font-weight: 600;
        min-width: 140px;
    }
    .thank-you-buttons a:hover,
    .thank-you-buttons button:hover {
        background-color: #c7322b;
    }

    /* Responsive adjustments */
    @media (max-width: 480px) {
        .thank-you-box {
            padding: 30px 20px;
        }
        .thank-you-box h1 {
            font-size: 32px;
        }
        .thank-you-box p {
            font-size: 16px;
        }
        .thank-you-buttons a,
        .thank-you-buttons button {
            min-width: 120px;
            padding: 12px 24px;
            font-size: 14px;
        }
    }
</style>

<div class="thank-you-container">
    <div class="thank-you-box">
        <h1>Thank You for Reaching Out!</h1>
        <p>Our executive will contact you shortly. Meanwhile, feel free to explore our latest blogs and learn more about car care, maintenance tips, and exclusive offers.</p>
        <div class="thank-you-buttons">
            <button onclick="location.href='{{ route('front_/') }}'">Home Page</button>
            <a href="{{ url('/blog') }}" target="_blank" rel="noopener">Explore Blogs</a>
        </div>
    </div>
</div>

@endsection
