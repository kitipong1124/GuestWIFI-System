<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome RJM</title>
<style>
body { font-family: Arial; background:#f4f4f4; text-align:center; padding-top:50px; }
.box { display:inline-block; background:#fff; padding:40px; border-radius:15px; box-shadow:0 0 10px rgba(0,0,0,0.2); width:300px; }
h1 { color:#2c3e50; }
p { color:#7f8c8d; }
.progress { width:100%; background:#eee; border-radius:10px; margin-top:20px; }
.bar { width:0%; height:15px; background:#007bff; border-radius:10px; transition:width 0.3s; }
</style>
</head>
<body>
<div class="box">
  <h1>ยินดีต้อนรับสู่เครือข่าย RJM</h1>
  <p>Powered by Mikrotik & PHP</p>
  <div class="progress">
    <div class="bar" id="bar"></div>
  </div>
  <p id="status">Logging in...</p>
</div>

<script>
let bar = document.getElementById('bar');
let status = document.getElementById('status');
let width = 0;
let interval = setInterval(()=>{
    if(width >= 100){
        clearInterval(interval);
        status.textContent = "เชื่อมต่อเรียบร้อยแล้ว! คุณสามารถใช้อินเทอร์เน็ตได้ทันที.";
    } else {
        width += 2; // ปรับความเร็วของ progress bar (2% ต่อ 0.1 วินาที → ประมาณ 5 วินาทีเต็ม)
        bar.style.width = width + '%';
    }
}, 100);
</script>
</body>
</html>
