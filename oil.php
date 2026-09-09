<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Like Share Follow Animation</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
        height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        overflow: hidden;
        font-family: Arial, sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .container {
        text-align: center;
        z-index: 10;
    }

    .container h1 {
        font-size: 3rem;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    /* Floating social icons */
    .social-icon {
        position: absolute;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        opacity: 0;
        transform: scale(0);
        pointer-events: none;
    }

    .like {
        background: #1877f2;
        top: 20%;
        left: -100px;
    }

    .share {
        background: #25D366;
        top: 50%;
        right: -100px;
    }

    .follow {
        background: #E4405F;
        bottom: 20%;
        left: -100px;
    }

    /* Animation classes */
    .slide-in-left {
        animation: slideInLeft 0.8s ease-out forwards, bounce 0.5s 0.8s, slideOutLeft 0.8s 3s forwards;
    }

    .slide-in-right {
        animation: slideInRight 0.8s ease-out forwards, bounce 0.5s 0.8s, slideOutRight 0.8s 3s forwards;
    }

    @keyframes slideInLeft {
        to {
            opacity: 1;
            transform: scale(1) translateX(150px);
        }
    }

    @keyframes slideInRight {
        to {
            opacity: 1;
            transform: scale(1) translateX(-150px);
        }
    }

    @keyframes bounce {
        0%, 100% { transform: scale(1) translateX(150px); }
        50% { transform: scale(1.2) translateX(150px); }
    }

    .share { 
        animation: slideInRight 0.8s ease-out forwards, bounceRight 0.5s 0.8s, slideOutRight 0.8s 3s forwards;
    }

    @keyframes bounceRight {
        0%, 100% { transform: scale(1) translateX(-150px); }
        50% { transform: scale(1.2) translateX(-150px); }
    }

    @keyframes slideOutLeft {
        to {
            opacity: 0;
            transform: scale(0) translateX(-100px);
        }
    }

    @keyframes slideOutRight {
        to {
            opacity: 0;
            transform: scale(0) translateX(100px);
        }
    }

    /* Use images instead of text */
    .social-icon img {
        width: 50px;
        height: 50px;
        object-fit: contain;
    }
</style>
</head>
<body>
    <div class="container">
        <h1>Welcome to My Page</h1>
        <p>Content goes here...</p>
    </div>

    <!-- Like Icon -->
    <div class="social-icon like" id="likeIcon">
        👍
        <!-- Replace with: <img src="like.png" alt="Like"> -->
    </div>

    <!-- Share Icon -->
    <div class="social-icon share" id="shareIcon">
        ↗️
        <!-- Replace with: <img src="share.png" alt="Share"> -->
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
</body>
</html>
background: url("kci_image/img14.jpg");
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    background-attachment: fixed;