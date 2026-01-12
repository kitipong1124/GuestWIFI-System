<?php
session_start();
require_once __DIR__ . '/../config.php'; // ✅ เรียกไฟล์ config

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// ✅ ใช้ค่า Constant จาก config.php
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Database connection failed.");
$conn->set_charset(DB_CHARSET);

// กราฟย้อนหลัง 7 วัน
$graphData = [];
$today = new DateTime();
for ($i = 6; $i >= 0; $i--) {
    $day = clone $today;
    $day->modify("-$i days");
    $date = $day->format('Y-m-d');

    $q = $conn->query("SELECT
        SUM(CASE WHEN approved = 0 THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN approved = 2 THEN 1 ELSE 0 END) AS rejected
        FROM guest_users WHERE DATE(start_time) = '$date'");
    $row = $q->fetch_assoc();
    $graphData[] = [
        'date' => $date,
        'pending' => (int)$row['pending'],
        'approved' => (int)$row['approved'],
        'rejected' => (int)$row['rejected'],
    ];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Guest WiFi</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.table-sm td, .table-sm th { font-size: 13px; vertical-align: middle; }
.content-header h1 { font-size: 22px; }
.user-image { width: 20px; height: 20px; object-fit: cover; border-radius: 50%; }
.main-sidebar:hover { width: 250px !important; }
@media (max-width:768px){
  .table-responsive { overflow-x:auto; }
  .content-header h1 { font-size: 18px; }
  .btn-sm { font-size: 11px; padding: .25rem .5rem; }
}
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse">
<div class="wrapper">

<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="content-wrapper">
<div class="content-header">
<div class="container-fluid">
<div class="row mb-2">
<div class="col-sm-6"><h1 class="m-0">Dashboard User Guest WiFi</h1></div>
<div class="col-sm-6 text-right">
<button class="btn btn-outline-primary btn-sm" onclick="toggleView('table')">📋 Table</button>
<button class="btn btn-outline-success btn-sm" onclick="toggleView('chart')">📊 Stats</button>
</div>
</div>

<div class="row mb-2 mt-2 g-2">
    <div class="col-md-3">
        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Search...">
    </div>
    <div class="col-md-2">
        <select id="statusFilter" class="form-control form-control-sm">
            <option value="">All Status</option>
            <option value="0">Pending</option>
            <option value="1">Approved</option>
            <option value="2">Rejected</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" id="startDate" class="form-control form-control-sm" title="Start Date">
    </div>
    <div class="col-md-2">
        <input type="date" id="endDate" class="form-control form-control-sm" title="End Date">
    </div>
    <div class="col-md-1">
        <button class="btn btn-secondary btn-sm w-100" onclick="resetFilters()">Reset</button>
    </div>
</div>

<div id="alertPending" class="alert alert-warning text-center" style="display:none;"></div>
</div>
</div>

<section class="content">
<div class="container-fluid">
<div id="tableView">
<div class="card">
<div class="card-body table-responsive p-0">
<table class="table table-bordered table-hover table-sm text-nowrap">
<thead class="thead-dark text-center">
<tr>
<th>First Name</th><th>Last Name</th><th>Email</th><th>Company</th>
<th>Start</th><th>Expire</th><th>User</th><th>Pass</th>
<th>Status</th><th>Manage</th>
</tr>
</thead>
<tbody id="userTableBody"></tbody>
</table>
</div>
<div class="card-footer d-flex justify-content-between align-items-center">
  <div>
    <ul class="pagination mb-0" id="pagination"></ul>
  </div>
  
  <form method="post" action="export_csv.php">
    <button type="submit" class="btn btn-info btn-sm">Export CSV</button>
    
    </form>
</div>
</div>
</div>

<div id="chartView" style="display:none">
<canvas id="userChart" height="120"></canvas>
</div>
</div>
</section>
</div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3"></div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editUserForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User Info</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="editUserId">
          <div class="mb-2">
            <label>First Name</label>
            <input type="text" name="first_name" id="editFirstName" class="form-control" required>
          </div>
          <div class="mb-2">
            <label>Last Name</label>
            <input type="text" name="last_name" id="editLastName" class="form-control" required>
          </div>
          <div class="mb-2">
            <label>Email</label>
            <input type="email" name="email" id="editEmail" class="form-control" required>
          </div>
          <div class="mb-2">
            <label>Company</label>
            <input type="text" name="company" id="editCompany" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
let currentPage = 1;
const rowsPerPage = 20;
let searchQuery = '';
let statusFilter = '';
let startDate = '';
let endDate = '';
let searchTimeout;

const searchInput = document.getElementById('searchInput');
const statusSelect = document.getElementById('statusFilter');
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');

// ✅ เพิ่ม Event Listener ให้ช่องวันที่
startDateInput.addEventListener('change', () => { startDate = startDateInput.value; currentPage = 1; fetchUserData(); });
endDateInput.addEventListener('change', () => { endDate = endDateInput.value; currentPage = 1; fetchUserData(); });

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchQuery = searchInput.value.trim();
        currentPage = 1;
        fetchUserData();
    }, 500);
});

statusSelect.addEventListener('change', () => {
    statusFilter = statusSelect.value;
    currentPage = 1;
    fetchUserData();
});

// ✅ ฟังก์ชัน Reset Filter
function resetFilters() {
    searchInput.value = '';
    statusSelect.value = '';
    startDateInput.value = '';
    endDateInput.value = '';
    
    searchQuery = '';
    statusFilter = '';
    startDate = '';
    endDate = '';
    currentPage = 1;
    fetchUserData();
}

function toggleView(view) {
  document.getElementById('tableView').style.display = (view==='table')?'block':'none';
  document.getElementById('chartView').style.display = (view==='chart')?'block':'none';
}

function showToast(msg,type="success") {
  let bgClass="bg-success", icon="✅";
  if(type==="error"){bgClass="bg-danger";icon="❌";}
  else if(type==="warning"){bgClass="bg-warning text-dark";icon="⚠️";}
  else if(type==="info"){bgClass="bg-info";icon="ℹ️";}
  const toastEl=document.createElement('div');
  toastEl.className=`toast align-items-center text-white ${bgClass} border-0`;
  toastEl.role='alert';
  toastEl.innerHTML=`<div class="d-flex"><div class="toast-body">${icon} ${msg}</div>
  <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  document.querySelector('.toast-container').appendChild(toastEl);
  new bootstrap.Toast(toastEl,{delay:4000}).show();
}

// ✅ อัปเดตการดึงข้อมูลให้ส่งวันที่ไปด้วย
function fetchUserData() {
  let url = `fetch_users.php?search=${encodeURIComponent(searchQuery)}&status=${statusFilter}&start_date=${startDate}&end_date=${endDate}`;
  fetch(url)
    .then(res => res.json())
    .then(data => {
      displayTable(data);
      createPagination(data.length);
    });
}

// Display Table
function displayTable(data) {
  const tbody = document.getElementById('userTableBody');
  tbody.innerHTML = '';
  const startIndex = (currentPage-1) * rowsPerPage;
  const paginatedData = data.slice(startIndex, startIndex + rowsPerPage);
  let pendingCount = 0;

  paginatedData.forEach(user => {
    let status = '';
    if(user.approved==1) status='<span class="badge bg-success">Approved</span>';
    else if(user.approved==2) status='<span class="badge bg-danger">Rejected</span>';
    else {status='<span class="badge bg-warning text-dark">Pending</span>'; pendingCount++;}

    // ปุ่มจัดการ + Edit
    let manage = '';
    if(user.approved==0){
      manage = `<a href="approve.php?id=${user.id}&action=approve" class="btn btn-success btn-sm mb-1">Approved</a>
                <a href="approve.php?id=${user.id}&action=reject" class="btn btn-danger btn-sm">Rejected</a>`;
    } else {
      manage = `<button class="btn btn-info btn-sm mb-1" onclick="editUser(${user.id}, '${user.first_name}', '${user.last_name}', '${user.email}', '${user.company}')">Edit</button>
                <button class="btn btn-danger btn-sm" 
                   onclick="disconnectUser(${user.id}, '${user.first_name}', '${user.last_name}', '${user.username}', '${user.email}', '${user.company}', this)">
                   Disconnect
                 </button>`;
    }

    const row = document.createElement('tr');

    if(user.disconnected==1){
      row.style.backgroundColor = '#d6d8d9';
    }

    row.innerHTML = `
      <td>${user.first_name}</td>
      <td>${user.last_name}</td>
      <td>${user.email}</td>
      <td>${user.company}</td>
      <td>${user.start_time}</td>
      <td>${user.expire_time}</td>
      <td>${user.username}</td>
      <td>${user.password}</td>
      <td class="text-center">${status}</td>
      <td class="text-center">${manage}</td>
    `;

    if(user.disconnected==1){
      row.querySelectorAll('.btn').forEach(b => b.disabled = true);
    }

    tbody.appendChild(row);
  });

  const alertPending = document.getElementById('alertPending');
  alertPending.style.display = pendingCount>0?'block':'none';
  if(pendingCount>0) alertPending.innerHTML = `<strong>📢 Alert:</strong> Users Pending Approval <span class="badge bg-danger">${pendingCount}</span> list`;
}

// Disconnect User
function disconnectUser(userId, firstName, lastName, username, email, company, btn) {
    const message = `Confirm disconnect this user?\n\n` +
                    `Name: ${firstName} ${lastName}\n` +
                    `Username: ${username}\n` +
                    `Email: ${email}\n` +
                    `Company: ${company}`;
    if(!confirm(message)) return;

    fetch(`disconnect_user.php?id=${userId}`)
      .then(res => res.text())
      .then(msg => {
        showToast(msg, "info");
        const row = btn.closest('tr');
        row.style.backgroundColor = '#d6d8d9';
        row.querySelectorAll('.btn').forEach(b => b.disabled = true);
      })
      .catch(err => showToast("เกิดข้อผิดพลาดในการตัดการเชื่อมต่อ", "error"));
}

// Edit User Functions
function editUser(id, firstName, lastName, email, company) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editFirstName').value = firstName;
    document.getElementById('editLastName').value = lastName;
    document.getElementById('editEmail').value = email;
    document.getElementById('editCompany').value = company;

    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
}

document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('edit_user.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(msg => {
        showToast(msg, 'info');
        fetchUserData();
        bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
    })
    .catch(err => showToast('Error updating user', 'error'));
});

// ✅ Delete History Function (คงเดิมตามที่มีอยู่)
function confirmDeleteHistory() {
    const days = document.getElementById('deleteDays').value;
    
    if(!confirm(`ยืนยันการลบข้อมูลที่เก่ากว่า ${days} วัน? \nการกระทำนี้ไม่สามารถกู้คืนได้!`)) return;

    const modalEl = document.getElementById('deleteHistoryModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();

    fetch('delete_history.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `days=${days}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast(data.message, 'success');
            fetchUserData(); 
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        console.error('Error:', error);
    });
}

// ✅ Pagination
function createPagination(totalItems) {
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    if (totalPages <= 1) return;

    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="javascript:void(0)">Previous</a>`;
    prevLi.addEventListener('click', () => { if(currentPage>1){ currentPage--; fetchUserData(); }});
    pagination.appendChild(prevLi);

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    if(startPage>1){ addPageButton(1); if(startPage>2)addDots(); }
    for(let i=startPage;i<=endPage;i++){ addPageButton(i,i===currentPage); }
    if(endPage<totalPages){ if(endPage<totalPages-1)addDots(); addPageButton(totalPages); }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="javascript:void(0)">Next</a>`;
    nextLi.addEventListener('click', () => { if(currentPage<totalPages){ currentPage++; fetchUserData(); }});
    pagination.appendChild(nextLi);

    function addPageButton(page,active=false){
        const li=document.createElement('li');
        li.className=`page-item ${active?'active':''}`;
        li.innerHTML=`<a class="page-link" href="javascript:void(0)">${page}</a>`;
        li.addEventListener('click',()=>{ currentPage=page; fetchUserData(); });
        pagination.appendChild(li);
    }
    function addDots(){
        const li=document.createElement('li');
        li.className="page-item disabled";
        li.innerHTML=`<span class="page-link">...</span>`;
        pagination.appendChild(li);
    }
}

// Chart
const ctx = document.getElementById('userChart').getContext('2d');
const chartData = <?= json_encode($graphData) ?>;
new Chart(ctx,{
  type:'bar',
  data:{
    labels:chartData.map(d=>d.date),
    datasets:[
      {label:'Approved',data:chartData.map(d=>d.approved),backgroundColor:'rgba(40, 167, 69, 0.7)'},
      {label:'Pending',data:chartData.map(d=>d.pending),backgroundColor:'rgba(255, 193, 7, 0.8)'},
      {label:'Rejected',data:chartData.map(d=>d.rejected),backgroundColor:'rgba(220, 53, 69, 0.8)'}
    ]
  },
  options:{responsive:true,scales:{y:{beginAtZero:true}}}
});

fetchUserData();
</script>
</body>
</html>