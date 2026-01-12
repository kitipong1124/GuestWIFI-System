<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="admin.php" class="brand-link">
    <i class="fas fa-wifi brand-image img-circle elevation-3" style="opacity: .8"></i>
    <span class="brand-text font-weight-light">Guest WiFi Admin</span>
  </a>
  <style>
    .brand-link .brand-image {
      float: none;
      margin-left: 1.0rem;
      margin-right: 0.6rem;
    }
  </style>
  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column">
        
        <li class="nav-item">
          <a href="admin.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? ' active' : '' ?>">
            <i class="nav-icon fas fa-home"></i><p>Home</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="dashboard.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? ' active' : '' ?>">
            <i class="nav-icon fas fa-users"></i><p>List</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>