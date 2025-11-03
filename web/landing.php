<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage Platform - Transform Your Displays</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dsp-blue': '#3498DB',
                        'dsp-green': '#5CB85C',
                        'dsp-red': '#E74C3C'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #3498DB 0%, #5CB85C 50%, #E74C3C 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .rgb-border {
            position: relative;
            overflow: hidden;
        }
        
        .rgb-border::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #3498DB, #5CB85C, #E74C3C);
            animation: slide 3s linear infinite;
        }
        
        @keyframes slide {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .feature-card {
            backdrop-filter: blur(10px);
            background: rgba(31, 41, 55, 0.8);
        }
        
        .hero-bg {
            background: radial-gradient(circle at 20% 50%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(92, 184, 92, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 20%, rgba(231, 76, 60, 0.1) 0%, transparent 50%);
        }
    </style>
</head>
<body class="bg-gray-900 text-white overflow-x-hidden">
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-black bg-opacity-90 backdrop-blur-md border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <img src="assets/images/logo.svg" alt="DSP Logo" class="h-10 w-auto">
                    <span class="ml-3 text-xl font-bold gradient-text">Digital Signage Platform</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="?page=login" class="text-gray-300 hover:text-white px-4 py-2 rounded-md text-sm font-medium transition">Login</a>
                    <a href="?page=register" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Get Started Free</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center hero-bg pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <div class="hero-content">
                <h1 class="text-6xl md:text-7xl lg:text-8xl font-black mb-6 leading-tight">
                    Transform Your
                    <span class="gradient-text block">Digital Displays</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-400 mb-12 max-w-3xl mx-auto leading-relaxed">
                    Manage content, create stunning playlists, and control screens from anywhere. 
                    The modern way to power your digital signage.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                    <a href="?page=register" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:from-blue-600 hover:to-blue-700 transition shadow-2xl transform hover:scale-105">
                        Start Free Today
                    </a>
                    <a href="#features" class="bg-gray-800 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-700 transition border border-gray-700">
                        Learn More
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-8 max-w-3xl mx-auto stats-grid">
                    <div class="stat-item">
                        <div class="text-4xl font-black gradient-text">100%</div>
                        <div class="text-gray-400 text-sm mt-2">Free to Start</div>
                    </div>
                    <div class="stat-item">
                        <div class="text-4xl font-black gradient-text">∞</div>
                        <div class="text-gray-400 text-sm mt-2">Unlimited Screens</div>
                    </div>
                    <div class="stat-item">
                        <div class="text-4xl font-black gradient-text">24/7</div>
                        <div class="text-gray-400 text-sm mt-2">Always Online</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll indicator -->
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 scroll-indicator">
            <div class="w-6 h-10 border-2 border-gray-600 rounded-full flex justify-center">
                <div class="w-1 h-3 bg-gradient-to-b from-dsp-blue to-transparent rounded-full mt-2 animate-bounce"></div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 section-header">
                <h2 class="text-5xl font-black mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-400">Everything you need to manage digital signage</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card p-8 rounded-2xl border border-gray-700 hover:border-dsp-blue transition transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">📺</div>
                    <h3 class="text-2xl font-bold mb-3">Screen Management</h3>
                    <p class="text-gray-400">Create and manage unlimited screens with unique device keys. Monitor status in real-time.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card p-8 rounded-2xl border border-gray-700 hover:border-dsp-green transition transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">🎨</div>
                    <h3 class="text-2xl font-bold mb-3">Content Library</h3>
                    <p class="text-gray-400">Upload images and videos. Organize your media in one central location.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card p-8 rounded-2xl border border-gray-700 hover:border-dsp-red transition transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">📋</div>
                    <h3 class="text-2xl font-bold mb-3">Smart Playlists</h3>
                    <p class="text-gray-400">Create dynamic playlists with drag-and-drop. Set custom durations and transitions.</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="feature-card p-8 rounded-2xl border border-gray-700 hover:border-dsp-blue transition transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">⏰</div>
                    <h3 class="text-2xl font-bold mb-3">Scheduling</h3>
                    <p class="text-gray-400">Automate content changes based on time and date. Perfect for time-sensitive campaigns.</p>
                </div>
                
                <!-- Feature 5 -->
                <div class="feature-card p-8 rounded-2xl border border-gray-700 hover:border-dsp-green transition transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">🔗</div>
                    <h3 class="text-2xl font-bold mb-3">Easy Sharing</h3>
                    <p class="text-gray-400">Generate public links to share playlists. Display on any device with a browser.</p>
                </div>
                
                <!-- Feature 6 -->
                <div class="feature-card p-8 rounded-2xl border border-gray-700 hover:border-dsp-red transition transform hover:-translate-y-2">
                    <div class="text-5xl mb-4">📊</div>
                    <h3 class="text-2xl font-bold mb-3">Analytics Dashboard</h3>
                    <p class="text-gray-400">Track screen status, content performance, and system health at a glance.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 section-header">
                <h2 class="text-5xl font-black mb-4">How It Works</h2>
                <p class="text-xl text-gray-400">Get started in 3 simple steps</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center step-item">
                    <div class="w-20 h-20 bg-gradient-to-br from-dsp-blue to-blue-600 rounded-full flex items-center justify-center text-3xl font-black mx-auto mb-6">1</div>
                    <h3 class="text-2xl font-bold mb-3">Upload Content</h3>
                    <p class="text-gray-400">Add your images and videos to the content library</p>
                </div>
                
                <div class="text-center step-item">
                    <div class="w-20 h-20 bg-gradient-to-br from-dsp-green to-green-600 rounded-full flex items-center justify-center text-3xl font-black mx-auto mb-6">2</div>
                    <h3 class="text-2xl font-bold mb-3">Create Playlists</h3>
                    <p class="text-gray-400">Organize content into playlists with custom timing</p>
                </div>
                
                <div class="text-center step-item">
                    <div class="w-20 h-20 bg-gradient-to-br from-dsp-red to-red-600 rounded-full flex items-center justify-center text-3xl font-black mx-auto mb-6">3</div>
                    <h3 class="text-2xl font-bold mb-3">Display Anywhere</h3>
                    <p class="text-gray-400">Show your content on any screen with a web browser</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 section-header">
                <h2 class="text-5xl font-black mb-4">Simple Pricing</h2>
                <p class="text-xl text-gray-400">Start free, upgrade when you're ready</p>
            </div>
            
            <div class="max-w-lg mx-auto">
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-10 rounded-3xl border-2 border-dsp-blue shadow-2xl pricing-card">
                    <div class="text-center mb-8">
                        <div class="text-6xl font-black gradient-text mb-4">FREE</div>
                        <p class="text-gray-400 text-lg">Currently in beta</p>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-6 h-6 text-dsp-green mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Unlimited screens
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-6 h-6 text-dsp-green mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Unlimited content
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-6 h-6 text-dsp-green mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Unlimited playlists
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-6 h-6 text-dsp-green mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Scheduling features
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-6 h-6 text-dsp-green mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Public sharing links
                        </li>
                    </ul>
                    
                    <a href="?page=register" class="block w-full bg-gradient-to-r from-dsp-blue to-blue-600 text-white text-center px-8 py-4 rounded-xl font-bold text-lg hover:from-blue-600 hover:to-blue-700 transition shadow-xl">
                        Get Started Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-black">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center cta-section">
            <h2 class="text-5xl md:text-6xl font-black mb-6">Ready to Get Started?</h2>
            <p class="text-xl text-gray-400 mb-12">Join now and transform your digital signage experience</p>
            <a href="?page=register" class="inline-block bg-gradient-to-r from-dsp-blue to-blue-600 text-white px-12 py-5 rounded-xl font-bold text-xl hover:from-blue-600 hover:to-blue-700 transition shadow-2xl transform hover:scale-105">
                Create Free Account
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t-4 rgb-border py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-400 text-sm">&copy; <?php echo date('Y'); ?> Digital Signage Platform. All rights reserved.</p>
        </div>
    </footer>

    <!-- GSAP Animations -->
    <script>
        gsap.registerPlugin(ScrollTrigger);
        
        // Hero animations
        gsap.from('.hero-content h1', {
            opacity: 0,
            y: 100,
            duration: 1,
            ease: 'power4.out'
        });
        
        gsap.from('.hero-content p', {
            opacity: 0,
            y: 50,
            duration: 1,
            delay: 0.3,
            ease: 'power4.out'
        });
        
        gsap.from('.hero-content .flex', {
            opacity: 0,
            y: 50,
            duration: 1,
            delay: 0.6,
            ease: 'power4.out'
        });
        
        gsap.from('.stat-item', {
            opacity: 0,
            scale: 0.5,
            duration: 0.8,
            delay: 0.9,
            stagger: 0.2,
            ease: 'back.out(1.7)'
        });
        
        // Section headers
        gsap.utils.toArray('.section-header').forEach(header => {
            gsap.from(header, {
                scrollTrigger: {
                    trigger: header,
                    start: 'top 80%'
                },
                opacity: 0,
                y: 50,
                duration: 1,
                ease: 'power4.out'
            });
        });
        
        // Feature cards
        gsap.utils.toArray('.feature-card').forEach((card, i) => {
            gsap.from(card, {
                scrollTrigger: {
                    trigger: card,
                    start: 'top 85%'
                },
                opacity: 0,
                y: 100,
                rotation: 5,
                duration: 0.8,
                delay: i * 0.1,
                ease: 'power4.out'
            });
        });
        
        // Steps
        gsap.utils.toArray('.step-item').forEach((step, i) => {
            gsap.from(step, {
                scrollTrigger: {
                    trigger: step,
                    start: 'top 85%'
                },
                opacity: 0,
                x: i % 2 === 0 ? -100 : 100,
                duration: 1,
                delay: i * 0.2,
                ease: 'power4.out'
            });
        });
        
        // Pricing card
        gsap.from('.pricing-card', {
            scrollTrigger: {
                trigger: '.pricing-card',
                start: 'top 85%'
            },
            opacity: 0,
            scale: 0.8,
            duration: 1,
            ease: 'back.out(1.7)'
        });
        
        // CTA
        gsap.from('.cta-section', {
            scrollTrigger: {
                trigger: '.cta-section',
                start: 'top 85%'
            },
            opacity: 0,
            y: 100,
            duration: 1,
            ease: 'power4.out'
        });
    </script>
</body>
</html>
