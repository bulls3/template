<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Dashboard';
$db = getDB();

$totalProducts   = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalCategories = (int) $db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalSuppliers  = (int) $db->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
$lowStock        = (int) $db->query('SELECT COUNT(*) FROM products WHERE quantity <= min_stock')->fetchColumn();
$totalValue      = (float) $db->query('SELECT COALESCE(SUM(price * quantity), 0) FROM products')->fetchColumn();

$recentMovements = $db->query("
    SELECT sm.*, p.name AS product_name, u.name AS user_name
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    ORDER BY sm.created_at DESC
    LIMIT 8
")->fetchAll();

$lowStockItems = $db->query("
    SELECT p.name, p.sku, p.quantity, p.min_stock, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.quantity <= p.min_stock
    ORDER BY p.quantity ASC
    LIMIT 8
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?= $totalProducts ?></h3>
                <p>Products</p>
              </div>
              <div class="icon"><i class="fas fa-box"></i></div>
              <a href="products.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?= $totalCategories ?></h3>
                <p>Categories</p>
              </div>
              <div class="icon"><i class="fas fa-tags"></i></div>
              <a href="categories.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?= $totalSuppliers ?></h3>
                <p>Suppliers</p>
              </div>
              <div class="icon"><i class="fas fa-truck"></i></div>
              <a href="suppliers.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h3><?= $lowStock ?></h3>
                <p>Low Stock Items</p>
              </div>
              <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
              <a href="products.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-4">
            <div class="info-box">
              <span class="info-box-icon bg-primary"><i class="fas fa-dollar-sign"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Inventory Value</span>
                <span class="info-box-number">$<?= number_format($totalValue, 2) ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header border-transparent">
                <h3 class="card-title">Low Stock Alerts</h3>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table m-0">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Min</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!$lowStockItems): ?>
                        <tr><td colspan="4" class="text-center text-muted">No low stock items</td></tr>
                      <?php else: foreach ($lowStockItems as $item): ?>
                        <tr>
                          <td><?= htmlspecialchars($item['name']) ?></td>
                          <td><?= htmlspecialchars($item['category_name']) ?></td>
                          <td><span class="badge badge-danger"><?= (int) $item['quantity'] ?></span></td>
                          <td><?= (int) $item['min_stock'] ?></td>
                        </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card">
              <div class="card-header border-transparent">
                <h3 class="card-title">Recent Stock Movements</h3>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table m-0">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>By</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!$recentMovements): ?>
                        <tr><td colspan="4" class="text-center text-muted">No movements yet</td></tr>
                      <?php else: foreach ($recentMovements as $m): ?>
                        <tr>
                          <td><?= htmlspecialchars($m['product_name']) ?></td>
                          <td>
                            <?php if ($m['type'] === 'in'): ?>
                              <span class="badge badge-success">IN</span>
                            <?php else: ?>
                              <span class="badge badge-danger">OUT</span>
                            <?php endif; ?>
                          </td>
                          <td><?= (int) $m['quantity'] ?></td>
                          <td><?= htmlspecialchars($m['user_name']) ?></td>
                        </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer clearfix">
                <a href="stock.php" class="btn btn-sm btn-info float-right">View All</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
