<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "guestwifi_db");
    if ($conn->connect_error) {
        $_SESSION['toast'] = "<div class='alert alert-danger'>❌ Database connection failed</div>";
        header("Location: register_form.php");
        exit;
    }
    $conn->set_charset("utf8mb4");

    $device_type = $_POST['device_type'];
    $first_name = htmlspecialchars($_POST['first_name'], ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars($_POST['last_name'], ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $company = htmlspecialchars($_POST['company'], ENT_QUOTES, 'UTF-8');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['toast'] = "<div class='alert alert-danger'>📧 อีเมลไม่ถูกต้อง</div>";
    } else {
        $username = substr(bin2hex(random_bytes(4)), 0, 6);
        $password = substr(bin2hex(random_bytes(4)), 0, 6);
        $start_time = date("Y-m-d H:i:s");
        $expire_time = date("Y-m-d H:i:s", strtotime("+8 hours"));

        $stmt = $conn->prepare("INSERT INTO guest_users 
            (device_type, first_name, last_name, email, company, start_time, expire_time, username, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $device_type, $first_name, $last_name, $email, $company, $start_time, $expire_time, $username, $password);

        if ($stmt->execute()) {
            $_SESSION['toast'] = "<div class='alert alert-success alert-dismissible fade show text-center'>
                ✅ ลงทะเเบียเรียบร้อย<br>
                <strong>Username:</strong> $username<br>
                <strong>Password:</strong> $password<br>
                <small>ใช้งานได้ถึงเวลา: " . date("H:i", strtotime($expire_time)) . "</small>
            </div>";
        } else {
            $_SESSION['toast'] = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
        }

        $stmt->close();
        $conn->close();
    }
    header("Location: register_form.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guest Registration</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
  <style>
    .main-sidebar:hover { width: 250px !important; transition: width 0.3s ease; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse">
<div class="wrapper">

<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content pt-4">
      <div class="container-fluid">
        <?php if (!empty($_SESSION['toast'])): ?>
          <div class="container">
            <?= $_SESSION['toast']; unset($_SESSION['toast']); ?>
          </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header bg-success text-white">Guest WiFi Registration</div>
          <div class="card-body">
            <form method="POST">
              <div class="row">
                <div class="mb-3 col-md-6">
                  <label>Device Type</label>
                  <select name="device_type" class="form-select" required>
                    <option value="">-- Type --</option>
                    <option value="Mobile Phone">Mobile Phone</option>
                    <option value="Handheld">Handheld</option>
                    <option value="Computer">Computer</option>
                    <option value="Laptop">Laptop</option>
                    <option value="Tablet">Tablet</option>
                  </select>
                </div>
                <div class="mb-3 col-md-6">
                  <label>First Name</label>
                  <input type="text" name="first_name" class="form-control" placeholder="ชื่อจริง" required>
                </div>
                <div class="mb-3 col-md-6">
                  <label>Last Name</label>
                  <input type="text" name="last_name" class="form-control" placeholder="นามสกุล" required>
                </div>
                <div class="mb-3 col-md-6">
                  <label>Email</label>
                  <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                </div>
                <div class="mb-3 col-md-6">
                  <label>Company</label>
                  <input type="text" name="company" class="form-control" placeholder="บริษัท/หน่วยงาน" required>
                </div>
              </div>
              <button type="submit" class="btn btn-success w-100">Register</button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<script>
  // auto-dismiss alert in 5 sec
  setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) alert.remove();
  }, 5000);
</script>
</body>
</html>