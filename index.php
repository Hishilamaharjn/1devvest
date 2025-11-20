<?php   
session_start();
require 'db_connect.php';

// Fetch approved AND featured projects + total funded
$projects = [];
try {
    $stmt = $pdo->query("
        SELECT p.*, COALESCE(SUM(ip.invested_amount), 0) as total_funded
        FROM projects p
        LEFT JOIN investor_projects ip ON p.id = ip.project_id AND ip.status = 'approved'
        WHERE p.status = 'approved' AND p.is_featured = 1
        GROUP BY p.id
        ORDER BY p.id DESC LIMIT 12
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("DB Error: ".$e->getMessage());
    $projects = [];
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DevVest - Innovate. Invest. Impact.</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<style>
body { font-family: 'Poppins', sans-serif; background: #f8f9fc; }

/* ==================== HERO SECTION – NORMAL ZOOM (background zooms with page) ==================== */
.hero {
    height: 92vh;
    min-height: 600px;
    background: url('https://images.unsplash.com/photo-1531482615713-2afd69097998?fit=crop&w=1200&q=80') 
                center center / cover no-repeat;
    /* background-attachment: fixed;   ← REMOVED so everything zooms normally */
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 60px;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.48);
    z-index: 1;
}

.hero-content {
    max-width: 600px;
    position: relative;
    z-index: 2;
}

.hero h1 {
    font-size: 48px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 20px;
}

.hero p {
    font-size: 18px;
    margin-bottom: 30px;
}

.hero-buttons button {
    padding: 13px 28px;
    margin-right: 15px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.hero-buttons .btn-light {
    background: #fff;
    color: #333;
}

.hero-buttons .btn-outline-light {
    background: transparent;
    border: 2px solid #fff !important;
    color: #fff;
}

.hero-buttons button:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
/* =================================================================== */

.section { padding: 70px 20px; }
.section-title { text-align: center; font-size: 2.3rem; margin-bottom: 40px; font-weight: 700; color: #333; }

.search-box {
    max-width: 500px;
    margin: 0 auto 40px;
    position: relative;
}
.search-box input {
    border-radius: 50px;
    padding: 14px 50px 14px 20px;
    font-size: 1.1rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border: none;
    width: 100%;
}
.search-box button {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: #667eea;
    color: white;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 1.2rem;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    max-width: 1300px;
    margin: 0 auto;
}

.project-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}
.project-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}
.project-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}
.card-body {
    padding: 18px;
}
.card-body h3 {
    font-size: 1.25rem;
    margin-bottom: 8px;
    color: #333;
}
.card-body p {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 12px;
    line-height: 1.5;
}
.progress {
    height: 7px;
    background: #e9ecef;
    border-radius: 10px;
    margin-bottom: 10px;
}
.progress-bar {
    background: linear-gradient(90deg, #667eea, #764ba2);
}
.funding-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #555;
}
.funding-info strong { color: #333; }

/* Modal */
.modal-dialog { max-width: 600px; }
.modal-content { border-radius: 18px; border: none; }
.modal-header { border-bottom: none; padding: 20px 25px 0; }
.modal-body { padding: 20px 25px 25px; }
.modal-body img { border-radius: 12px; margin-bottom: 18px; max-height:300px; object-fit:cover; }
.modal-body h2 { font-size: 1.8rem; margin-bottom: 12px; }
.modal-body p { font-size: 1rem; line-height: 1.7; color: #555; }
.detail-info { background: #f8f9fc; padding: 15px; border-radius: 12px; margin: 15px 0; font-size: 0.95rem; }
.detail-info strong { color: #333; }
</style>
</head>
<body>

<?php require_once 'header.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <h1>Empowering Developers.<br> Accelerating Innovation.</h1>
        <p>Support groundbreaking IT projects and help talented developers bring ideas to life.</p>
        <div class="hero-buttons">
            <button class="btn btn-light btn-lg" onclick="location.href='about.php'">Learn More</button>
            <button class="btn btn-outline-light btn-lg" onclick="location.href='register.php'">Get Started</button>
        </div>
    </div>
</section>

<!-- FEATURED PROJECTS -->
<section class="section">
    <h2 class="section-title">Featured Projects</h2>

    <div class="search-box">
        <input type="text" id="searchInput" class="form-control" placeholder="Search projects...">
        <button type="button"><i class="fas fa-search"></i></button>
    </div>

    <div class="projects-grid" id="projectsGrid">
        <?php if(empty($projects)): ?>
            <p class="text-center text-muted fs-4">No featured projects available at the moment.</p>
        <?php else: ?>
            <?php foreach($projects as $p): 
                $db_image = trim($p['image'] ?? '');
                if (!empty($db_image)) {
                    if (file_exists(__DIR__ . '/' . $db_image)) {
                        $img_path = $db_image;
                    } elseif (file_exists(__DIR__ . '/client/images/' . basename($db_image))) {
                        $img_path = 'client/images/' . basename($db_image);
                    } else {
                        $img_path = 'client/images/default_project.png';
                    }
                } else {
                    $img_path = 'client/images/default_project.png';
                }

                $goal = (float)($p['goal'] ?? 0);
                $funded = (float)$p['total_funded'];
                $progress = $goal > 0 ? min(100, round(($funded / $goal) * 100)) : 0;
            ?>
                <div class="project-card" 
                     data-title="<?= htmlspecialchars($p['title']) ?>"
                     data-image="<?= htmlspecialchars($img_path) ?>"
                     data-desc="<?= htmlspecialchars($p['description']) ?>"
                     data-goal="<?= number_format($goal) ?>"
                     data-raised="<?= number_format($funded) ?>"
                     data-percent="<?= $progress ?>"
                     data-category="<?= htmlspecialchars($p['category'] ?? 'N/A') ?>"
                     data-start="<?= date('M d, Y', strtotime($p['start_date'])) ?>"
                     data-end="<?= date('M d, Y', strtotime($p['end_date'])) ?>">
                    <img src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($p['title']) ?></h3>
                        <p><?= htmlspecialchars(substr($p['description'], 0, 90)) ?>...</p>
                        <div class="progress"><div class="progress-bar" style="width: <?= $progress ?>%"></div></div>
                        <div class="funding-info">
                            <span><strong>Rs. <?= number_format($funded) ?></strong> raised</span>
                            <span><?= $progress ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="modalTitle"></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" src="" alt="" class="img-fluid rounded mb-3">
        <p id="modalDesc" class="text-start mb-4"></p>

        <div class="detail-info text-start">
            <p><strong>Goal Amount:</strong> Rs. <span id="modalGoal"></span></p>
            <p><strong>Raised:</strong> Rs. <span id="modalRaised"></span> (<span id="modalPercent"></span>% funded)</p>
            <p><strong>Category:</strong> <span id="modalCategory"></span></p>
            <p><strong>Project Period:</strong> <span id="modalStart"></span> – <span id="modalEnd"></span></p>
        </div>

        <div class="progress mt-3" style="height:10px;">
            <div id="modalProgress" class="progress-bar bg-gradient" style="width:0%;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live Search
document.getElementById('searchInput').addEventListener('input', filterProjects);
document.querySelector('.search-box button').addEventListener('click', () => document.getElementById('searchInput').focus());

function filterProjects() {
    const term = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.project-card').forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        card.style.display = title.includes(term) ? '' : 'none';
    });
}

// Modal
document.querySelectorAll('.project-card').forEach(card => {
    card.addEventListener('click', function() {
        document.getElementById('modalTitle').textContent = this.dataset.title;
        document.getElementById('modalImage').src = this.querySelector('img').src;
        document.getElementById('modalDesc').textContent = this.dataset.desc;
        document.getElementById('modalGoal').textContent = this.dataset.goal;
        document.getElementById('modalRaised').textContent = this.dataset.raised;
        document.getElementById('modalPercent').textContent = this.dataset.percent;
        document.getElementById('modalCategory').textContent = this.dataset.category;
        document.getElementById('modalStart').textContent = this.dataset.start;
        document.getElementById('modalEnd').textContent = this.dataset.end;
        document.getElementById('modalProgress').style.width = this.dataset.percent + '%';

        new bootstrap.Modal(document.getElementById('projectModal')).show();
    });
});
</script>

</body>
</html>