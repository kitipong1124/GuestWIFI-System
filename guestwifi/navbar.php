<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// โหลดรูปโปรไฟล์
$filename = basename($_SESSION['admin_profile'] ?? 'default.jpg');
$profileImage = 'uploads/' . $filename;
if (!file_exists($profileImage)) {
    $profileImage = 'uploads/default.jpg';
}

// โหลดครั้งแรกสำหรับ pendingCount (ใช้ตอน page load)
$pendingCount = 0;
try {
    $pdo = new PDO("mysql:host=localhost;dbname=guestwifi_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT COUNT(*) FROM guest_users WHERE approved = 0");
    $pendingCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $pendingCount = 0;
}
?>
<nav class="main-header navbar navbar-expand navbar-dark bg-dark">
  <style>
    .user-image {
      width: 24px;
      height: 24px;
      object-fit: cover;
      border-radius: 50%;
    }
    .navbar-marquee {
      color: #fff;
      font-weight: bold;
      overflow: hidden;
      white-space: nowrap;
      position: relative;
      flex: 1;
    }
    .navbar-marquee span {
      display: inline-block;
      padding-left: 100%;
      animation: marquee 40s linear infinite;
    }
    @keyframes marquee {
      0%   { transform: translateX(0); }
      100% { transform: translateX(-100%); }
    }
    .nav-icon {
      font-size: 1.2rem;
      position: relative;
    }
    .badge-notify {
      position: absolute;
      top: -5px;
      right: -5px;
      font-size: 0.7rem;
    }
  </style>

  <!-- ข้อความเลื่อน -->
  <div class="navbar-marquee">
    <span>Welcome to the Guest WiFi System // Powered by Mikrotik & PHP // Made by Siwakorn & Natthaphon // Upgrade by Kitipong & Sukrit</span>
  </div>

  <ul class="navbar-nav ml-auto">
    <!-- Bell Notification -->
    <li class="nav-item dropdown">
      <a class="nav-link" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell nav-icon"></i>
        <?php if ($pendingCount > 0): ?>
          <span class="badge badge-danger badge-notify"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown">
        <li class="dropdown-header"><?= $pendingCount > 0 ? $pendingCount . " Pending Approvals" : "No Pending Approvals" ?></li>
        <li><a class="dropdown-item" href="admin.php?view=pending">Details</a></li>
      </ul>
    </li>

    <!-- Profile Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="<?= $profileImage ?>" class="user-image me-2" alt="Admin Profile">
        <span class="ml-2"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
    <li><a class="dropdown-item" href="change_profile.php">Change Profile</a></li>
    <li><a class="dropdown-item" href="change_password.php">Change Password</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
  </ul>
    </li>
  </ul>
</nav>

<!-- ✅ JS: Auto Refresh Pending Count -->
<script>
function updatePendingCount() {
  fetch("get_pending_count.php")
    .then(response => response.json())
    .then(data => {
      const badge = document.querySelector(".badge-notify");
      const header = document.querySelector("#notifDropdown + ul .dropdown-header");

      if (data.count > 0) {
        if (!badge) {
          const newBadge = document.createElement("span");
          newBadge.className = "badge badge-danger badge-notify";
          newBadge.textContent = data.count;
          document.querySelector("#notifDropdown").appendChild(newBadge);
        } else {
          badge.textContent = data.count;
        }
        if (header) header.textContent = data.count + " Pending Approvals";
      } else {
        if (badge) badge.remove();
        if (header) header.textContent = "No Pending Approvals";
      }
    })
    .catch(err => console.error("Error fetching pending count:", err));
}

// โหลดทันทีเมื่อเปิดหน้า
updatePendingCount();

// รีเฟรชทุก 10 วินาที
setInterval(updatePendingCount, 10000);
</script>
