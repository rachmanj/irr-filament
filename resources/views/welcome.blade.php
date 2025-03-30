<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IRR Next Gen</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 2rem;
            position: relative;
            z-index: 10;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -1px;
            background: linear-gradient(to right, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 1.25rem;
            font-weight: 300;
            margin-bottom: 3rem;
            color: #94a3b8;
            max-width: 600px;
        }

        .button {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            background: linear-gradient(to right, #2563eb, #7c3aed);
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 1;
        }

        .background-gradient {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(30, 64, 175, 0.15), transparent 70%),
                radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.1), transparent 70%);
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.3;
            animation: float 15s infinite ease-in-out;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) translateX(0);
            }

            25% {
                transform: translateY(-30px) translateX(30px);
            }

            50% {
                transform: translateY(-15px) translateX(-20px);
            }

            75% {
                transform: translateY(30px) translateX(25px);
            }
        }

        .footer {
            position: absolute;
            bottom: 2rem;
            font-size: 0.875rem;
            color: #64748b;
            z-index: 10;
        }
    </style>
</head>

<body>
    <div class="background">
        <div class="background-gradient"></div>
        <div class="particles" id="particles">
            <!-- Particles will be added by JS -->
        </div>
    </div>

    <div class="container">
        <div class="logo">IRR Next Gen</div>
        <div class="subtitle">Welcome to IRR Next Gen, a Document Delivery System designed for streamlined document
            management and efficient workflow processes.</div>
        <a href="/admin" class="button">Go to Dashboard</a>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} IRR Next Gen. All rights reserved.
    </div>

    <script>
        // Add animated particles for a dynamic background
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const colors = ['#3b82f6', '#8b5cf6', '#6366f1', '#0ea5e9', '#6d28d9'];
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');

                // Random position
                const top = Math.random() * 100;
                const left = Math.random() * 100;

                // Random size between 5px and 20px
                const size = Math.random() * 15 + 5;

                // Random color
                const color = colors[Math.floor(Math.random() * colors.length)];

                // Random animation delay
                const delay = Math.random() * 5;

                // Random animation duration between 10s and 20s
                const duration = Math.random() * 10 + 10;

                // Apply styles
                particle.style.top = `${top}%`;
                particle.style.left = `${left}%`;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.backgroundColor = color;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;

                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>

</html>
