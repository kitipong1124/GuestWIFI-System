<?php
// 📂 GuestWIFI/config.php

// ------------------------------
// 1. Database Configuration
// ------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'guestwifi_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ------------------------------
// 2. Mikrotik Router Configuration
// ------------------------------
define('ROUTER_IP', '172.16.123.254');
define('ROUTER_USER', 'admin');
define('ROUTER_PASS', '1234');
define('ROUTER_PORT', 8728);

// ------------------------------
// 3. Email Configuration
// ------------------------------
define('MAIL_HOST', 'smtp.gmail.com');   
define('MAIL_PORT', 587);                    
define('MAIL_USER', 'guestwifi.rjm1234@gmail.com'); 
define('MAIL_PASS', 'wnyb seif fqzm uxwr');     
define('MAIL_FROM', 'guestwifi.rjm1234@gmail.com'); 
define('MAIL_FROM_NAME', 'Guest WiFi System');     
define('MAIL_ADMIN_ADDRESS', 'naktub.1124@gmail.com'); 

// ------------------------------
// 4. System Settings (URL for Login/Redirect)
// ------------------------------
define('BASE_URL', 'http://172.16.123.30/GUESTWIFI/'); 
?>
