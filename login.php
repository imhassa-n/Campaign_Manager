<?php

session_start();
include 'db.php';

$error = '';

if(isset($_POST['login']))
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query($conn,"
    SELECT * FROM users
    WHERE email='$email'
    AND password='$password'
    ");

    if(mysqli_num_rows($query) > 0)
    {
        $user_data = mysqli_fetch_assoc($query);
        $_SESSION['user']    = $user_data['email'];
        $_SESSION['role']    = $user_data['role'] ?? 'Admin';
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_name'] = $user_data['name'];

        header("Location: dashboard.php");
        exit;
    }
    else
    {
        $error = "Invalid email or password. Please try again.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WebDex Campaign Manager Pro — Staff Login Portal">
    <title>Sign In — WebDex Campaign Manager</title>

    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --brand-primary: #0cbaba;
            --brand-secondary: #380036;
            --bg-dark: #070B14;
            --glass-bg: rgba(16, 23, 41, 0.55);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-highlight: rgba(255, 255, 255, 0.15);
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-dark);
            position: relative;
            overflow: hidden;
            color: var(--text-main);
        }

        /* Animated Ambient Background */
        .ambient-bg {
            position: absolute;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            z-index: 0;
            top: 0;
            left: 0;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 20s infinite ease-in-out alternate;
        }

        .orb-1 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(12,186,186,0.4) 0%, rgba(0,0,0,0) 70%);
            top: -200px;
            left: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(56,0,54,0.5) 0%, rgba(0,0,0,0) 70%);
            bottom: -150px;
            right: -100px;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0,143,158,0.3) 0%, rgba(0,0,0,0) 70%);
            top: 40%;
            left: 60%;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-40px, 20px) scale(0.9); }
            100% { transform: translate(0, 0) scale(1); }
        }

        /* Glass Grid Overlay */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        /* Login Container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 0 20px;
            perspective: 1000px;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-top: 1px solid var(--glass-highlight);
            border-left: 1px solid var(--glass-highlight);
            border-radius: 28px;
            padding: 48px 40px;
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: cardEntrance 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(40px) rotateX(10deg); }
            to { opacity: 1; transform: translateY(0) rotateX(0); }
        }

        /* Brand Area */
        .brand-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.1s both;
        }

        .brand-logo {
            height: 80px;
            object-fit: contain;
            margin-bottom: 24px;
            filter: brightness(0) invert(1) drop-shadow(0 4px 12px rgba(255,255,255,0.2));
            transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .brand-logo:hover {
            transform: scale(1.05);
        }

        .brand-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            background: linear-gradient(to right, #fff, #a5f3fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-header p {
            font-size: 15px;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Form Area */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .input-group {
            position: relative;
            animation: fadeUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.2s both;
        }

        .input-group:nth-child(2) {
            animation-delay: 0.3s;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .input-control {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-control i.icon-left {
            position: absolute;
            left: 18px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 18px;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .input-control input {
            width: 100%;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 16px 20px 16px 50px;
            color: white;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .input-control input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        /* Fix for browser autofill background */
        .input-control input:-webkit-autofill,
        .input-control input:-webkit-autofill:hover, 
        .input-control input:-webkit-autofill:focus, 
        .input-control input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #0b1120 inset !important;
            -webkit-text-fill-color: white !important;
            caret-color: white;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .input-control input:focus {
            outline: none;
            background: rgba(0, 0, 0, 0.4);
            border-color: rgba(12, 186, 186, 0.5);
            box-shadow: 
                inset 0 2px 4px rgba(0, 0, 0, 0.2),
                0 0 0 4px rgba(12, 186, 186, 0.15);
        }

        .input-control input:focus + i.icon-left,
        .input-control input:focus ~ i.icon-left {
            color: #0cbaba;
            transform: scale(1.1);
        }

        .pass-toggle {
            position: absolute;
            right: 18px;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            transition: color 0.3s ease;
            z-index: 2;
        }

        .pass-toggle:hover {
            color: white;
        }

        /* Submit Button */
        .submit-btn {
            margin-top: 10px;
            width: 100%;
            background: linear-gradient(135deg, #0cbaba 0%, #380036 100%);
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 8px 24px rgba(12, 186, 186, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            animation: fadeUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.4s both;
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            transform: skewX(-20deg);
            transition: all 0.6s ease;
        }

        .submit-btn:hover {
            background-position: right center;
            box-shadow: 0 12px 32px rgba(12, 186, 186, 0.4);
            transform: translateY(-2px);
        }

        .submit-btn:hover::after {
            left: 150%;
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Footer */
        .footer-text {
            text-align: center;
            margin-top: 32px;
            font-size: 13px;
            color: var(--text-muted);
            animation: fadeUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.5s both;
        }

        .footer-text span {
            color: #0cbaba;
            font-weight: 600;
        }

        /* Error Banner */
        .error-banner {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #fca5a5;
            padding: 14px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        .error-banner i {
            color: #ef4444;
            font-size: 20px;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 24px;
                border-radius: 24px;
            }
            .brand-logo {
                height: 65px;
            }
            .brand-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<!-- Ambient Background -->
<div class="ambient-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>
<div class="grid-overlay"></div>

<!-- Login Container -->
<div class="login-wrapper">
    <div class="login-card">
        
        <div class="brand-header">
            <img src="assets/logo.png" alt="WebDex Logo" class="brand-logo">
            <h1>Welcome Back</h1>
            <p>Sign in to WebDex Campaign Manager</p>
        </div>

        <?php if($error): ?>
        <div class="error-banner">
            <i class="bi bi-shield-exclamation"></i>
            <div><?php echo $error; ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            
            <div class="input-group">
                <label for="email">Email Address</label>
                <div class="input-control">
                    <i class="bi bi-envelope-fill icon-left"></i>
                    <input type="email" id="email" name="email" placeholder="name@company.com" required autocomplete="email">
                </div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-control">
                    <i class="bi bi-lock-fill icon-left"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pass-toggle" onclick="togglePassword()" id="toggleBtn">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="submit-btn">
                Sign In <i class="bi bi-arrow-right"></i>
            </button>

        </form>

        <div class="footer-text">
            Secure Access Portal &middot; <span>WebDex</span>
        </div>

    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.querySelector('#toggleBtn i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-fill');
        icon.classList.add('bi-eye-slash-fill');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash-fill');
        icon.classList.add('bi-eye-fill');
    }
}
</script>

</body>
</html>