<?php
// รับค่าที่ Mikrotik ส่งมา
$username = $_GET['username'] ?? '';
$ip       = $_GET['ip'] ?? '';
$mac      = $_GET['mac'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Guest WiFi - Status</title>
  <style>
    /* Reset & Font */
    body, html { margin:0; padding:0; height:100%; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    /* Background Gradient */
    body {
      display: flex; justify-content: center; align-items: center;
      background: linear-gradient(135deg, #6a11cb, #2575fc);
      min-height: 100vh;
    }

    /* Card */
    .card {
      background: #fff;
      padding: 40px 30px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      max-width: 500px;
      width: 90%;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    /* Decorative circle glow */
    .card::before {
      content:''; position:absolute;
      width:300px; height:300px;
      background: rgba(255,255,255,0.1);
      border-radius:50%;
      top:-100px; left:-100px;
      filter: blur(100px);
    }

    h1 { color: #2575fc; font-size: 26px; margin-bottom: 25px; text-shadow: 1px 1px 5px rgba(0,0,0,0.2); }
    p { font-size: 16px; margin: 10px 0; color: #333; }
    strong { color: #0066cc; }

    /* Uptime & Bytes */
    #uptime, #bytesIn, #bytesOut { font-weight: bold; color: #ff4d4d; }

    /* Logout Button */
    .logout-btn {
      display: inline-block;
      margin-top: 25px;
      padding: 12px 25px;
      background: #ff4d4d;
      color: #fff;
      font-weight: bold;
      text-decoration: none;
      border-radius: 50px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }
    .logout-btn:hover {
      background: #cc0000;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }

    /* Responsive */
    @media(max-width: 576px){
      .card { padding: 30px 20px; }
      h1 { font-size: 22px; }
      p { font-size: 14px; }
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>🎉 Welcome to the Guest WiFi Network</h1>
    <p>Name: <strong id="username"><?= htmlspecialchars($username) ?></strong></p>
    <p>IP Address: <strong id="ip"><?= htmlspecialchars($ip) ?></strong></p>
    <p>MAC Address: <strong id="mac"><?= htmlspecialchars($mac) ?></strong></p>
    <p>Uptime: <strong id="uptime">--:--:--</strong></p>
    <p>Byte-in: <strong id="bytesIn">0</strong></p>
    <p>Byte-out: <strong id="bytesOut">0</strong></p>

    <!-- Logout ชี้ไป logout.php พร้อมส่ง username -->
    <a class="logout-btn" href="logout.php?username=<?= urlencode($username) ?>">Logout</a>
  </div>

  <script>
    async function fetchStatus() {
      let user = document.getElementById("username").textContent;
      try {
        let res = await fetch("status_api.php?username=" + encodeURIComponent(user));
        let data = await res.json();

        if (data.error) { console.log("❌", data.error); return; }

        document.getElementById("uptime").textContent  = data.uptime;
        document.getElementById("bytesIn").textContent = formatBytes(data.bytes_in);
        document.getElementById("bytesOut").textContent= formatBytes(data.bytes_out);

      } catch (e) { console.error("Fetch error:", e); }
    }

    function formatBytes(bytes) {
      bytes = parseInt(bytes);
      const units = ['B', 'KB', 'MB', 'GB', 'TB'];
      let i = 0;
      while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
      return bytes.toFixed(2) + " " + units[i];
    }

    // Update every 5 seconds
    setInterval(fetchStatus, 60000);
    fetchStatus();
  </script>
</body>
</html>
