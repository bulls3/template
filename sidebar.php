<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index.php" class="brand-link">
      <span class="brand-text font-weight-light ml-3"><i class="fas fa-boxes"></i> Inventory</span>
    </a>
    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <i class="fas fa-user-circle fa-2x text-light ml-2"></i>
        </div>
        <div class="info">
          <a href="#" class="d-block"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></a>
        </div>
      </div>
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <li class="nav-item">
            <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="products.php" class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-box"></i>
              <p>Products</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="categories.php" class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-tags"></i>
              <p>Categories</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="suppliers.php" class="nav-link <?= $currentPage === 'suppliers.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-truck"></i>
              <p>Suppliers</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="stock.php" class="nav-link <?= $currentPage === 'stock.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-exchange-alt"></i>
              <p>Stock Movements</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>
