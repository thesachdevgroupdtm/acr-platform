@extends('front.layout.main')
@section('content')

<head>

  <!-- Fonts -->
  <!-- Styles -->
  <link rel="stylesheet" href="https://www.galaxytoyota.in/public/css/app.css">
  <script src="https://kit.fontawesome.com/3e00a6d6cd.js" crossorigin="anonymous"></script>
  <!-- Scripts -->
  <script src="https://www.galaxytoyota.in/public/jquery/jquery.min.js"></script>
  <script src="https://www.galaxytoyota.in/public/js/app.js" defer></script>


  <style>
    .max-w-md.mx-auto.bg-white.rounded-xl.shadow-md.overflow-hidden.md\:max-w-7xl.mt-5.p-2 {
      background: white !important;
    }

    @media (min-width: 768px) {
      .md\:p-8 {
        padding: 2rem !important;
      }
    }

    .text-red-550 {
      --tw-text-opacity: 1;
      color: rgb(29 69 145);
    }

    .text-red-500 {
      --tw-text-opacity: 1;
      color: orangered;
    }

    .bg-red-600 {
      --tw-bg-opacity: 1;
      background-color: orangered;
    }

    .hover\:bg-red-700:hover {
      --tw-bg-opacity: 1;
      background-color: #ff4500c4;
    }
  </style>
  
  <!---->
  <style>
   .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5); /* Semi-transparent black */
      z-index: 999; /* Ensure the overlay is on top of everything else */
    }

    .popup-form {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%,-50%);
      background-color: #fff;
      padding: 20px;
      max-width: 600px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border-radius: 5px;
      z-index: 1000; /* Ensure the popup form is on top of the overlay */
    }

    .close-button {
      position: absolute;
      top: 5px;
      right: 5px;
      cursor: pointer;
    }

    .banner-form {
      background-color: #fff;
      padding: 20px;
      margin: 20px auto;
      max-width: 600px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border-radius: 5px;
      animation: slide-up 0.5s ease;
    }

    @keyframes slide-up {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    h1 {
      font-size: 24px;
      text-align: center;
      margin-bottom: 20px;
    }

    .msform {
      margin-top: 20px;
    }

    .col-12 {
      padding: 0 15px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .textstyl {
      width: 100%;
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      transition: border-color 0.3s;
    }

    .textstyl:focus {
      outline: none;
      border-color: #6cb2eb;
    }

    .btn {
      background-color: #6cb2eb;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn:hover {
      background-color: #4d8ac9;
    }

    .form-group.text-center {
      margin-top: 20px;
    }

    /* Responsive Styles */
    @media screen and (max-width: 768px) {
      .banner-form {
        max-width: 90%;
      }
    }
  </style>
  <!---->
  
  
</head>

<body class="font-Poppins max-w-screen-2xl mx-auto my-0 bg-white text-black">
    
    <!---->
    
    <div class="overlay" id="overlay"></div>
  <div class="popup-form" id="popupForm">
    <span class="close-button" onclick="closeForm()">X</span>
    <div class="banner-form">
      <h1>
        Avail of the Best Car Services at the Multibrand Car Service Center - Auto Car Repair
      </h1>
      <div class="msform">
        <div class="col-12">
          <form id="carEnquiryForm" action="mail.php" method="post">
            <div class="text-grp">
              <div id="step-one">
                <div class="row m-0">
                  <div class="col-md-12 col-sm-6 col-6 pl-0 pr-1 m-padding-lr">
                    <div class="form-group">
                      <input type="text" class="textstyl" placeholder="Enter Your Name" name="name" id="name1" required>
                    </div>
                    <div class="form-group">
                      <input id="" required="required" type="text" class="textstyl" name="email" placeholder="Enter Email">
                    </div>
                    <div class="form-group">
                      <input class="textstyl" required="required" type="text" name="number" placeholder="Contact Number" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,10);">
                    </div>
                    <div class="form-group">
                      <select name="inputState" id="inputState1" class="textstyl" required="" onchange="populateModels()">
                        <option value="SelectState" selected="">Popular Brands </option>
                        <option value="HONDA">HONDA</option>
                        <option value="HYUNDAI">HYUNDAI</option>
                        <option value="MARUTI">MARUTI</option>
                        <option value="TOYOTA">TOYOTA</option>
                        <option value="MAHINDRA">MAHINDRA</option>
                        <option value="RENAULT">RENAULT</option>
                        <option value="KIA">KIA</option>
                        <option value="CITROEN">CITROEN</option>
                        <option value="SKODA">SKODA</option>
                        <option value="NISSAN">NISSAN</option>
                        <option value="VOLKSWAGEN">VOLKSWAGEN</option>
                        <option value="TATA">TATA</option>
                        <option value="FORD">FORD</option>
                        <option value="MG">MG</option>
                        <option value="MERCEDES">MERCEDES BENZ</option>
                        <option value="AUDI">AUDI</option>
                        <option value="BMW">BMW</option>
                        <option value="VOLVO">VOLVO</option>
                        <option value="PORSCHE">PORSCHE</option>
                        <option value="LEXUS">LEXUS</option>
                        <option value="LANDROVER">LANDROVER</option>
                        <option value="JAGUAR">JAGUAR</option>
                        <option value="JEEP">JEEP</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <select class="textstyl" id="inputDistrict1" name="inputDistrict" required="">
                        <option value="">Select Model </option>
                      </select>
                    </div>
                    <div class="form-group">
                      <input type="submit" class="next btn submit quote-btn-bg">
                    </div>
                  </div>
                  <!-- Add your other form inputs here -->
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!--<button onclick="openForm()">Open Form</button>-->

  <script>
    const carModels = {
      HONDA: ["AMAZE","HONDA WR-V","HONDA CITY","HONDA CITY 4TH GEN","HONDA JAZZ","HONDA WR-V"],
   HYUNDAI: ["HYUNDAI SANTRO","HYUNDAI GRAND I10 NIOS","HYUNDAI AURA","HYUNDAI I20","HYUNDAI VENUE","HYUNDAI VERNA","HYUNDAI CRETA","HYUNDAI ALCAZAR","HYUNDAI ELANTRA","HYUNDAI TUCSON","HYUNDAI ELITE I20","HYUNDAI XCENT","HYUNDAI GRAND I10"],
   MARUTI: ["MARUTI ALTO","MARUTI S-PRESSO","MARUTI EECO","MARUTI WAGON R","MARUTI CELERIO","MARUTI IGNIS","MARUTI SWIFT","MARUTI DZIRE","MARUTI BALENO","MARUTI VITARA BREZZA","MARUTI ERTIGA","MARUTI S-CROSS","MARUTI CIAZ","MARUTI XL6"],
   TOYOTA: ["TOYOTA GLANZA","TOYOTA URBAN CRUISER","TOYOTA INNOVA CRYSTA","TOYOTA FORTUNER","TOYOTA CAMRY","TOYOTA VELLFIRE","TOYOTA ETIOS LIVA","TOYOTA YARIS","TOYOTA COROLLA ALTIS","TOYOTA PRIUS","TOYOTA LAND CRUISER"],
   MAHINDRA: ["MAHINDRA KUV100 NXT","MAHINDRA XUV300","MAHINDRA BOLERO","MAHINDRA BOLERO NEO","MAHINDRA EVERITO (ELECTRIC)","MAHINDRA MARAZZO","MAHINDRA XUV700","MAHINDRA SCORPIO","MAHINDRA THAR","MAHINDRA ALTURAS G4","MAHINDRA VERITO VIBE","MAHINDRA VERITO","MAHINDRA BOLERO POWER PLUS","MAHINDRA NUVOSPORT","MAHINDRA TUV300"],
   RENAULT: ["RENAULT KWID","RENAULT TRIBER","RENAULT KIGER","RENAULT DUSTER"],
   KIA: ["KIA SONET","KIA SELTOS","KIA CARNIVAL"],
   JEEP: ["JEEP COMPASS","JEEP COMPASS TRAILHAWK","JEEP WRANGLER","JEEP GRAND CHEROKEE"],
   CITROEN: ["CITROEN C3","CITROEN C5 AIRCROSS"],
   SKODA: ["SKODA RAPID","SKODA KUSHAQ","SKODA OCTAVIA","SKODA SUPERB"],
   NISSAN: ["NISSAN MICRA ACTIVE","NISSAN MICRA","NISSAN SUNNY","NISSAN TERRANO","NISSAN KICKS","NISSAN GTR","NISSAN MAGNITE"],
   VOLKSWAGEN: ["VOLKSWAGEN POLO","VOLKSWAGEN AMEO","VOLKSWAGEN VENTO","VOLKSWAGEN PASSAT","VOLKSWAGEN TIGUAN","VOLKSWAGEN VIRTUS","VOLKSWAGEN TAIGUN"],
   TATA: ["TATA TIAGO","TATA PUNCH","TATA TIGOR","TATA ALTROZ","TATA TIAGO NRG","TATA NEXON","TATA TIGOR EV","TATA HARRIER","TATA NEXON EV","TATA SAFARI","TATA NANO GENX","TATA BOLT","TATA ZEST","TATA SAFARI STORME","TATA WINGER"],
   FORD: ["FORD FIGO","FORD FREESTYLE","FORD ASPIRE","FORD ECOSPORT","FORD ENDEAVOUR","FORD MUSTANG","FORD MONDEO","FORD KUGA"],
   MG: ["MG ASTOR","MG HECTOR","MG HECTOR PLUS","MG ZS EV","MG GLOSTER"],
   MERCEDES: ["G CLASS","C CLASS","GLA","GLS","MAYBACH S CLASS","A CLASS LIMOUSINE","S CLASS","E CLASS","GLE","MAYBACH GLS","GLC","GLB","AMG GLC43 COUPE","AMG A35","GLC COUPE","EQS","AMG GLA 35","AMG E53 CABRIOLET","AMG GT4 DOOR COUPE","EQB","AMG E63","AMG A45 S","AMG GLE COUPE","AMG E53","EQC","EQS"],
   AUDI: ["A4","Q3","Q3 SPORTSBACK","A6","Q5","S5 SPORTSBACK","Q7","E TRON","Q8","RS5","E TRON SPORTSBACK","A8 L","E TRON GT","RS Q8"],
   BMW: ["IX","I4","I7","X7","X5","X3","X1","XM","M340I","M8 COMPETITION COUPE","M4 COMPETETION COUPE","Z4M40I","7 SERIES SEDAN","6 SERIES GRAN TURISMO","5 SERIES SEDAN","M340I","3 SERIES GRAN LIMOUSINE","2 SERIES GRAN COUPE"],
   VOLVO: ["XC40","XC60","XC90","XC40 RECHARGE","S90"],
   PORSCHE: ["CAYENNE","911","MACAN","PANAMERA","718","TAYCAN","CAYENNE COUPE","TAYCAN CROSS TURISMO"],
   LEXUS: ["LX","ES","NX","LC 500H","LS","RX","RX FACELIFT","UX 300E"],
   LANDROVER: ["RANGE ROVER VELAR","DEFENDER","RANGE ROVER","RANGE ROVER EVOQUE","RANGE ROVER SPORT","DISCOVERY SPORT","DISCOVERY"],
   JAGUAR: ["XF","F PACE","F TYPE","I PACE","E PACE"],
   JEEP: ["COMPASS","MEREDIAN","WRANGLER"]
      // Add other car models here
    };

    function populateModels() {
      const brandSelect = document.getElementById('inputState1');
      const modelSelect = document.getElementById('inputDistrict1');
      const selectedBrand = brandSelect.value;

      modelSelect.innerHTML = '<option value="">Select Model</option>'; // Clear previous options

      if (selectedBrand && carModels[selectedBrand]) {
        carModels[selectedBrand].forEach(model => {
          const option = document.createElement('option');
          option.value = model;
          option.textContent = model;
          modelSelect.appendChild(option);
        });
        modelSelect.disabled = false; // Enable model select
      } else {
        modelSelect.disabled = true; // Disable model select if no brand selected
      }
    }

    function openForm() {
      document.getElementById("popupForm").style.display = "block";
      document.getElementById("overlay").style.display = "block"; // Show the overlay
    }

    function closeForm() {
      document.getElementById("popupForm").style.display = "none";
      document.getElementById("overlay").style.display = "none"; // Hide the overlay
    }
  </script>
    
    <!---->
  <div class="min-h-screen bg-white text-black">
    <!-- Page Content -->
    <main>
      <div class="bg-gray-70">
        <div class="swiper mySwiper">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <img src="resources/views/front/offer/offer2024/offerbanner1.jpg" class="w-full">
            </div>
            <!-- <div class="swiper-slide">
                                    <img src="https://www.galaxytoyota.in/public/storage/1479/toyota-ac-service-offers.jpg" class="w-full">
                                </div>
                                <div class="swiper-slide">
                                    <img src="https://www.galaxytoyota.in/public/storage/1480/toyota-car-service2.jpg" class="w-full">
                                </div>
                                <div class="swiper-slide">
                                    <img src="https://www.galaxytoyota.in/public/storage/1481/toyota-car-battery-service1.jpg" class="w-full">
                                </div>
                                <div class="swiper-slide">
                                    <img src="https://www.galaxytoyota.in/public/storage/1482/toyota-car-service1.jpg" class="w-full">
                                </div> -->

          </div>
        </div>

        <div class="xl:px-32 lg:px-24 md:px-4 px-2 py-5 lg:py-10">
          <h3 class="text-lg md:text-4xl font-bold text-red-550 text-center">Service Offers</h3>
          <ul id="paginated-list" data-current-page="1" aria-live="polite">
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src="resources/views/front/offer/offer2024/1.jpeg" alt="Ac Service Camp">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> AC Duct Cleaning With Rodent Repellent Starting at Rs. 1599/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Revitalize your car's air quality! Our AC Duct Cleaning with Rodent Repellent starts at just Rs. 1599*.



                      &nbsp;</p>
                    <p> Say goodbye to stale odors and allergens, and enjoy a revitalized interior with cleaner air.

                    </p>
                    <ul>
                      <li> Trust us to create a fresh and comfortable atmosphere for your vehicle, ensuring a more enjoyable ride every time. </li>
                      <!-- <li>AC Evaporator/ Filter Cleaning</li>
                                                    <li>Compressor Flushing</li>
                                                    <li>AC Vent Cleaning</li>
                                                    <li>Radiator/ Condenser Complete Washing</li>
                                                    <li>25% Off on Any 3 Value Added Services</li> -->
                    </ul>
                    <!-- <p>The exclusive offer starts at Rs. 4,800.</p> -->
                    <p> Book your car service at Auto Car Repair (myTVS) now!

                    </p>
                    <p>*T&amp;C Apply</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (2).jpeg" alt="Ac Check up">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Wurth Anti-Rust Coating Starting at Rs. 1599/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">

                    <p>Protect your vehicle from corrosion with Wurth Anti-Rust Coating, now available starting at just Rs. 1599*.
                      &nbsp;</p>
                    <p>Our advanced formula creates a durable barrier against rust, extending the lifespan of your car and preserving its appearance.
                    </p>
                    <ul>
                      <li>Shield your car from moisture, salt, and environmental damage, ensuring long-lasting protection for your vehicle's metal surfaces.</li>
                      <li> Drive confidently knowing your car is safeguarded with Wurth Anti-Rust Coating.</li>

                    </ul>
                    <p>Book your car service at Auto Car Repair (myTVS) now!</p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src="resources/views/front/offer/offer2024/  (3).jpeg" alt="Windshield replacement (1)">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Service Under 30 Minutes - Towing Service Starting at Rs. 999/-* (Upto 5-10 Km)
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Get quick and efficient towing service in under 30 minutes, starting at just Rs. 999* for distances up to 5-10 km.&nbsp;</p>
                    <p>Whether you're stranded on the roadside or need assistance with a vehicle breakdown, our reliable towing service ensures prompt assistance to get you back on the road with minimal delay. Trust us for fast and affordable towing solutions, providing peace of mind during unexpected situations.</p>
                    <!-- <ul>
                                                    <li>Auto Car Repair MyTvs Approved Quality Windshield</li>
                                                    <li>Crystal Clear View</li>
                                                    <li>No Distortion</li>
                                                    <li>Preserves the Field of Vision Even If Damaged</li>
                                                    <li>Not Easily Penetrable</li>
                                                    <li>6 Months/ 10,000 KM Leakage Warranty</li>
                                                </ul> -->
                    <p>Book your car service at Auto Car Repair (myTVS) now!

                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (4).jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Radiator, Condensor, & Evaporator Cleaning Starting at Rs. 3499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Experience optimal cooling performance with our comprehensive Radiator, Condenser, & Evaporator Cleaning service, starting at just Rs. 3499*.
                      &nbsp;</p>
                    <p>Our skilled technicians use advanced equipment and techniques to remove dirt, debris, and contaminants, ensuring efficient heat transfer and improved air conditioning functionality.Keep your vehicle's cooling system in top condition and enjoy a comfortable ride in any weather with our affordable cleaning service.
                    </p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (5).jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Car Rubbing & Polish at Rs. 1199/-*</h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Restore your car's shine and luster with our professional Car Rubbing & Polish service, priced at just Rs. 1199*.
                      &nbsp;</p>
                    <p>Our skilled technicians meticulously remove surface imperfections and apply high-quality polish to enhance your vehicle's appearance. Say goodbye to dull paint and hello to a showroom-worthy finish.Treat your car to a makeover today and enjoy a sleek and glossy look that turns heads on the road.
                    </p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                   <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (6).jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Car Battery Replacement Onsite at Rs. 499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Convenience meets affordability with our Onsite Car Battery Replacement service, priced at just Rs. 499*.&nbsp;</p>
                    <p>No more waiting at the garage – our technicians come to you, equipped to swiftly replace your car battery. Get back on the road in no time with our reliable service, ensuring peace of mind and uninterrupted driving experience. Trust us for quick and hassle-free battery replacement right at your doorstep.</p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (7).jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Car 3M Ceramic Coating Starting at Rs. 14999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Elevate your car's protection and aesthetics with our premium Car 3M Ceramic Coating service, starting at just Rs. 14999*.
                      &nbsp;</p>
                    <p>Our advanced ceramic coating provides durable defense against scratches, UV damage, and environmental contaminants, while enhancing the gloss and depth of your vehicle's paint. Invest in long-lasting shine and preservation for your car's exterior, ensuring a stunning finish that lasts for years to come.
                    </p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (8).jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Service Under 30 Minutes - Battery Jump Starting at Rs. 499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Get back on the road in no time with our Battery Jump Starting service, completed in under 30 minutes and priced at just Rs. 499*.


                      &nbsp;</p>
                    <p> Whether you're stranded with a dead battery or need assistance in a hurry, our prompt and reliable service ensures a quick solution to get you moving again. Trust us for efficient and affordable battery jump starting, providing peace of mind during unexpected situations.</p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (9).jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> 2 Year Package - Antirust With Paint Protection Starting at Rs. 5999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Protect your car's exterior with our comprehensive 2-Year Package - Antirust With Paint Protection, starting at just Rs. 5999*. &nbsp;</p>
                    <p>Our advanced antirust treatment forms a durable barrier against corrosion, while the paint protection ensures a glossy finish that lasts. Invest in long-term preservation for your vehicle's appearance and value, safeguarding it against rust and environmental damage for years to come. Drive with confidence knowing your car is well-protected inside and out.
                    </p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/10.jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">ACR Secure Warranty Plan Starting at Rs. 4999/-*</h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Ensure peace of mind on the road with our ACR Secure Warranty Plan, starting at just Rs. 4999*. &nbsp;</p>
                    <p>Our comprehensive coverage provides protection against unexpected repairs, offering financial security and hassle-free service when you need it most. Drive confidently knowing that your vehicle is safeguarded by our reliable warranty plan, designed to keep you on the move without worrying about unforeseen expenses.
                    </p>

                    <p>Book your car warranty at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (12).jpeg" alt="General Car Service offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Car Shocker Replacement Starting at Rs. 999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Experience smoother rides with our affordable Car Shocker Replacement service, starting at just Rs. 999*.
                      &nbsp;</p>
                    <p>Say goodbye to bumpy drives and uneven handling as our skilled technicians swiftly replace your car's shockers with quality parts. Enjoy improved comfort and stability on the road, ensuring a safer and more enjoyable driving experience. Trust us for reliable and quality shocker replacements that keep your car running smoothly.
                    </p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (13).jpeg" alt="Wheel alignment">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Unlimited Car Wash Starting at Rs. 2999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Keep your car looking its best with our Unlimited Car Wash service, starting at just Rs. 2999*.


                      &nbsp;</p>
                    <ul>
                      <li> Enjoy the convenience of unlimited washes, ensuring your vehicle stays clean and pristine all year round. With our professional cleaning techniques and quality products, we'll leave your car sparkling every time. Say goodbye to dirt and grime and hello to a fresh and polished ride with our affordable and convenient car wash package.

                      </li>

                    </ul>
                    <p> Book your car service at Auto Car Repair (myTVS) now!</p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (21).jpeg" alt="Front Bumper">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Full Car Wash Starting at Rs. 599/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Give your car the attention it deserves with our Full Car Wash service, starting at just Rs. 599*.
                      &nbsp;&nbsp;</p>
                    <p>Our meticulous cleaning process ensures every inch of your vehicle is thoroughly washed, leaving it spotless and shining like new. From exterior wash to interior vacuuming, we provide comprehensive care to restore your car's beauty inside and out. Treat your car to a refreshing wash and enjoy a pristine driving experience.
                      &nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (15).jpeg" alt="Tyre Offer">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Dent/Paint Service Starting at Rs. 2199/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Restore your car's flawless appearance with our Dent/Paint Service, starting at just Rs. 2199*.
                    </p>
                    <ul>
                      <li>Our skilled technicians expertly repair dents and touch up paint imperfections, leaving your vehicle looking as good as new. Say goodbye to unsightly dings and scratches, and hello to a smooth and sleek finish. Trust us to revitalize your car's exterior and maintain its pristine condition for a stunning look on the road.
                      </li>

                    </ul>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (16).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Car Service Starting at Rs. 2499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Keep your car running smoothly with our comprehensive Car Service, starting at just Rs. 2499*.
                      &nbsp;</p>
                    <p>Our skilled technicians provide thorough inspections and maintenance, ensuring your vehicle performs at its best. From oil changes to filter replacements, we cover all essential service tasks to keep your car in top condition. Trust us to maintain your car's reliability and safety, giving you peace of mind on every journey.&nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>

                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (17).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> BMW Car Service Starting at Rs. 9999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Experience luxury service for your BMW with our premium Car Service starting at Rs. 9999*.

                      &nbsp;</p>
                    <p>Our specialized technicians provide meticulous care and attention to detail, ensuring your vehicle receives the highest quality maintenance and repairs. From routine inspections to performance enhancements, trust us to keep your BMW running at its peak performance. Drive with confidence knowing your luxury car is in expert hands with our comprehensive service package.
                      &nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>

                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (19).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Audi Car Service Starting at Rs. 9999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Experience luxury service for your Audi with our premium Car Service starting at Rs. 9999*.
                      &nbsp;</p>
                    <p>Our specialized technicians provide meticulous care and attention to detail, ensuring your vehicle receives the highest quality maintenance and repairs. From routine inspections to performance enhancements, trust us to keep your Audi running at its peak performance. Drive with confidence knowing your luxury car is in expert hands with our comprehensive service package.&nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (18).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Mercedes Car Service Starting at Rs. 9999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Experience luxury service for your Mercedes with our premium Car Service starting at Rs. 9999*.
                      &nbsp;</p>
                    <p>Our specialized technicians provide meticulous care and attention to detail, ensuring your vehicle receives the highest quality maintenance and repairs. From routine inspections to performance enhancements, trust us to keep your Mercedes running at its peak performance. Drive with confidence knowing your luxury car is in expert hands with our comprehensive service package.
                      &nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (20).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Fortuner Service Starting at Rs. 5999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Elevate your Fortuner's performance with our comprehensive service starting at just Rs. 5999*.&nbsp;</p>
                    <p>Our specialized technicians provide thorough inspections and maintenance tailored to your SUV's needs. From engine tune-ups to fluid checks, trust us to keep your Fortuner running smoothly on and off the road. Drive with confidence knowing your SUV is in expert hands with our reliable service package.

                      &nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (22).jpeg" alt="Brake Replacement">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Kia Seltos Service Starting at Rs. 4199/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Elevate your Kia Seltos’ performance with our comprehensive service starting at just Rs. 4199*.
                    </p>
                    <p>Our specialized technicians provide thorough inspections and maintenance tailored to your SUV's needs. From engine tune-ups to fluid checks, trust us to keep your Kia Seltos running smoothly on and off the road. Drive with confidence knowing your SUV is in expert hands with our reliable service package.&nbsp;</p>

                    <p>Book your car service at Auto Car Repair (myTVS) now!

                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (23).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Skoda Slavia Service Starting at Rs. 5999/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Elevate your Skoda Slavias performance with our comprehensive service starting at just Rs. 5999*.

                      &nbsp;</p>
                    <p> Our specialized technicians provide thorough inspections and maintenance tailored to your SUV's needs. From engine tune-ups to fluid checks, trust us to keep your Skoda Slavia running smoothly on and off the road. Drive with confidence knowing your SUV is in expert hands with our reliable service package.&nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!

                    </p>
                    <p>T&amp;C Apply*</p>

                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="#" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (24).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">VW Tiguan Service Starting at Rs. 6499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Elevate your VW Tiguan’s performance with our comprehensive service starting at just Rs. 6499*.


                      &nbsp;</p>
                    <p>Our specialized technicians provide thorough inspections and maintenance tailored to your SUV's needs. From engine tune-ups to fluid checks, trust us to keep your VW Tiguan running smoothly on and off the road. Drive with confidence knowing your SUV is in expert hands with our reliable service package.

                      &nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (28).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Creta Service Starting at Rs. 4499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Elevate your Creta’s performance with our comprehensive service starting at just Rs. 4499*.



                      &nbsp;</p>
                    <p> Our specialized technicians provide thorough inspections and maintenance tailored to your SUV's needs. From engine tune-ups to fluid checks, trust us to keep your Creta running smoothly on and off the road. Drive with confidence knowing your SUV is in expert hands with our reliable service package.

                      &nbsp;</p>
                    <p> Book your car service at Auto Car Repair (myTVS) now!</p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (25).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Clutch Replacement Service Starting at Rs. 1299/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Get back on the road smoothly with our Clutch Replacement Service priced at Rs. 1299*.


                      &nbsp;</p>
                    <p> Our expert technicians ensure seamless installation of a new clutch, restoring your vehicle's performance and drivability. Don't let a worn-out clutch hold you back – trust us for quick and reliable replacement. Drive confidently knowing your vehicle's clutch is in capable hands with our professional service.

                      &nbsp;</p>
                    <p> Book your car service at Auto Car Repair (myTVS) now!our clutch with care and precision. Book your appointment now and let us take care of the rest!</p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (26).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold"> Windshield Replacement Starting at Rs. 3499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Ensure clear visibility and safety on the road with our Windshield Replacement service, starting at just Rs. 3499*.
                      &nbsp;</p>
                    <p>Our skilled technicians use high-quality materials and precise installation techniques to replace your windshield swiftly and efficiently. Don't let cracks or chips compromise your driving experience – trust us to restore your vehicle's windshield to pristine condition, ensuring a clear view ahead for your journeys.


                      &nbsp;</p>
                    <p> Book your car service at Auto Car Repair (myTVS) now!</p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li>
              <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-7xl mt-5 p-2">
                <div class="md:flex">
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <img class="" src=" resources/views/front/offer/offer2024/  (27).jpeg" alt="Clutch repair">
                  </div>
                  <div class="md:p-8 p-2 md:w-6/12 space-y-3">
                    <div class="uppercase tracking-wide text-sm text-red-500 font-semibold">
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">Car Scanning Starting at Rs. 499/-*
                    </h1>
                    <p class="mt-2 text-slate-500 text-justify">
                    <p>Diagnose your car's issues quickly and affordably with our Car Scanning service, starting at just Rs. 499*.

                      &nbsp;</p>
                    <p>Our state-of-the-art diagnostic tools and experienced technicians efficiently scan your vehicle's systems to identify any potential problems. From engine issues to electronic malfunctions, trust us to provide accurate and reliable diagnoses, ensuring your car stays in top condition. Drive with confidence knowing your vehicle has been thoroughly scanned for optimal performance and safety.
                      &nbsp;</p>
                    <p>Book your car service at Auto Car Repair (myTVS) now!
                    </p>
                    <p>T&amp;C Apply*</p>
                    </p>
                    <button onclick="openForm()" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Book Offer</button>
                    <a href="https://api.whatsapp.com/send?phone=9810446692" class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                      Chat on WhatsApp
                      <svg class="h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 28 28">
                        <g id="Group_5" data-name="Group 5" transform="translate(0.213 0.213)" opacity="0.78">
                          <circle id="Ellipse_3" data-name="Ellipse 3" cx="14" cy="14" r="14" transform="translate(-0.213 -0.213)" fill="#25d366" />
                          <path id="Icon_awesome-whatsapp" data-name="Icon awesome-whatsapp" d="M16.354,5.045a9.535,9.535,0,0,0-15,11.5L0,21.485l5.054-1.327a9.5,9.5,0,0,0,4.556,1.159h0a9.624,9.624,0,0,0,9.622-9.532,9.569,9.569,0,0,0-2.881-6.741ZM9.613,19.712a7.908,7.908,0,0,1-4.036-1.1l-.288-.172-3,.786.8-2.924L2.9,16a7.938,7.938,0,1,1,14.723-4.212A8.011,8.011,0,0,1,9.613,19.712Zm4.345-5.934c-.236-.12-1.408-.7-1.627-.773s-.378-.12-.537.12-.614.773-.756.936-.279.18-.515.06a6.483,6.483,0,0,1-3.242-2.834c-.245-.421.245-.391.7-1.3a.441.441,0,0,0-.021-.416C7.9,9.45,7.424,8.278,7.226,7.8s-.391-.4-.537-.408-.3-.009-.455-.009a.882.882,0,0,0-.635.3,2.676,2.676,0,0,0-.833,1.988,4.666,4.666,0,0,0,.97,2.465,10.643,10.643,0,0,0,4.07,3.6,4.661,4.661,0,0,0,2.86.6A2.439,2.439,0,0,0,14.272,15.2a1.992,1.992,0,0,0,.137-1.134C14.354,13.954,14.195,13.894,13.959,13.778Z" transform="translate(4.169 1.919)" fill="#fff" />
                        </g>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </li>


          </ul>

          <nav class="pagination-container">
            <button class="pagination-button" id="prev-button" aria-label="Previous page" title="Previous page">
              &lt;
            </button>

            <div id="pagination-numbers">

            </div>

            <button class="pagination-button" id="next-button" aria-label="Next page" title="Next page">
              &gt;
            </button>
          </nav>










        </div>
      </div>
      <div class="fixed left-0 right-0 bottom-0 md:hidden z-10">
        <div class="md:block lg:block xl:block" x-data="{open : true}">
          <!-- Button (blue), duh! -->



        </div>


      </div>
    </main>



  </div>



  <style>
    .hidden {
      display: none;
    }

    .pagination-container {
      width: calc(100% - 0rem);
      display: flex;
      align-items: center;
      position: relative;
      padding: 1rem 0;
      justify-content: center;
    }

    .pagination-number,
    .pagination-button {
      font-size: 1.1rem;
      background-color: transparent;
      border: none;
      margin: 0.25rem 0.25rem;
      cursor: pointer;
      height: 2.5rem;
      width: 2.5rem;
      border-radius: .2rem;
    }

    .pagination-number:hover,
    .pagination-button:not(.disabled):hover {
      background: #fff;
    }

    .pagination-number.active {
      color: #fff;
      background: #0085b6;
    }
  </style>
  <script>
    const paginationNumbers = document.getElementById("pagination-numbers");
    const paginatedList = document.getElementById("paginated-list");
    const listItems = paginatedList.querySelectorAll("li");
    const nextButton = document.getElementById("next-button");
    const prevButton = document.getElementById("prev-button");

    const paginationLimit = 20;
    const pageCount = Math.ceil(listItems.length / paginationLimit);
    let currentPage = 1;

    const disableButton = (button) => {
      button.classList.add("disabled");
      button.setAttribute("disabled", true);
    };

    const enableButton = (button) => {
      button.classList.remove("disabled");
      button.removeAttribute("disabled");
    };

    const handlePageButtonsStatus = () => {
      if (currentPage === 1) {
        disableButton(prevButton);
      } else {
        enableButton(prevButton);
      }

      if (pageCount === currentPage) {
        disableButton(nextButton);
      } else {
        enableButton(nextButton);
      }
    };

    const handleActivePageNumber = () => {
      document.querySelectorAll(".pagination-number").forEach((button) => {
        button.classList.remove("active");
        const pageIndex = Number(button.getAttribute("page-index"));
        if (pageIndex == currentPage) {
          button.classList.add("active");
        }
      });
    };

    const appendPageNumber = (index) => {
      const pageNumber = document.createElement("button");
      pageNumber.className = "pagination-number";
      pageNumber.innerHTML = index;
      pageNumber.setAttribute("page-index", index);
      pageNumber.setAttribute("aria-label", "Page " + index);

      paginationNumbers.appendChild(pageNumber);
    };

    const getPaginationNumbers = () => {
      for (let i = 1; i <= pageCount; i++) {
        appendPageNumber(i);
      }
    };

    const setCurrentPage = (pageNum) => {
      currentPage = pageNum;

      handleActivePageNumber();
      handlePageButtonsStatus();

      const prevRange = (pageNum - 1) * paginationLimit;
      const currRange = pageNum * paginationLimit;

      listItems.forEach((item, index) => {
        item.classList.add("hidden");
        if (index >= prevRange && index < currRange) {
          item.classList.remove("hidden");
        }
      });
    };

    window.addEventListener("load", () => {
      getPaginationNumbers();
      setCurrentPage(1);

      prevButton.addEventListener("click", () => {
        setCurrentPage(currentPage - 1);
      });

      nextButton.addEventListener("click", () => {
        setCurrentPage(currentPage + 1);
      });

      document.querySelectorAll(".pagination-number").forEach((button) => {
        const pageIndex = Number(button.getAttribute("page-index"));

        if (pageIndex) {
          button.addEventListener("click", () => {
            setCurrentPage(pageIndex);
          });
        }
      });
    });
  </script>
  <!-- Swiper JS -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>

  <!-- <script>
    new Swiper(".mySwiper", {
        spaceBetween: 30,
        centeredSlides: true,
        loop: true,
        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });

    new Swiper(".mySwiperInfoBar", {
        breakpoints: {
            // when window width is >= 320px
            768: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            0: {
                slidesPerView: 4,
                spaceBetween: 0
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 20
            }
        },
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        history: {
            key: "slide",
        },
    });
    new Swiper(".mySwiperUsedCar", {
        loop: true,
        breakpoints: {
            // when window width is >= 320px
            768: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            0: {
                slidesPerView: 1,
                spaceBetween: 20
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 20
            }
        },
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true
        },
        history: {
            key: "slide",
        },
    });
    new Swiper(".mySwiperOffers", {
        loop: true,
        breakpoints: {
            // when window width is >= 320px
            768: {
                slidesPerView: 1,
                spaceBetween: 20
            },
            0: {
                slidesPerView: 1,
                spaceBetween: 20
            },
            1024: {
                slidesPerView: 1,
                spaceBetween: 20
            }
        },
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true
        },
        history: {
            key: "slide",
        },
    });
</script> -->
</body>

@endsection