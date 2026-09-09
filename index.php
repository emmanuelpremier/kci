<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="https://www.w3school.com/lib/w3.com">
	<meta name="description" content="Kingdomite Church Int'l">
	<link rel="stylesheet" type="text/css" href="kci.css">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Kingdomite Church Int'l</title>
</head>
<body>
	<!--header-->
	<section class="kc1">
		<div class="kc2">
			<img src="kci_image/im2.jpg">
		</div>
		<div class="kc3">
			<ul>
				<li><a href="index.php">Home</a>
					<div class="line"></div>
				</li>
				<li><a href="aboutus.php">About</a>
					<div class="linea"></div>
				</li>
				<li ><a href="events.php">Events   v</a>
					<div class="linec"></div>
					<ul>
						<li><a href="events/oil.php">Oil & Wine Summit</a></li>
						<li><a href="events/conf.php">June Confrence</a></li>
						<li><a href="events/embers.php">Embers Of Glory</a></li>
					</ul>
				</li>
				<li class="linef"><a href="min.php">Ministries         v</a>
					<div class="lined"></div>
					<ul>
						<li><a href="miss.php">Missions</a></li>
						<li><a href="wom.php">Women's Ministry</a></li>
						<li><a href="yot.php">YC-The Youth Church</a></li>
						<li><a href="mte.php">MTC-Mighty Teens Church</a></li>
						<li><a href="chi.php">Children Church</a></li>
					</ul>
				</li>
				<li><a href="contact.php">Contact</a>
					<div class="linee"></div>
				</li>
			</ul>
		</div>
		<div class="kc4">
			<a href="giving.php">Give</a>
		</div>
	</section>

	<div class="kc5">
		<h2>Welcome to the<span class="spe"><br>Kingdomite Church International</span></h2>
		<h4>A Place of destiny discovery</h4>
		<p>We are glad you're here!</p>
	</div>
	<div class="kc6 reveal active">
		<div class="kc7">
			<div class="kc9"><h2>WELCOME</h2><h3>________________</h3>
			</div>
			<p>TO THE FAMILY!</p>
		</div>
		<div class="kc8">
			<p>Experience Love, Experience the Life of God, Experience direction for purposeful living! Welcome to Kingdomite Church International, a church where people genuinely encounter God.</p>
		</div>
	</div>
	<script type="text/javascript">
		window.addEventListener('scroll', reveal)

			function reveal(){
				var reveals = document.querySelectorAll('.reveal');

				for (var i = 0; i < reveals.length; i++) {
					var windowheight = window.innerHeight;
					var revealTop = reveals[i].getBoundingClientRect().top;
					var revealpoint = 120;

					if (revealTop < windowheight - revealpoint) {
						reveals[i].classList.add('active');
					}
					else{
						reveals[i].classList.remove('active');
					}
				}
			}
	</script>
	<div class="kc10">
		<div class="kc11">
			<h2>Is it your first time worshiping with us? If it is, kindly fill out our</h2>
			<a href="form.php">Form Now!</a>
		</div>
		<div class="kc12">
			<h2>Don't Know our Church?</h2>
			<a href="location.php">Find Now!</a>
		</div>
	</div>
	<div class="kc13">
		<div class="kc18 real action">
		<div class="kc19"><p>RECENT</p><h3>______________</h3></div>
		<h2>QUOTES</h2>
		</div>
		<div class="kc21 all in">
			<div class="kc14">
				<img src="kci_image/im4.jpg">
			</div>
			<div class="kc15">
				<img src="kci_image/im7.jpg">
			</div>
			<div class="kc16">
				<img src="kci_image/im6.jpg">
			</div>
			<div class="kc17">
				<img src="kci_image/im5.png">
			</div>
			<div class="kc20">
				<a href="quotes.php">View More</a>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		window.addEventListener('scroll', real)

		function real() {
			var reveals = document.querySelectorAll('.real');

			for (var i = 0; i < reveals.length; i++){
				var windowheight = window.innerHeight;
				var revealTop = reveals[i].getBoundingClientRect().top;
				var revealpoint = 160;

				if (revealTop < windowheight - revealpoint) {
					reveals[i].classList.add('action');
				}
				else{
					reveals[i].classList.remove('action');
				}
			}
		}
	</script>
	<script type="text/javascript">
		window.addEventListener('scroll', all)

		function all() {
			var reveals = document.querySelectorAll('.all');

			for (var i = 0; i < reveals.length; i++){
				var windowheight = window.innerHeight;
				var revealTop = reveals[i].getBoundingClientRect().top;
				var revealpoint = 120;

				if (revealTop < windowheight - revealpoint) {
					reveals[i].classList.add('in');
				}
				else{
					reveals[i].classList.remove('in');
				}
			}
		}
	</script>
	<section id="main">
		<div class="kc22 reveal active">
			<div class="kc23">
				<img src="kci_image/im8.jpg">
			</div>
			<div class="kc24">
				<div class="kc25">
					<div class="kc26"><h2>CONNECT WITH</h2><h3>_____________</h3></div>
					<h4>OUR LEAD PASTOR</h4>
				</div>
				<div class="kc27">
					<p>I'm David Vincent, Lead Pastor of Kingdomite Church International. I personally invite you to our official website, I would love to hear from and pray with you. Get in touch with me via any of personal social media handles to stay connected with me, Thank you and remain blessed.</p>
				</div>
				<div class="kc28">
					<a href="https://web.facebook.com/David Vincent Dan-Obu"><img class="fb" src="kci_image/img9.jpg"></a>
					<a href="https://web.whatsapp.com/Pst David Vincent"><img class="wt" src="kci_image/img10.webp"></a>
				</div>
			</div>
		</div>
	</section>
	<script type="text/javascript">
		window.addEventListener('scroll', reveal);
		function reveal(){
			var reveals = document.querySelectorAll('.reveal');
			for (var i = 0; i < reveals.length; i++) {
				var windowheight = window.innerHeight;
				var revealTop = reveals[i].getBoundingClientRect().top;
				var revealpoint = 150;
				if (revealTop < windowheight - revealpoint) {
					reveals[i].classList.add('active');
				}
				else{
					reveals[i].classList.remove('active');
				}
			}	
		}
	</script>
	<div class="kc29">
		<div class="kc30">
		</div>
		<div class="kc31">
			<div class="kc32"><h2>CONNECT</h2><p>________________</p></div>
			<h3>WITH US</h3>
		</div>
	</div>
	<div class="kc33">
		<a href="https://web.facebook.com/The Kingdomites Church International" class="fb-link" data-tooltip="Follow us on Facebook">
			<div class="kc34">
				<div class="kc35">
					<img src="kci_image/img9.jpg">
				</div>
				<div class="kc36">
					<h2>FACEBOOK</h2>
					<p>Like & Follow us on Facebook</p>
				</div>
			</div>
		</a>
		<div class="social-icon like" id="likeIcon">
     	   👍
       		 <!-- Replace with: <img src="like.png" alt="Like"> -->
    	</div>

   		 <!-- Share Icon -->
   		 <div class="social-icon share" id="shareIcon">
    	    <img src="kci_image/img11.jpeg" alt="Share">
   		 </div>

    	<!-- Follow Icon -->
    	<div class="social-icon follow" id="followIcon">
      	  	➕
       		 <!-- Replace with: <img src="follow.png" alt="Follow"> -->
   		</div>
   		 <script>
    // Trigger animations in sequence
    function startAnimation() {
        const like = document.getElementById('likeIcon');
        const share = document.getElementById('shareIcon');
        const follow = document.getElementById('followIcon');

        // Reset
        [like, share, follow].forEach(el => {
            el.classList.remove('slide-in-left', 'slide-in-right');
            void el.offsetWidth; // Restart animation trick
        });

        // Start sequence
        like.classList.add('slide-in-left');
        
        setTimeout(() => {
            share.classList.add('slide-in-right');
        }, 600);

        setTimeout(() => {
            follow.classList.add('slide-in-left');
        }, 1200);
    }

    // Run on load
    window.addEventListener('load', startAnimation);
    
    // Loop every 6 seconds
    setInterval(startAnimation, 6000);
	</script>
	</div>
	
	<div class="kc37">
		<div class="kc38">
			<img src="kci_image/img13.png">
			<h2>Contact:</h2>
			<h3>+234 806 497 9241</h3>
			<h4>+234 901 319 4092</h4>
		</div>
		<div class="kc39">
			<h2>Location:</h2>
			<h3>NO 6 Beside Jumbo Close Off Ogboso Road Obaema, Oyigbo, Rivers State, Nigeria </h3>
		</div>
		<div class="kc40">
			<h2>Stay Connected</h2>
			<h3>You can follow us by clicking the link above, to get the latest updates and post from our facebook page.</h3>
		</div>
	</div>
	<div class="kc41">
		<p>©2026 - Kingdomite Church International | All Rights Reserved</p>
	</div>
</body>
</html>