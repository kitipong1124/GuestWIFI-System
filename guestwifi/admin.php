<?php
session_start();
require_once __DIR__ . '/../config.php'; // ✅ เรียกไฟล์ config

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

try {
    // ✅ ใช้ค่า Constant จาก config.php
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ฟังก์ชันสำหรับดึงจำนวนผู้ใช้ card info ยกเว้น Active
function getCardCounts($pdo){
    return [
        'total'   => $pdo->query("SELECT COUNT(*) FROM guest_users")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM guest_users WHERE approved = 0")->fetchColumn(),
        'today'   => $pdo->query("SELECT COUNT(*) FROM guest_users WHERE DATE(start_time) = CURDATE()")->fetchColumn()
    ];
}

$cards = getCardCounts($pdo);

$filter = $_GET['filter'] ?? 'all';

// ฟังก์ชันดึง guest table โดยไม่กำหนด Active เพราะจะใช้ Mikrotik
function getGuests($pdo, $filter){
    $sql = "SELECT * FROM guest_users";
    if($filter === 'pending'){
        $sql .= " WHERE approved = 0";
    } elseif($filter === 'today'){
        $sql .= " WHERE DATE(start_time) = CURDATE()";
    }
    $sql .= " ORDER BY start_time DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$guests = getGuests($pdo, $filter);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin | Guest WiFi</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
.small-text td, .small-text th { font-size: 12px; vertical-align: middle; }
.status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
.active-dot { background-color: green; }
.inactive-dot { background-color: red; }
.info-box .info-box-content { font-size: 14px; }
#clock { font-weight: bold; font-size: 18px; }
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse">
<div class="wrapper">
<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="content-wrapper">
<div class="content-header">
<div class="container-fluid">
<h1 class="m-0">System Overview</h1>
</div>
</div>

<div class="content">
<div class="container-fluid">

<div class="row">
<div class="col-md-6">
<div class="info-box bg-light">
<span class="info-box-icon bg-info elevation-1"><i class="far fa-clock"></i></span>
<div class="info-box-content">
<span class="info-box-text">System Date & Time</span>
<span class="info-box-number" id="clock">--:--:--</span>
</div>
</div>
</div>
<div class="col-md-6">
<div class="info-box bg-light">
<span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-microchip"></i></span>
<div class="info-box-content">
<span class="info-box-text">Board Info</span>
<span class="info-box-number" id="boardInfo">Loading...</span>
</div>
</div>
</div>
</div>

<div class="row mb-3" id="cardRow">
<div class="col-md-3 col-6">
<a href="?filter=all" class="text-white">
<div class="small-box bg-info">
<div class="inner"><h3 id="cardTotal"><?= $cards['total'] ?></h3><p>All Users</p></div>
<div class="icon"><i class="fas fa-users"></i></div>
</div></a>
</div>
<div class="col-md-3 col-6">
<a href="?filter=active" class="text-white">
<div class="small-box bg-success">
<div class="inner"><h3 id="cardActive">0</h3><p>Active</p></div>
<div class="icon"><i class="fas fa-wifi"></i></div>
</div></a>
</div>
<div class="col-md-3 col-6">
<a href="?filter=pending" class="text-white">
<div class="small-box bg-warning">
<div class="inner"><h3 id="cardPending"><?= $cards['pending'] ?></h3><p>Pending</p></div>
<div class="icon"><i class="fas fa-clock"></i></div>
</div></a>
</div>
<div class="col-md-3 col-6">
<a href="?filter=today" class="text-white">
<div class="small-box bg-primary">
<div class="inner"><h3 id="cardToday"><?= $cards['today'] ?></h3><p>Registered Today</p></div>
<div class="icon"><i class="fas fa-calendar-day"></i></div>
</div></a>
</div>
</div>

<div class="table-responsive">
<?php if($filter==='pending'): ?>
<table class="table table-bordered table-hover table-sm text-nowrap small-text" id="pendingTable">
<thead class="thead-dark text-center">
<tr>
<th>First Name</th><th>Last Name</th><th>Email</th><th>Company</th>
<th>Start</th><th>Expire</th><th>User</th><th>Pass</th><th>Status</th><th>Manage</th>
</tr>
</thead>
<tbody>
<?php foreach ($guests as $guest): ?>
<tr data-id="<?= $guest['id'] ?>">
<td><?= htmlspecialchars($guest['first_name']) ?></td>
<td><?= htmlspecialchars($guest['last_name']) ?></td>
<td><?= htmlspecialchars($guest['email']) ?></td>
<td><?= htmlspecialchars($guest['company'] ?? '-') ?></td>
<td><?= $guest['start_time'] ?></td>
<td><?= $guest['expire_time'] ?></td>
<td><?= htmlspecialchars($guest['username']) ?></td>
<td><?= htmlspecialchars($guest['password']) ?></td>
<td><span class="badge bg-warning text-dark">Pending</span></td>
<td>
<a href="approve.php?id=<?= $guest['id'] ?>&action=approve" class="btn btn-sm btn-success manage-btn" data-id="<?= $guest['id'] ?>" data-action="approve">Approved</a>
<a href="approve.php?id=<?= $guest['id'] ?>&action=reject" class="btn btn-sm btn-danger manage-btn" data-id="<?= $guest['id'] ?>" data-action="reject">Rejected</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<table class="table table-bordered table-hover text-nowrap small-text" id="guestTable">
<thead class="thead-dark">
<tr>
<th>Status</th><th>Name</th><th>Email</th><th>Device</th><th>IP</th><th>MAC</th><th>Start</th><th>Expire</th>
</tr>
</thead>
<tbody>
<?php foreach ($guests as $guest): ?>
<tr data-username="<?= htmlspecialchars($guest['username']) ?>" data-id="<?= $guest['id'] ?>">
<td><span class="status-dot inactive-dot"></span>Loading...</td>
<td><?= htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']) ?></td>
<td><?= htmlspecialchars($guest['email']) ?></td>
<td><?= htmlspecialchars($guest['device_type'] ?? '-') ?></td>
<td><?= htmlspecialchars($guest['ip_address'] ?? '-') ?></td>
<td><?= htmlspecialchars($guest['mac_address'] ?? '-') ?></td>
<td><?= $guest['start_time'] ?></td>
<td><?= $guest['expire_time'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<div class="card mt-4">
<div class="card-header bg-dark text-white"><h5 class="mb-0">Traffic Monitor (Hotspot Users)</h5></div>
<div class="card-body">
<div class="row mb-3">
<div class="col-md-4">
<label for="userSelect">Select user:</label>
<select id="userSelect" class="form-control"><option>Loading users...</option></select>
</div>
</div>
<canvas id="trafficChart" height="80"></canvas>
</div>
</div>

</div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ================= Clock =================
function updateClock(){
  const now = new Date();
  document.getElementById("clock").innerText = now.toLocaleTimeString('th-TH',{hour12:false})+" | "+now.toLocaleDateString('th-TH');
}
setInterval(updateClock,1000);
updateClock();

// ================= Traffic Monitor (Unit: MB) =================
let hotspotUsers = [], selectedIP = null;

let trafficChart = new Chart(document.getElementById('trafficChart'), {
  type: 'line',
  data: { 
    labels: [], 
    datasets: [
        // เปลี่ยนชื่อ Label ให้รู้ว่าเป็น MB
        {label:'Upload (MB)', data:[], borderWidth:1, borderColor: 'rgba(54, 162, 235, 1)', backgroundColor: 'rgba(54, 162, 235, 0.2)'}, 
        {label:'Download (MB)', data:[], borderWidth:1, borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)'}
    ] 
  },
  options: { 
    responsive: true, 
    animation: false, 
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
            // ✅ ส่วนที่ 1: แปลงตัวเลขแกน Y จาก Bytes เป็น MB
            callback: function(value, index, values) {
                // หารด้วย 1024*1024 เพื่อเป็น MB และทศนิยม 2 ตำแหน่ง
                return (value / (1024 * 1024)).toFixed(2) + ' MB';
            }
        }
      }
    },
    plugins: {
        tooltip: {
            callbacks: {
                // ✅ ส่วนที่ 2: แปลงตัวเลขตอนเอาเมาส์ชี้ (Hover) เป็น MB
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += (context.parsed.y / (1024 * 1024)).toFixed(2) + ' MB';
                    }
                    return label;
                }
            }
        }
    }
  }
});

function loadHotspotInfo(){
  $.getJSON('get_hotspot_info.php', function(res){
    $('#boardInfo').text(`Board: ${res.board} | Model: ${res.model} | CPU: ${res.cpu} %`);
    const select = $('#userSelect').empty();
    if(res.hotspot.length > 0){
      hotspotUsers = res.hotspot;
      res.hotspot.forEach(u => select.append(`<option value="${u.address}">${u.user} (${u.address})</option>`));
      selectedIP = select.val(); 
      // fetchTraffic(selectedIP); <--- อย่าลืมฟังก์ชันนี้นะครับ (ในโค้ดที่ส่งมาไม่มี แต่ต้องเรียกใช้)
    } else select.append('<option>No active users</option>');
  });
}

function fetchTraffic(ip){
  if(!ip) return;
  $.getJSON(`traffic.php?ip=${ip}`, function(data){
    const now = new Date().toLocaleTimeString();
    trafficChart.data.labels.push(now);
    trafficChart.data.datasets[0].data.push(data.rx);
    trafficChart.data.datasets[1].data.push(data.tx);
    if(trafficChart.data.labels.length > 20){
      trafficChart.data.labels.shift();
      trafficChart.data.datasets[0].data.shift();
      trafficChart.data.datasets[1].data.shift();
    }
    trafficChart.update();
  });
}

$('#userSelect').on('change', function(){
  selectedIP = $(this).val();
  trafficChart.data.labels = [];
  trafficChart.data.datasets[0].data = [];
  trafficChart.data.datasets[1].data = [];
  trafficChart.update();
});
loadHotspotInfo();
setInterval(()=>fetchTraffic(selectedIP),5000);

// ================= AJAX Refresh Cards และ Guest Status =================
function refreshActiveCard(){
  $.getJSON('fetch_users.php?mode=admin', function(data){
    // Count Active จาก Mikrotik
    let activeCount = data.filter(u => u.active_mikrotik==1).length;
    $('#cardActive').text(activeCount);

    // Update Guest Table Status
    $('#guestTable tbody tr').each(function(){
      const tr = $(this);
      const username = tr.data('username');
      const userData = data.find(u => u.username === username);
      if(!userData) return;
      const isActive = userData.active_mikrotik == 1;
      tr.find('td').eq(0).html(`<span class="status-dot ${isActive?'active-dot':'inactive-dot'}"></span>${isActive?'Active':'Inactive'}`);
      tr.find('td').eq(3).text(userData.device_type || '-');
      tr.find('td').eq(4).text(userData.ip_address || '-');
      tr.find('td').eq(5).text(userData.mac_address || '-');
      tr.find('td').eq(6).text(userData.start_time);
      tr.find('td').eq(7).text(userData.expire_time);
      <?php if($filter==='active'): ?>
      if(!isActive) tr.hide(); else tr.show();
      <?php endif; ?>
    });
  });
}
refreshActiveCard();
setInterval(refreshActiveCard,30000);

function refreshOtherCards(){
  $.getJSON('fetch_cards.php', function(res){
    $('#cardTotal').text(res.total);
    $('#cardPending').text(res.pending);
    $('#cardToday').text(res.today);
  });
}
refreshOtherCards();
setInterval(refreshOtherCards,30000);

// ================= DataTables =================
<?php if($filter==='pending'): ?>
$('#pendingTable').DataTable({ pageLength:10, responsive:true });
<?php else: ?>
$('#guestTable').DataTable({ pageLength:10, responsive:true });
<?php endif; ?>
</script>
</body>
</html>