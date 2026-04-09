<?php
session_start();
$isLoggedIn = isset($_SESSION['user']);
$dashboardUrl = $isLoggedIn ? ($_SESSION['user']['role'] === 'admin' ? 'admin.php' : 'map.php') : 'login.php';
$btnText = $isLoggedIn ? 'Dashboard' : 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIT NEVIGATOR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --bg-dark: #0f172a;
            --bg-sidebar: #1e293b;
            --accent-teal: #14b8a6;
            --accent-teal-glow: rgba(20, 184, 166, 0.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: #f8fafc;
            scroll-behavior: smooth;
        }

        .glass-dark {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .hero-mesh {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(172, 66%, 41%, 0.15) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(222, 47%, 11%, 1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(172, 66%, 41%, 0.1) 0, transparent 50%);
        }

        .teal-glow-btn {
            background-color: var(--accent-teal);
            box-shadow: 0 0 20px var(--accent-teal-glow);
            transition: all 0.3s ease;
        }

        .teal-glow-btn:hover {
            box-shadow: 0 0 35px var(--accent-teal-glow);
            transform: translateY(-2px);
        }

        .app-mockup {
            border: 8px solid #1e293b;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .sidebar-item-active {
            background: rgba(20, 184, 166, 0.1);
            border-left: 3px solid var(--accent-teal);
            color: var(--accent-teal);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="overflow-x-hidden">

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass-dark">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-teal-500 p-2.5 rounded-xl text-white shadow-lg shadow-teal-500/20">
                        <i class="fas fa-layer-group text-xl"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white uppercase letter-spacing-widest">MIT <span class="text-teal-400 font-light">NEVIGATOR</span></span>
                </div>
                <div class="hidden md:flex space-x-10 text-sm font-medium text-slate-400">
                    <a href="#features" class="hover:text-teal-400 transition-all">Navigation</a>
                    <a href="#about" class="hover:text-teal-400 transition-all">Analytics</a>
                    <a href="#contact" class="hover:text-teal-400 transition-all">Infrastructure</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?= $dashboardUrl ?>" class="text-slate-400 hover:text-white transition-all text-sm font-medium"><?= $btnText ?></a>
                    <button onclick="redirectToCollege()" class="teal-glow-btn text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-all">
                        MIT TOUR
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="pt-40 pb-20 px-6 hero-mesh relative overflow-hidden">
        <div class="max-w-5xl mx-auto text-center relative z-10">
            <div class="inline-flex items-center space-x-2 py-1.5 px-4 rounded-full bg-teal-500/10 border border-teal-500/20 text-teal-400 text-xs font-bold uppercase tracking-widest mb-8">
                <span class="relative flex h-2 w-2 mr-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
                </span>
                Now Mapping Building B
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold mb-8 leading-tight tracking-tight">
                The Interactive <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-400 to-emerald-300">Institute Map System.</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-400 mb-12 max-w-3xl mx-auto leading-relaxed">
                Experience high-fidelity indoor mapping and real-time route optimization. Designed for complex university infrastructures with precision at every floor level.
            </p>
            
            <!-- Central Action Button -->
            <div class="flex flex-col items-center justify-center">
                <button onclick="redirectToCollege()" 
                    class="group relative inline-flex items-center justify-center px-12 py-5 font-bold text-white transition-all duration-300 bg-slate-800 border border-teal-500/30 rounded-2xl hover:bg-slate-700 hover:border-teal-400 shadow-2xl scale-100 hover:scale-105 active:scale-95 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-tr from-teal-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <i class="fas fa-university mr-4 text-teal-400 text-2xl"></i>
                    <span class="text-xl">VISIT OFFICIAL WEBSITE</span>
                </button>
                <div class="mt-6 flex items-center space-x-4 text-slate-500 text-sm">
                    <span><i class="fas fa-check-circle text-teal-500 mr-2"></i>Live Tracking</span>
                    <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                    <span><i class="fas fa-check-circle text-teal-500 mr-2"></i>Multi-Floor Logic</span>
                </div>
            </div>
        </div>

        <!-- Mockup Preview -->
        <div class="max-w-6xl mx-auto mt-24 relative">
            <div class="app-mockup bg-[#0f172a] overflow-hidden flex flex-col h-[500px]">
                <!-- Mockup Header -->
                <div class="h-14 border-b border-slate-800 flex items-center justify-between px-6 bg-[#1e293b]">
                    <div class="flex items-center space-x-4">
                        <div class="flex space-x-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/50"></div>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">http://localhost:8000/building-b/interactive-map</span>
                    </div>
                    <div class="bg-red-950/30 text-red-400 text-[10px] font-bold px-3 py-1 rounded border border-red-900/50 uppercase tracking-tighter">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Emergency Exit
                    </div>
                </div>
                <!-- Mockup Body -->
                <div class="flex flex-1 overflow-hidden">
                    <!-- Mockup Sidebar -->
                    <div class="w-56 bg-[#1e293b] border-r border-slate-800 p-4 space-y-6">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Floors</p>
                            <div class="space-y-1">
                                <div class="px-3 py-2 text-xs rounded-lg text-slate-400 hover:bg-slate-800 transition-colors flex justify-between">Ground <span class="text-slate-600">10</span></div>
                                <div class="px-3 py-2 text-xs rounded-lg text-slate-400 hover:bg-slate-800 transition-colors flex justify-between">1st Floor <span class="text-slate-600">11</span></div>
                                <div class="px-3 py-2 text-xs rounded-lg sidebar-item-active flex justify-between">2nd Floor <span class="text-teal-900 font-bold">11</span></div>
                                <div class="px-3 py-2 text-xs rounded-lg text-slate-400 hover:bg-slate-800 transition-colors flex justify-between">3rd Floor <span class="text-slate-600">9</span></div>
                            </div>
                        </div>
                        <div class="pt-6 border-t border-slate-800">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Route Finder</p>
                            <div class="bg-slate-900 rounded-lg p-2 text-[10px] text-slate-400 mb-2 border border-slate-800">Start: BGF-06 Lab</div>
                            <div class="bg-slate-900 rounded-lg p-2 text-[10px] text-slate-400 border border-slate-800">End: BSF-09 Lab</div>
                        </div>
                    </div>
                    <!-- Mockup Map Area -->
                    <div class="flex-1 bg-slate-950 flex items-center justify-center relative">
                        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#14b8a6 0.5px, transparent 0.5px); background-size: 20px 20px;"></div>
                        <i class="fas fa-compass text-teal-500/20 text-9xl"></i>
                        <div class="absolute bottom-6 right-6 w-48 h-32 bg-slate-900/80 rounded-lg border border-teal-500/20 backdrop-blur p-2">
                            <div class="w-full h-full border border-slate-700 rounded bg-slate-800/50 flex items-center justify-center">
                                <span class="text-[10px] text-slate-500 uppercase font-bold">Overview Map</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Detailed Stats -->
    <section class="py-24 px-6 bg-[#0a0f1d]">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="p-8 rounded-2xl bg-[#1e293b]/30 border border-slate-800">
                <div class="text-teal-500 text-sm font-bold uppercase tracking-widest mb-2">Efficiency</div>
                <div class="text-4xl font-bold text-white mb-2">12ms</div>
                <p class="text-slate-500 text-sm leading-relaxed">Average pathfinding calculation latency.</p>
            </div>
            <div class="p-8 rounded-2xl bg-[#1e293b]/30 border border-slate-800">
                <div class="text-teal-500 text-sm font-bold uppercase tracking-widest mb-2">Coverage</div>
                <div class="text-4xl font-bold text-white mb-2">100%</div>
                <p class="text-slate-500 text-sm leading-relaxed">All accessibility ramps and elevators mapped.</p>
            </div>
            <div class="p-8 rounded-2xl bg-[#1e293b]/30 border border-slate-800">
                <div class="text-teal-500 text-sm font-bold uppercase tracking-widest mb-2">Scale</div>
                <div class="text-4xl font-bold text-white mb-2">2.4k</div>
                <p class="text-slate-500 text-sm leading-relaxed">Unique POIs across the university network.</p>
            </div>
            <div class="p-8 rounded-2xl bg-[#1e293b]/30 border border-slate-800">
                <div class="text-teal-500 text-sm font-bold uppercase tracking-widest mb-2">Uptime</div>
                <div class="text-4xl font-bold text-white mb-2">99.9%</div>
                <p class="text-slate-500 text-sm leading-relaxed">Reliability for the central navigation node.</p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-32 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-teal-400 font-bold uppercase tracking-widest text-sm mb-4">The Infrastructure</h2>
                    <h3 class="text-4xl md:text-5xl font-bold text-white mb-4">Built for Campus Scale.</h3>
                    <p class="text-slate-400 text-lg">Every building, every room, every hallway—digitized into a unified navigation ecosystem.</p>
                </div>
                <a href="#" class="text-teal-400 font-bold hover:text-teal-300 transition-colors flex items-center">
                    Explore Documentation <i class="fas fa-arrow-right ml-2 text-sm"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="group p-10 rounded-3xl bg-[#1e293b]/20 border border-slate-800 hover:border-teal-500/50 transition-all duration-300">
                    <div class="w-16 h-16 bg-teal-500/10 rounded-2xl flex items-center justify-center text-teal-500 mb-8 group-hover:bg-teal-500 group-hover:text-white transition-all">
                        <i class="fas fa-map-marked-alt text-2xl"></i>
                    </div>
                    <h4 class="text-2xl font-bold mb-4 text-white">Floor-Wise Breakdown</h4>
                    <p class="text-slate-400 leading-relaxed">Toggle between ground, first, and specialized labs with a single click. High-contrast floor indicators ensure clarity.</p>
                </div>

                <!-- Feature 2 -->
                <div class="group p-10 rounded-3xl bg-[#1e293b]/20 border border-slate-800 hover:border-teal-500/50 transition-all duration-300">
                    <div class="w-16 h-16 bg-teal-500/10 rounded-2xl flex items-center justify-center text-teal-500 mb-8 group-hover:bg-teal-500 group-hover:text-white transition-all">
                        <i class="fas fa-bolt text-2xl"></i>
                    </div>
                    <h4 class="text-2xl font-bold mb-4 text-white">Quick-Action Search</h4>
                    <p class="text-slate-400 leading-relaxed">Instantly locate rooms using a powerful global search. Integrated with the central campus database for real-time accuracy.</p>
                </div>

                <!-- Feature 3 -->
                <div class="group p-10 rounded-3xl bg-[#1e293b]/20 border border-slate-800 hover:border-teal-500/50 transition-all duration-300">
                    <div class="w-16 h-16 bg-teal-500/10 rounded-2xl flex items-center justify-center text-teal-500 mb-8 group-hover:bg-teal-500 group-hover:text-white transition-all">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <h4 class="text-2xl font-bold mb-4 text-white">Safety Protocols</h4>
                    <p class="text-slate-400 leading-relaxed">Dedicated emergency exit identification and hazardous lab warnings integrated directly into the map UI.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#0a0f1d] py-20 px-6 border-t border-slate-800">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="bg-teal-500 p-2 rounded-lg text-white">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tighter uppercase">MIT NEVIGATOR</span>
                    </div>
                    <p class="text-slate-500 max-w-sm mb-6">The leading infrastructure for campus navigation and spatial analytics. Transforming the student experience through precision design.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-full border border-slate-800 flex items-center justify-center text-slate-500 hover:border-teal-500 hover:text-teal-500 transition-all"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full border border-slate-800 flex items-center justify-center text-slate-500 hover:border-teal-500 hover:text-teal-500 transition-all"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div>
                    <h5 class="text-white font-bold mb-6">Product</h5>
                    <ul class="space-y-4 text-sm text-slate-500">
                        <li><a href="#" class="hover:text-teal-400 transition-colors">Map Engine</a></li>
                        <li><a href="#" class="hover:text-teal-400 transition-colors">Admin Dashboard</a></li>
                        <li><a href="#" class="hover:text-teal-400 transition-colors">POI Database</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-white font-bold mb-6">Support</h5>
                    <ul class="space-y-4 text-sm text-slate-500">
                        <li><a href="#" class="hover:text-teal-400 transition-colors">Documentation</a></li>
                        <li><a href="#" class="hover:text-teal-400 transition-colors">API Keys</a></li>
                        <li><a href="#" class="hover:text-teal-400 transition-colors">System Status</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-12 border-t border-slate-900 flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-slate-600 text-xs">© 2026 MIT NEVIGATOR Engineering. All rights reserved.</p>
                <div class="flex space-x-8 text-xs text-slate-600">
                    <a href="#" class="hover:text-slate-400">Privacy Policy</a>
                    <a href="#" class="hover:text-slate-400">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Custom Redirect Modal -->
    <div id="redirectModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
        <div class="bg-slate-900 rounded-3xl p-10 max-w-md w-full text-center shadow-[0_0_50px_rgba(0,0,0,0.5)] border border-slate-800 scale-95 transition-all duration-300">
            <div class="w-24 h-24 bg-teal-500/10 rounded-full flex items-center justify-center text-teal-500 text-5xl mx-auto mb-8 border border-teal-500/20">
                <i class="fas fa-door-open"></i>
            </div>
            <h3 class="text-3xl font-bold text-white mb-4">External Portal</h3>
            <p class="text-slate-400 mb-10 leading-relaxed">Establishing connection to the university central gateway. Proceed to the campus portal?</p>
            <div class="flex flex-col space-y-3">
                <button onclick="confirmRedirect()" class="w-full py-4 rounded-xl bg-teal-500 text-slate-950 font-bold hover:bg-teal-400 shadow-xl shadow-teal-500/10 transition-all">Proceed to Gateway</button>
                <button onclick="closeModal()" class="w-full py-4 rounded-xl border border-slate-800 font-semibold text-slate-500 hover:bg-slate-800 transition-all">Cancel Request</button>
            </div>
        </div>
    </div>

    <script>
        function redirectToCollege() {
            window.location.href = "https://mityeola.com";
        }
        // Intersection Observer for scroll animations
        const observerOptions = { threshold: 0.1 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100', 'translate-y-0');
                    entry.target.classList.remove('opacity-0', 'translate-y-10');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.group').forEach(el => {
            el.classList.add('opacity-0', 'translate-y-10', 'transition-all', 'duration-700');
            observer.observe(el);
        });
    </script>
</body>
</html>