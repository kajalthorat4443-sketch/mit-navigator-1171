<?php
session_start();
if(isset($_SESSION['user'])){
    if($_SESSION['user']['role'] === 'admin') header("Location: admin.php");
    else header("Location: map.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIT NEVIGATOR - Register</title>
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
        }

        .auth-container {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .teal-glow-btn {
            background-color: var(--accent-teal);
            box-shadow: 0 0 15px var(--accent-teal-glow);
            transition: all 0.3s ease;
        }

        .teal-glow-btn:hover {
            box-shadow: 0 0 25px var(--accent-teal-glow);
            transform: translateY(-2px);
        }

        /* Ambient animated background similar to hero-mesh */
        .ambient-mesh {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 80% 20%, hsla(172, 66%, 41%, 0.15) 0, transparent 50%), 
                radial-gradient(at 20% 80%, hsla(222, 47%, 11%, 1) 0, transparent 50%);
        }
        
        .custom-input {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(51, 65, 85, 0.8);
            transition: all 0.2s;
        }
        
        .custom-input:focus {
            outline: none;
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 1px var(--accent-teal);
        }
    </style>
</head>
<body class="ambient-mesh min-h-screen flex flex-col justify-center items-center p-6 relative overflow-hidden">

    <!-- Decorative Elements -->
    <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-teal-500/10 rounded-full blur-3xl mix-blend-screen pointer-events-none"></div>
    <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl mix-blend-screen pointer-events-none"></div>

    <div class="auth-container rounded-3xl p-10 w-full max-w-md relative z-10">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-teal-500/10 text-teal-500 text-3xl mb-4 border border-teal-500/20">
                <i class="fas fa-fingerprint text-2xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-white tracking-tight">System Entry Point</h2>
            <p class="text-slate-400 mt-2 text-sm">Register a new access profile.</p>
        </div>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form action="api/auth.php?action=register" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-400 mb-2 uppercase tracking-wide text-xs">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-slate-500 text-sm"></i>
                    </div>
                    <input type="email" id="email" name="email" required placeholder="name@campusnav.edu" 
                           class="custom-input text-white w-full py-3.5 pl-11 pr-4 rounded-xl text-sm w-full block">
                </div>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-slate-400 mb-2 uppercase tracking-wide text-xs">Security Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-shield-alt text-slate-500 text-sm"></i>
                    </div>
                    <input type="password" id="password" name="password" required placeholder="••••••••" 
                           class="custom-input text-white w-full py-3.5 pl-11 pr-4 rounded-xl text-sm w-full block">
                </div>
            </div>
            
            <div class="bg-slate-900/50 rounded-lg p-3 border border-slate-800 flex items-start space-x-3">
                <i class="fas fa-info-circle text-teal-500 mt-0.5"></i>
                <p class="text-[11px] text-slate-400 leading-tight">Admin access requires manual clearance from system operations. This form grants standard user telemetry clearance.</p>
            </div>

            <button type="submit" class="teal-glow-btn w-full text-slate-900 font-bold text-base py-4 rounded-xl uppercase tracking-wider h-[56px] flex items-center justify-center">
                Create Profile <i class="fas fa-server ml-2 opacity-70"></i>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-700/50 text-center space-y-4">
            <p class="text-sm text-slate-400">
                Awaiting clearance? 
                <a href="login.php" class="text-teal-400 hover:text-teal-300 font-semibold transition-colors">Authenticate</a>
            </p>
            <a href="index.php" class="inline-flex items-center text-xs text-slate-500 hover:text-slate-300 font-medium transition-colors">
                <i class="fas fa-long-arrow-alt-left mr-2"></i> Return to Website
            </a>
        </div>
    </div>

</body>
</html>
