@extends('front.layout.main')

@section('content')



<!--Bootstrap Full-Width Responsive Slider/Carousel + bcSwipe + Caption + owlcarousel style pagination. Enjoy :) -->

<script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">



<section>

    <div class="owl-carousel owl-theme">

                <div class="item">

                <img src="resources\views\front\servicecenter\files\img\acrbanner.jpeg" >  

                </div>                    

                <div class="item">

                <img src="resources\views\front\servicecenter\files\img\acrbanner.jpeg" >   

                </div>             

                <div class="item">

                <img src="resources\views\front\servicecenter\files\img\acrbanner.jpeg"> 

                </div>

    </div>

</section>

<style>

  .section {

    margin: 0 auto;

  padding: 0px !important;

}

             .item {

 

  position: relative;

    }

    .item img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    }

    .item .cover {

    padding: 75px 0;

    position: absolute;

    width: 100%;

    height: 100%;

    top: 0;

    left: 0;

    background: rgba(0, 0, 0, 0.6);

    display: flex;

    align-items: center;

    }

    .item .cover .header-content {

    position: relative;

    padding: 56px;

    overflow: hidden;

    }

    .item .cover .header-content .line {

    content: "";

    display: inline-block;

    width: 100%;

    height: 100%;

    left: 0;

    top: 0;

    position: absolute;

    border: 9px solid #fff;

    -webkit-clip-path: polygon(0 0, 60% 0, 36% 100%, 0 100%);

    clip-path: polygon(0 0, 60% 0, 36% 100%, 0 100%);

    }

    .item .cover .header-content h2 {

    font-weight: 300;

    font-size: 35px;

    color: #fff;

    }

    .item .cover .header-content h1 {

    font-size: 56px;

    font-weight: 600;

    margin: 5px 0 20px;

    word-spacing: 3px;

    color: orangered;

    }

    .item .cover .header-content h4 {

    font-size: 24px;

    font-weight: 300;

    line-height: 36px;

    color: #fff;

    }

    .owl-item.active h1 {

    -webkit-animation-duration: 1s;

    animation-duration: 1s;

    -webkit-animation-fill-mode: both;

    animation-fill-mode: both;

    animation-name: fadeInDown;

    animation-delay: 0.3s;

    }

    .owl-item.active h2 {

    -webkit-animation-duration: 1s;

    animation-duration: 1s;

    -webkit-animation-fill-mode: both;

    animation-fill-mode: both;

    animation-name: fadeInDown;

    animation-delay: 0.3s;

    }

    .owl-item.active h4 {

    -webkit-animation-duration: 1s;

    animation-duration: 1s;

    -webkit-animation-fill-mode: both;

    animation-fill-mode: both;

    animation-name: fadeInUp;

    animation-delay: 0.3s;

    }

    .owl-item.active {

    -webkit-animation-duration: 3s;

    animation-duration: 2s;

    -webkit-animation-fill-mode: both;

    animation-fill-mode: both;

    animation-name: fadeInLeft;

    animation-delay: 0.3s;

    }

    .owl-item .line {

    -webkit-animation-duration: 5s;

    animation-duration: 5s;

    -webkit-animation-fill-mode: both;

    animation-fill-mode: both;

    animation-name: fadeInLeft;

    animation-delay: 0.3s;

    }



    .owl-nav .owl-prev {

    position: absolute;

    left: 15px;

    top: 43%;

    opacity: 0;

    -webkit-transition: all 0.4s ease-out;

    transition: all 0.4s ease-out;

    background: rgba(0, 0, 0, 0.5) !important;

    width: 40px;

    cursor: pointer;

    height: 40px;

    position: absolute;

    display: block;

    z-index: 1000;

    border-radius: 0;

    }

    .owl-nav .owl-prev span {

    font-size: 1.6875rem;

    color: #fff;

    }

    .owl-nav .owl-prev:focus {

    outline: 0;

    }

    .owl-nav .owl-prev:hover {

    background: #000 !important;

    }

    .owl-nav .owl-next {

    position: absolute;

    right: 15px;

    top: 43%;

    opacity: 0;

    -webkit-transition: all 0.4s ease-out;

    transition: all 0.4s ease-out;

    background: rgba(0, 0, 0, 0.5) !important;

    width: 40px;

    cursor: pointer;

    height: 40px;

    position: absolute;

    display: block;

    z-index: 1000;

    border-radius: 0;

    }

    .owl-nav .owl-next span {

    font-size: 1.6875rem;

    color: #fff;

    }

    .owl-nav .owl-next:focus {

    outline: 0;

    }

    .owl-nav .owl-next:hover {

    background: #000 !important;

    }

</style>



<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.js"></script>

<script>

            $('.owl-carousel').owlCarousel({

    loop:true,

    margin:10,

    dots:false,

    nav:true,

    mouseDrag:false,

    autoplay:true,

    animateOut: 'slideOutUp',

    responsive:{

        0:{

            items:1

        },

        600:{

            items:1

        },

        1000:{

            items:1

        }

    }

    });

</script>



<section class="section_all bg-light" id="about">

            <div class="container">

                <div class="row">

                    <div class="col-lg-12">

                        <div class="section_title_all text-center">

                            <h3 class="font-weight-bold">Auto Car Repair<span class="text-custom"> Multi-brand Car Service Center in Delhi NCR</span></h3>

                            <p class="section_subtitle mx-auto text-muted">Visit our Auto Car Repair for comprehensive automotive care. Our skilled technicians are adept at servicing a variety of car brands, ensuring expert maintenance, repairs, and genuine parts.

				Experience personalized service, competitive pricing, and a one-stop solution for all your vehicle needs. Whether it's routine maintenance or complex repairs, trust us to keep your car running smoothly and efficiently, maintaining its optimal performance and longevity.</p>

                  

                        </div>

                    </div>

                </div>

                

</section>



<style>

       h3 {

          color: #2c3145;

      }

         



      .section_all {

          position: relative;

          padding-top: 40px;

          padding-bottom: 40px;

          

      }



</style>







<section>

		<div class="harssh" >

			<h1 style="    font-weight: 700;

    color: #1d4694;

    letter-spacing: 2px;margin: 10px;    text-align: -webkit-center;

">Locations</h1>

		</div>

</section>







<!--  slider  --><div class="section">

  <div class="container">

    <div class="swiper">

      <div class="swiper-wrapper">

        <div class="swiper-slide">

          <div class="people__card">

            <img src="resources\views\front\servicecenter\files\img\cgmotinagar.jpeg" class="people__card__image" style="    opacity: 25%;">

            <div class="people__card__content">

              <div class="slide__number">Auto Car Repair</div>

              <div class="slide__title"> MOTI NAGAR</div>

              <div class="slide__subtitle"> 60 N G Road, Rama Rd, Moti Nagar, New Delhi, Delhi 110015</div>

              <a href="https://acr-mechanic.com/motinagar" class="slide__btn">

                <span class="slide__btn__text">Click Now</span>

                <span class="slide__btn__icon">

                <svg width="100%" height="100%" viewBox="0 0 17 21" fill="none" xmlns="http://www.w3.org/2000/svg">

                    <path d="M3.22218 15.2222C2.79261 15.6518 2.79261 16.3482 3.22218 16.7778C3.65176 17.2074 4.34824 17.2074 4.77782 16.7778L3.22218 15.2222ZM16.1 5C16.1 4.39249 15.6075 3.9 15 3.9L5.1 3.9C4.49249 3.9 4 4.39249 4 5C4 5.60751 4.49249 6.1 5.1 6.1L13.9 6.1L13.9 14.9C13.9 15.5075 14.3925 16 15 16C15.6075 16 16.1 15.5075 16.1 14.9L16.1 5ZM4.77782 16.7778L15.7778 5.77782L14.2222 4.22218L3.22218 15.2222L4.77782 16.7778Z" fill="currentColor" />

                  </svg>

                </span>

              </a>

            </div>

            <div class="slide__gradient"></div>

          </div>

        </div>

        <div class="swiper-slide">

          <div class="people__card">

            <img src="resources\views\front\servicecenter\files\img\cggurugram.jpeg" class="people__card__image" style="opacity:25%">

            <div class="people__card__content">

              <div class="slide__number">Auto Car Repair</div>

              <div class="slide__title">GURUGRAM</div>

              <div class="slide__subtitle">Unit-1 Plot No 29 & 30, near, Kargil Shaheed Sukhbir Singh Yadav Marg, Info Technology Park, Sector 34, Gurugram, Haryana 122001</div>

              <a href="https://acr-mechanic.com/auto-car-repair-gurgaon" class="slide__btn">

                <span class="slide__btn__text">Click Now</span>

                <span class="slide__btn__icon">

                  <svg width="100%" height="100%" viewBox="0 0 17 21" fill="none" xmlns="http://www.w3.org/2000/svg">

                    <path d="M3.22218 15.2222C2.79261 15.6518 2.79261 16.3482 3.22218 16.7778C3.65176 17.2074 4.34824 17.2074 4.77782 16.7778L3.22218 15.2222ZM16.1 5C16.1 4.39249 15.6075 3.9 15 3.9L5.1 3.9C4.49249 3.9 4 4.39249 4 5C4 5.60751 4.49249 6.1 5.1 6.1L13.9 6.1L13.9 14.9C13.9 15.5075 14.3925 16 15 16C15.6075 16 16.1 15.5075 16.1 14.9L16.1 5ZM4.77782 16.7778L15.7778 5.77782L14.2222 4.22218L3.22218 15.2222L4.77782 16.7778Z" fill="currentColor" />

                  </svg>

                </span>

              </a>

            </div>

            <div class="slide__gradient"></div>

          </div>

        </div>

        <div class="swiper-slide">

          <div class="people__card">

            <img src="resources\views\front\servicecenter\files\img\comingsoon.jpeg" class="people__card__image" style="opacity:45%">

            <div class="people__card__content">

              <div class="slide__number">Auto Car Repair</div>

              <div class="slide__title">Coming Soon</div>

              <div class="slide__subtitle">•	Sahibabad

              •	Okhla

              •	Azadpur

              •	Faridabad 

              •	Karnal

              •	Manesar

              •	Sonipat</div>

              <a href="" class="slide__btn">

                <span class="slide__btn__text">Click Now</span>

                <span class="slide__btn__icon">

                  <svg width="100%" height="100%" viewBox="0 0 17 21" fill="none" xmlns="http://www.w3.org/2000/svg">

                    <path d="M3.22218 15.2222C2.79261 15.6518 2.79261 16.3482 3.22218 16.7778C3.65176 17.2074 4.34824 17.2074 4.77782 16.7778L3.22218 15.2222ZM16.1 5C16.1 4.39249 15.6075 3.9 15 3.9L5.1 3.9C4.49249 3.9 4 4.39249 4 5C4 5.60751 4.49249 6.1 5.1 6.1L13.9 6.1L13.9 14.9C13.9 15.5075 14.3925 16 15 16C15.6075 16 16.1 15.5075 16.1 14.9L16.1 5ZM4.77782 16.7778L15.7778 5.77782L14.2222 4.22218L3.22218 15.2222L4.77782 16.7778Z" fill="currentColor" />

                  </svg>

                </span>

              </a>

            </div>

            <div class="slide__gradient"></div>

          </div>

        </div>

       

      </div>

    </div>

  </div>

</div>

<!-- https://webflow.com/made-in-webflow/website/clonabale-parallax-swiper -->

<style>

  

img {

  max-width: 100%;

  vertical-align: middle;

  display: inline-block;

}



.section {

  justify-content: center;

  align-items: center;

  display: flex;

  position: relative;

  overflow: hidden;

  .container {

    width: 100%;

    max-width: 1920px;

    margin-left: auto;

    margin-right: auto;

    padding: 30px;

  }

}



.swiper-wrapper {

  flex: none;

  align-items: flex-start;

  display: flex;

}

.swiper-slide {

  flex: none;

  .people__card {

    position: relative;

    overflow: hidden;

    height: 640px;

    background-color: #111b1a;

    border-radius: 11px;

    @media (max-width: 1699px) {

      height: 512px;

    }

    @media (max-width: 1199px) {

      height: 450px;

    }

    @media (max-width: 991px) {

      height: 400px;

    }

    @media (max-width: 767px) {

      height: auto;

    }

    .people__card__image {

      display: inline-block;

      position: absolute;

      top: 0%;

      bottom: 0%;

      left: 0%;

      right: 0%;

      z-index: 2;

      margin-left: -100px;

      width: 130%;

      height: 100%;

      max-width: none;

      object-fit: cover;

      border-radius: 13px;

      transition: transform 0.7s;

    }

    .people__card__content {

      position: relative;

      z-index: 3;

      display: flex;

      flex-direction: column;

      align-items: flex-start;
      justify-content: center;

      width: 100%;

      height: 100%;

      padding: 40px 30px;

      border-radius: 11px;

      transition: 0.3s;

      .slide__number {

        margin-bottom: 30px;

        opacity: 1;

        font-size: 32px;

        font-weight: 300;

        color: #ebefe3;

        @media (max-width: 1199px) {

          margin-bottom: 20px;

          font-size: 24px;

        }

        @media (max-width: 1199px) {

          font-size: 20px;

        }

      }

      .slide__title {

        margin-bottom: 20px;

        font-size: 3em;

        font-weight: 700;

        line-height: 1.2;

        letter-spacing: -0.03em;

        color: #ebefe3;

        @media (max-width: 1199px) {

          font-size: 2.4em;

        }

        @media (max-width: 767px) {

          font-size: 1.92em;

        }

      }

      .slide__subtitle {

        margin-bottom: 30px;

        max-width: 70%;

        color: #ebefe3;

        font-size: 16px;

        font-weight: 400;

        line-height: 1.6;

        @media (max-width: 1199px) {

          font-size: 15px;

          max-width: 100%;

        }

        @media (max-width: 767px) {

          font-size: 14px;

          max-width: 85%;

        }

      }

      .slide__btn {

        display: flex;

        justify-content: center;

        align-items: center;

        padding: 12px 24px;

        border: 1px solid #ebefe3;

        border-radius: 30px;

        text-decoration: none;

        transition: background-color 0.3s;

        @media (max-width: 767px) {

          padding: 10px 20px;

        }

        &:hover {

          background-color: #ebefe3;

          .slide__btn__text {

            color: #111b1a;

          }

          .slide__btn__icon {

            path {

              color: #111b1a;

            }

          }

        }

        .slide__btn__text {

          margin-right: 5px;

          font-size: 20px;

          font-weight: 500;

          color: #ebefe3;

          transition: color 0.3s;

          @media (max-width: 767px) {

            font-size: 18px;

          }

        }

        .slide__btn__icon {

          width: 15px;

          font-size: 24px;

          @media (max-width: 767px) {

            font-size: 18px;

          }

          path {

            color: #ebefe3;

            transition: 0.3s;

          }

        }

      }

    }

    .slide__gradient {

      position: absolute;

      z-index: 2;

      top: 0%;

      bottom: 0%;

      left: 0%;

      right: 0%;

      background-image: linear-gradient(111deg, #000, rgba(0, 0, 0, 15%) 60%);

    }

  }

}



.swiper-slide.is-active .people__card__image {

  transform: translateX(100px);

}



</style>

<script>

  const swiper = new Swiper(".swiper", {

  // Optional parameters

  direction: "horizontal",

  grabCursor: true,

  slidesPerView: 1,

  slidesPerGroup: 1,

  centeredSlides: false,

  loop: true,

  spaceBetween: 10,

  mousewheel: {

    forceToAxis: true

  },

  breakpoints: {

    767: {

      slidesPerView: 2,

      spaceBetween: 24,

    },

    1699: {

      slidesPerView: 3,

      spaceBetween: 24,

    },

  },

  speed: 700,

  slideActiveClass: "is-active",

  slideDuplicateActiveClass: "is-active"

});



</script>



<style>

  

  .contact-form {

      background-color: #f4f4f4;

      padding: 30px;

  }

  

  .contact-form-location {

      background-color: #f4f4f4;

      padding: 30px;

  }

  

  .locations-contact-us {

      text-align: center;

  }

  

  textarea.form-control {

      height: auto;

  }

  .form-check {

      position: relative;

      display: block;

      padding-left: 1.59rem;

  }

  

  

  @media (min-width: 768px){

  .col-md-6 {

      -ms-flex: 0 0 50%;

      flex: 0 0 50%;

      max-width: 50%;

  }

  }

  .form-group {

      margin-bottom: 1rem;

  }

  </style>

<section id="location-wiseform" class="section">

	<div class="container">

		<div class="row">

			<div class="col-md-6">

				<div class="contact-form-location">

					<h3 style="    font-weight: 600;  ">Let’s connect!</h3>

					<p>We’d love to hear from you. Drop a message or give us a call. You can use the form below to get in touch with us.</p>

					<div class="form-contact-us">

						<form action="" method="post">

							<div class="row">

								<div class="col-md-6 col-sm-12 margin-bookservice" style="    padding: 10px 15px;  ">

									<input type="text" class="form-control" placeholder="First Name" required>

								</div>

								<div class="col-md-6 col-sm-12 margin-bookservice" style="    padding: 10px 15px;  ">

									<input type="email" class="form-control" placeholder="Email" required>

								</div>

							</div>

							<div class="row">

								<div class="col-md-6 col-sm-12 margin-bookservice" style="    padding: 10px 15px;  ">

									<input type="tel" class="form-control" placeholder="Phone" required>

								</div>

								<div class="col-md-6 col-sm-12 margin-bookservice" style="    padding: 10px 15px;  ">

									<select id="inputState" class="form-control">

										<option selected>Choose Service</option>

										<option>New Car</option>

										<option>Used Car</option>

										<option>Service</option>

										<option>Insurence</option>

									</select>

								</div>

							</div>

							<div class="form-group" style="    padding: 10px 0px;">



								<textarea class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>

							</div>



							<div class="form-group margin-bookservice">

								<div class="form-check">

									<input class="form-check-input" type="checkbox" id="gridCheck">

									<label class="form-check-label" for="gridCheck">

                    <span style="padding: 15px;">I agree to the Privacy Policy.</span>

									</label>

								</div>

							</div>



							<button type="submit" class="btn btn-primary bookservice-button">Submit</button>





						</form>

					</div>



				</div>

			</div>









			<div class="col-md-6">

				<div class="locations-contact-us">

					<img src="resources\views\front\servicecenter\files\img\Lets-Connect.jpeg" alt="" width="65%">

				</div>

			</div>

		</div>

	</div>

</section>









@endsection

