<style>
    /* Dramatic Custom Animations */
    @keyframes dramaticZoomIn {
        0% { transform: scale(0.3) translateY(50px); opacity: 0; filter: blur(10px); }
        70% { transform: scale(1.05) translateY(-10px); opacity: 1; filter: blur(0px); }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    
    @keyframes slideUpFadeIn {
        0% { transform: translateY(40px); opacity: 0; letter-spacing: normal; }
        100% { transform: translateY(0); opacity: 1; letter-spacing: 0.1em; }
    }

    @keyframes slideUpFadeInSecondary {
        0% { transform: translateY(30px); opacity: 0; letter-spacing: normal; }
        100% { transform: translateY(0); opacity: 1; letter-spacing: 0.25em; }
    }

    @keyframes slowPulsePrompt {
        0%, 100% { opacity: 0.4; transform: scale(0.98); }
        50% { opacity: 1; transform: scale(1); }
    }

    /* Animation Classes */
    .animate-logo {
        animation: dramaticZoomIn 1.5s cubic-bezier(0.25, 1, 0.5, 1) forwards;
    }
    .animate-title-main {
        opacity: 0; /* Starts hidden */
        animation: slideUpFadeIn 1.2s cubic-bezier(0.25, 1, 0.5, 1) 0.8s forwards;
    }
    .animate-title-sub {
        opacity: 0; /* Starts hidden */
        animation: slideUpFadeInSecondary 1.2s cubic-bezier(0.25, 1, 0.5, 1) 1.4s forwards;
    }
    .animate-prompt {
        opacity: 0; /* Starts hidden */
        animation: slideUpFadeIn 1s ease-out 2.5s forwards, slowPulsePrompt 3s ease-in-out 3.5s infinite;
    }
</style>

<div id="globalSplashScreen" class="fixed inset-0 w-screen h-screen z-[9999] hidden flex-col items-center justify-center bg-black cursor-pointer overflow-hidden" onclick="hideSplashScreen()">
    
    <div class="absolute inset-0 w-full h-full bg-cover bg-center bg-no-repeat opacity-90 transform scale-105 transition-transform duration-[10000ms] ease-out" id="splashBg" style="background-image: url('img/cover-page.jpg');"></div>
    
    <div class="absolute inset-0 bg-gradient-to-b from-purple-900/40 via-gray-900/60 to-black/90"></div>
    
    <div class="relative z-10 flex flex-col items-center text-center px-6 w-full max-w-5xl" id="splashContent">
        
        <img src="img/purplearmy_logo-removebg.png" alt="Purple Army Logo" class="w-48 md:w-64 lg:w-72 drop-shadow-[0_0_30px_rgba(106,27,154,0.8)] mb-8">
        
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-black text-white mb-2" style="font-family: 'Inter', sans-serif; text-shadow: 0 4px 10px rgba(0,0,0,0.8);">
            PURPLE ARMY
        </h1>
        
        <h2 class="text-xl md:text-3xl lg:text-4xl font-bold text-purple-300 mb-16 uppercase" style="text-shadow: 0 2px 5px rgba(0,0,0,0.9);">
            Consumers Cooperative
        </h2>
        
        <p class="text-gray-300 text-sm md:text-base lg:text-lg font-medium italic drop-shadow-md">
            - click anywhere on the screen to access the system -
        </p>
    </div>
</div>

<script>
    // --- SPLASH SCREEN & INACTIVITY ENGINE ---
    const splashScreen = document.getElementById('globalSplashScreen');
    const splashContent = document.getElementById('splashContent');
    const splashBg = document.getElementById('splashBg');
    
    // Set Inactivity Timeout to 30 Minutes (30 mins * 60 secs * 1000 ms)
    const INACTIVITY_LIMIT = 30 * 60 * 1000; 
    let inactivityTimer;

    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        // Do not restart timer if the splash screen is currently visible
        if (splashScreen && splashScreen.classList.contains('hidden')) {
            inactivityTimer = setTimeout(showSplashScreen, INACTIVITY_LIMIT);
        }
    }

    function showSplashScreen() {
        // Reset the animation classes by briefly removing them
        const logo = splashContent.querySelector('img');
        const h1 = splashContent.querySelector('h1');
        const h2 = splashContent.querySelector('h2');
        const prompt = splashContent.querySelector('p');

        logo.className = "w-48 md:w-64 lg:w-72 drop-shadow-[0_0_30px_rgba(106,27,154,0.8)] mb-8";
        h1.className = "text-5xl md:text-7xl lg:text-8xl font-black text-white mb-2";
        h1.style.textShadow = "0 4px 10px rgba(0,0,0,0.8)";
        h2.className = "text-xl md:text-3xl lg:text-4xl font-bold text-purple-300 mb-16 uppercase";
        h2.style.textShadow = "0 2px 5px rgba(0,0,0,0.9)";
        prompt.className = "text-gray-300 text-sm md:text-base lg:text-lg font-medium italic drop-shadow-md";

        // Trigger reflow to restart animations
        void splashScreen.offsetWidth;

        // Re-apply animation classes
        logo.classList.add('animate-logo');
        h1.classList.add('animate-title-main');
        h2.classList.add('animate-title-sub');
        prompt.classList.add('animate-prompt');

        // Add slow zoom effect to background
        splashBg.classList.remove('scale-105');
        splashBg.classList.add('scale-100');

        // Show the screen
        splashScreen.classList.remove('hidden');
        splashScreen.classList.add('flex');
        
        clearTimeout(inactivityTimer); // Stop timer while screen is showing
    }

    function hideSplashScreen() {
        splashScreen.classList.add('hidden');
        splashScreen.classList.remove('flex');
        
        // Reset background zoom
        splashBg.classList.remove('scale-100');
        splashBg.classList.add('scale-105');

        // Mark as shown for this session
        sessionStorage.setItem('firstLoadSplashShown', 'true');
        
        // Restart the inactivity tracker
        resetInactivityTimer();
    }

    // --- EVENT LISTENERS ---
    
    // Listen for ANY user interaction to reset the 30-minute timer
    window.addEventListener('mousemove', resetInactivityTimer);
    window.addEventListener('mousedown', resetInactivityTimer);
    window.addEventListener('keypress', resetInactivityTimer);
    window.addEventListener('scroll', resetInactivityTimer);
    window.addEventListener('touchstart', resetInactivityTimer);

    // Initialization logic when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        // If it is the user's very first time opening the app this session, show it!
        if (!sessionStorage.getItem('firstLoadSplashShown')) {
            showSplashScreen();
        } else {
            // Otherwise, just silently start the 30-minute countdown
            resetInactivityTimer();
        }
    });
</script>