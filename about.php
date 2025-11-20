<?php 
session_start(); 
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About DevVest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 120px 20px;
            text-align: center;
        }
        .about-hero h1 { font-size: 48px; margin-bottom: 15px; }
        .about-hero .highlight { font-size: 24px; font-weight: 300; opacity: 0.9; color: #000; }
        .about-hero-desc { max-width: 800px; margin: 25px auto; font-size: 18px; opacity: 0.95; line-height: 1.7; }

        .about-mission {
            padding: 90px 20px;
            text-align: center;
            background: #f8fafc;
        }
        .about-mission h2 { font-size: 36px; color: #1e293b; margin-bottom: 20px; }

        .about-works {
            padding: 90px 20px;
            background: #fff;
        }
        .works-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .work-card {
            display: flex;
            align-items: center;
            gap: 25px;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .work-card:hover { transform: translateY(-8px); }
        .work-card.reverse { flex-direction: row-reverse; }
        .work-icon { font-size: 55px; color: #3b4bff; }
        .work-text h3 { margin: 0 0 12px 0; color: #1e293b; font-size: 22px; }
        .work-text p { margin: 0; color: #64748b; line-height: 1.6; }
    </style>
</head>
<body>

<!-- Header (Navbar) -->
<?php include 'header.php'; ?>

<!-- About Hero Section -->
<section class="about-hero">
    <div class="container">
        <h1>About DevVest</h1>
        <p class="highlight">Empowering Innovation. Building Tomorrow.</p>
        <p class="about-hero-desc">
            DevVest is a powerful platform that connects talented developers with smart investors to turn groundbreaking IT ideas into successful real-world projects — securely, transparently, and efficiently.
        </p>
    </div>
</section>

<!-- Our Mission -->
<section class="about-mission">
    <div class="container">
        <h2>Our Mission</h2>
        <p style="max-width:900px; margin:30px auto 0; font-size:18px; line-height:1.8; color:#475569;">
            To empower developers by giving them a trusted space to showcase their ideas and secure funding, while enabling investors to discover and support the next big innovations in technology with full confidence and transparency.
        </p>
    </div>
</section>

<!-- How DevVest Works -->
<section class="about-works">
    <div class="container">
        <h2 style="text-align:center; margin-bottom:60px; font-size:36px; color:#1e293b;">How DevVest Works</h2>
        <div class="works-grid">

            <div class="work-card">
                <div class="work-icon"><i class="fa-solid fa-lightbulb"></i></div>
                <div class="work-text">
                    <h3>For Developers</h3>
                    <p>Submit your innovative IT projects, gain visibility, attract funding, and turn your ideas into reality with investor support.</p>
                </div>
            </div>

            <div class="work-card reverse">
                <div class="work-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div class="work-text">
                    <h3>For Investors</h3>
                    <p>Browse verified projects, invest in ideas you believe in, track progress in real-time, and grow with emerging tech ventures.</p>
                </div>
            </div>

            <div class="work-card">
                <div class="work-icon"><i class="fa-solid fa-handshake"></i></div>
                <div class="work-text">
                    <h3>Secure Collaboration</h3>
                    <p>Safe transactions, milestone-based funding, and direct communication ensure trust and success for both parties.</p>
                </div>
            </div>

            <div class="work-card reverse">
                <div class="work-icon"><i class="fa-solid fa-rocket"></i></div>
                <div class="work-text">
                    <h3>Launch the Future</h3>
                    <p>Together, developers build and investors fuel — creating tomorrow’s technology today.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Footer (Connected from footer.php) -->
<?php include 'footer.php'; ?>

</body>
</html>