@extends('front.layout.main')
@section('content')

<div class="banner-wrapper">
  <div class="banner-container">
    <div class="banner-img-wrapper">
      <div class="banner-padding">
        <div class="banner-abs">
          <div class="banner-abs-main">
            <div class="banner-inner-wrapper">
              <img alt="" class="CoverPhoto" src="resources/views/front/warranty/image/men banner.webp">
              
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


	<Style>
.banner-wrapper {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  position: relative;
  flex-wrap: nowrap;
  flex-shrink: 0;
  justify-content: center;
}
.banner-wrapper .banner-container {
  align-items: center;
  flex-grow: 1;
  flex-direction: column;
  flex-shrink: 1;
  display: flex;
  
  position: relative;
  flex-basis: 0px;
  justify-content: center;
}
.banner-wrapper .banner-container .banner-img-wrapper {
  
  overflow-y: hidden;
  overflow-x: hidden;
  position: relative;
  width: 100%;
}
.banner-wrapper .banner-container .banner-img-wrapper .banner-padding {
  padding-top: 21.296%;
  overflow-y: hidden;
  overflow-x: hidden;
  position: relative;
  width: 100%;
  background-color: #ccc;
}
@media only screen and (max-width: 768px) {
  .banner-wrapper .banner-container .banner-img-wrapper .banner-padding {
    padding-top: 25.296%;
  }
}
.banner-wrapper .banner-container .banner-img-wrapper .banner-padding .banner-abs {
  padding-left: 0;
  flex-grow: 1;
  flex-direction: column;
  margin-right: 0;
  z-index: 0;
  flex-shrink: 1;
  align-items: stretch;
  margin-left: 0;
  min-width: 0;
  bottom: 0;
  justify-content: space-between;
  display: flex;
  left: 0;
  padding-top: 0;
  top: 0;
  right: 0;
  margin-bottom: 0;
  position: absolute;
  padding-bottom: 0;
  min-height: 0;
}
.banner-wrapper .banner-container .banner-img-wrapper .banner-padding .banner-abs .banner-abs-main {
  left: 50%;
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  padding-top: 25%;
  width: 100%;
  height: 0;
  overflow-y: hidden;
  overflow-x: hidden;
}
@media only screen and (max-width: 768px) {
  .banner-wrapper .banner-container .banner-img-wrapper .banner-padding .banner-abs .banner-abs-main {
    padding-top: 25%;
  }
}
.banner-wrapper .banner-container .banner-img-wrapper .banner-padding .banner-abs .banner-abs-main .banner-inner-wrapper {
  position: absolute;
  padding-left: 0;
  right: 0;
  top: 0;
  left: 0;
  bottom: 0;
  flex-grow: 1;
  flex-direction: column;
  margin-right: 0;
  flex-shrink: 1;
  align-items: stretch;
  justify-content: space-between;
  display: flex;
}
.banner-wrapper .banner-container .banner-img-wrapper .banner-padding .banner-abs .banner-abs-main .CoverPhoto {
  position: absolute;
  right: 0;
  top: 0;
  left: 0;
  bottom: 0;
  width: 100%;
}



label {
  font-family: "Urbanist";
  font-size: 1.4rem;
}






.form-container {
  height: 100%;
  width: auto;
  display: flex;
  
}
.form-container .form {
  
  padding: 2rem;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  
 
  width: 100%
}
.form-container .form .submit-btn {
      background-color: #194797;
      color:white;
    padding: 0.2rem 0.8rem;
    /* border: 1px solid #ff7315; */
    cursor: pointer;
    font-family: "Urbanist";
    font-weight: 400;
}
.popup-form__submit{
    background-color: #4cc713!important;
}
}
.form-container .form .submit-btn:hover {
  border: 1px dashed #ff7315;
  color: blue;
}
.form-container .form .submit-btn:active {
  background-color: #ff7315;
  color: white;
}
.form-container .form .username-bar {
  border: 1px solid #000000ba;
    padding: 0.2rem 0.8rem;
}
.form-container .sidebar {
      box-shadow: rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.178) 0px 4px 6px;
    padding: 2rem;
    display: flex;
    flex-direction: column; 
    width: 100%;
    background: #ff7214;
    background: linear-gradient(45deg, rgba(255, 114, 20, 0.98) 16%, #f7aa02 74%);
    background: -moz-linear-gradient(45deg, rgba(255, 114, 20, 0.98) 16%, #f7aa02 74%);
    background: -webkit-linear-gradient(45deg, rgba(255, 114, 20, 0.98) 16%, #f7aa02 74%);
    flex-wrap: wrap;
    align-content: space-around;
    justify-content: space-between;
    align-items: center;
}
.form-container .sidebar h2 {
  margin-top: auto;
}

@media screen and (max-width: 524px) {
  .form-container {
    flex-direction: column;
  }

  /* .form {
    margin-top: 2rem;
    height: 8rem;
    width: 8rem;
  } */

  .sidebar {
    
    
    
  }
}
	</Style>

  <!-- brochure section -->

<style>
    
.buttonDownload {
	display: inline-block;
	position: relative;
	padding: 10px 25px;
  
	background-color: #194797;
	color: white;
  
	font-family: sans-serif;
	text-decoration: none;
	font-size: 0.9em;
	text-align: center;
	text-indent: 15px;
}

.buttonDownload:hover {
	background-color: #333;
	color: white;
}

.buttonDownload:before, .buttonDownload:after {
	content: ' ';
	display: block;
	position: absolute;
	left: 15px;
	top: 52%;
}

/* Download box shape  */
.buttonDownload:before {
	width: 10px;
	height: 2px;
	border-style: solid;
	border-width: 0 2px 2px;
}

/* Download arrow shape */
.buttonDownload:after {
	width: 0;
	height: 0;
	margin-left: 3px;
	margin-top: -7px;
  
	border-style: solid;
	border-width: 4px 4px 0 4px;
	border-color: transparent;
	border-top-color: inherit;
	
	animation: downloadArrow 2s linear infinite;
	animation-play-state: paused;
}

.buttonDownload:hover:before {
	border-color: #4CC713;
}

.buttonDownload:hover:after {
	border-top-color: #4CC713;
	animation-play-state: running;
}

/* keyframes for the download icon anim */
@keyframes downloadArrow {
	/* 0% and 0.001% keyframes used as a hackish way of having the button frozen on a nice looking frame by default */
	0% {
		margin-top: -7px;
		opacity: 1;
	}
	
	0.001% {
		margin-top: -15px;
		opacity: 0;
	}
	
	50% {
		opacity: 1;
	}
	
	100% {
		margin-top: 0;
		opacity: 0;
	}
}
</style>
    

    
    
  
<section id="brochure-section" style="    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-content: center;
    justify-content: space-between;
    align-items: center;
	padding: 30px;">
     
	<div class="container">
		<section class="form-container">
			<div class="wrapper">
  				<div class="content">
    				<h1>ACR Secure</h1>
    				<p style="text-align: justify;">ACR Secure offers added protection for your car, safeguarding your investment. After the initial warranty expires, The ACR Secure offers additional coverage, protecting you against maintenance and repair costs. ACR Secure policy offers you peace of mind by covering unexpected repair costs after the manufacturer's warranty expires. It safeguards your budget, ensures continued protection against mechanical failures, and allows you to enjoy your vehicle with added confidence, knowing that potential repairs are financially covered. ACR secure policy protects you on the road while you enjoy a hassle-free ride.</p>
    			</div>
			</div>
    	</section>
	</div>
</section>

    

<style>

.wrapper > *:not(first-of-type) {
  margin: 2em auto;
}

.content {
  color: var(--colorDark);
  text-align: center;
  max-width: 90%;
  margin: 0 auto;
}
.content img {
  max-width: 100%;
  height: auto;
}

pre {
  border: 1em solid var(--colorLight);
  padding: 1em;
  color: var(--colorLight);
  background: var(--colorDark);
  width: calc(var(--vwMax) * 1px);
  max-width: 100%;
  box-sizing: border-box;
  overflow-x: scroll;
  font-size: 0.8em;
}
@media screen and (max-width: 420px) {
  pre {
    box-shadow: inset -1em 0 1em #002129;
    border: 0;
  }
}


.container {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    : 10px;
    transition: transform 0.3s;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    margin-bottom: 5px;
}

input, textarea {
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ccc;
    : 5px;
	width: 70%;
}

textarea {
    resize: none;
    height: 100px;
}

button {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    : 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #0056b3;
}

.download-btn {
    background-color: white;
    border: 1px solid #ccc;
    : 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.download-btn img {
    width: 30px;
    height: 30px;
}

.download-btn:hover {
    background-color: #f8f9fa;
}
</style>

	<script>
		document.getElementById('feedback-form').addEventListener('submit', (event) => {
    event.preventDefault();
    alert('Feedback submitted!');
});

document.getElementById('download-btn').addEventListener('click', () => {
    alert('Download started!');
});
	</script>


		<section class="why-choose-section">
		    
			<div class="container">
			    <h2 style="font-size: 2rem;
    text-align: center;
    padding: 20px;">Stay Protected With<strong> ACR Secure </strong></h2>
				<div class="row">
					<div class="col-md-5 col-12 text-center">
						<div class="container" style="    height: 100%;
						    box-shadow: none;
                            display: grid;
                            align-content: space-around;
                            align-items: center;
                            width: auto;">
							<img src="resources/views/front/warranty/image/banner (3).webp" alt="about us" style="width: 100%;">
						</div>
					</div><!--- END COL -->
					<div class="col-md-7 col-12">
						<div class="choose-content">
							
							<div class="row">
								<div class="col-md-6 col-12">
									<div class="choose-box">
										<div class="choose-icon">
											<img src="resources/views/front/warranty/image/7 (1).webp" style="width: 4rem;">
										</div>
										<div class="choose-cont">
											<h4>Terms and Conditions</h4>
											<p >
											    Cars up to 10 years old or that have been run for less than 1,00,000 km are eligible for ACR Secure after a thorough inspection.</p>
										</div>
									</div>
								</div>
								<div class="col-md-6 col-12">
									<div class="choose-box">
										<div class="choose-icon">
											<img src="resources/views/front/warranty/image/1 (1).webp" style="width:4rem;">
										</div>
										<div class="choose-cont">
											<h4>Servicing Requirements</h4>
												<p style="overflow: auto;
    height: 20vh;">The vehicle must be serviced according to the following recommendations:

1st Paid Service - within 6 months or 7,500 km (whichever is earlier) from the date of start of warranty
2nd Paid Service - within 12 months or 15,000 km (whichever is earlier) from the date of last service
											</p>
										</div>
									</div>
								</div>
								<div class="col-md-6 col-12">
									<div class="choose-box">
										<div class="choose-icon">
											<img src="resources/views/front/warranty/image/4 (1).webp" style="width: 4rem;">
										</div>
										<div class="choose-cont">
											<h4>How to Make a Claim</h4>
												<p style="overflow: auto;
    height: 20vh;">In case of a breakdown, the owner must take all measures to minimize the extent of loss and immediately take or tow the car to the nearest service center. 
											He must also authorize the service center to determine the cause of the breakdown and undertake the costs of repair if the cause is not covered under the warranty.</p>
										</div>
									</div>
								</div>
								<div class="col-md-6 col-12">
									<div class="choose-box">
										<div class="choose-icon">
											<img src="resources/views/front/warranty/image/2 (1).webp" style="width: 4rem;">
										</div>
										<div class="choose-cont">
											<h4>For Your Guidance
											</h4>
											<p>The service center is liable to repair or replace the defective parts covered under the warranty 
											if the defect is due to mechanical or electrical breakdown as defined in the warranty.
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div><!--- END COL -->
				</div><!--- END ROW -->
			</div><!--- END CONTAINER -->
		</section>



<section id="brochure-section" style="    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-content: center;
    justify-content: space-between;
    align-items: center;
	padding: 30px;">
     
	<div class="container">
		<section class="form-container">
        	    <form class="popup-form" method="post" action="{{route('front_contact-store')}}" enctype="multipart/form-data" data-parsley-validate="" style="width: 100%;">
                @csrf
                <h2 class="popup-form__title" style= "color:black;margin: 1.5rem 0;">Contact Us</h2>
                <div class="form__box">
                    <input type="text" name="name" class="popup-form__input" placeholder="Name" required>
                    <label for="" class="form__label">ENTER NAME</label>
                    <div class="form__shadow"></div>
                </div>
                <div class="form__box">
                    <input type="tel" name="phone" class="popup-form__input num_only" maxlength="10" placeholder="Phone" required>
                    <label for="" class="form__label">ENTER Phone</label>
                    <div class="form__shadow"></div>
                </div>
                <div class="form__box">
                    <input type="email" name="email" class="popup-form__input" placeholder="Email" required>
                    <label for="" class="form__label">ENTER Email</label>
                    <div class="form__shadow"></div>
                </div>
                <div class="form__box">
                    <input type="text" name="message"  class="popup-form__input" placeholder="Your Message" required>
                    <label for="" class="form__label">ENTER Message</label>
                    <div class="form__shadow"></div>
                </div>
                <div class="form__box">
                    <select class="popup-form__input1" required="" name="location">
                        <option selected disabled>Location</option>
                        <option value="Motinagar">Motinagar</option>
                        <option value="Gurgaon">Gurgaon</option>
                    </select>
                </div>
                <?php /* <div id="contact-form-captcha" class="g-recaptcha" data-sitekey="{{ env('GOOGLE_RECAPTCHA_KEY') }}" data-callback="onSubmit"></div> */ ?>
                <div class="form__button">
                    <button type="submit" class="popup-form__submit" style="background-color: #194797!important;color: white">Submit</button>
                </div>
            </form>
        	<div class="sidebar">
            <h1 style="font-size: 3rem;
    text-align: center;">ACR Secure Policy</h1>
            <p style="     margin-top: 0;     margin-bottom: 0;
    text-align: center;
">Check Out Our Pricing Details and More by Clicking Below! Fill Out the Form and We Will Get Back to You!</p>
            <div class="container" style="    display: flex; 
    flex-direction: row;
    box-shadow: none;
    flex-wrap: nowrap;
    justify-content: space-evenly;
    gap:10px;">
            <!--<span>-->
            <!--<a href="resources/views/front/warranty/image/EW.xlsx" class="buttonDownload">Pricing</a>-->
            <!--</span>-->
            <span>
            <a href="resources/views/front/warranty/image/Booklet.pdf" class="buttonDownload">Booklet</a> 
             </span>        </div>
        	</div>
    	</section>
	</div>
</section>






<script src="../ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="../cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" type="text/javascript"></script>
<script src="assets/new/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/new/js/marq.js" type="text/javascript"></script>


@endsection
