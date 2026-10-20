<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Stock Movements';
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $type       = $_POST['type'] ?? '';
    $quantity   = (int) ($_POST['quantity'] ?? 0);
    $notes      = trim($_POST['notes'] ?? '');

    try {
        if ($product_id <= 0 || !in_array($type, ['in', 'out'], true) || $quantity <= 0) {
            throw new Exception('Product, type and a positive quantity are required.');
        }

        $db->beginTransaction();

        $stmt = $db->prepare('SELECT id, quantity, name FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception('Product not found.');
        }

        $newQty = $type === 'in'
            ? (int) $product['quantity'] + $quantity
            : (int) $product['quantity'] - $quantity;

        if ($newQty < 0) {
            throw new Exception('Not enough stock for "' . $product['name'] . '". Available: ' . $product['quantity']);
        }

        $upd = $db->prepare('UPDATE products SET quantity = ? WHERE id = ?');
        $upd->execute([$newQty, $product_id]);

        $ins = $db->prepare('
            INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
            VALUES (?, ?, ?, ?, ?)
        ');
        $ins->execute([
            $product_id,
            $_SESSION['user_id'],
            $type,
            $quantity,
            $notes !== '' ? $notes : null,
        ]);

        $db->commit();
        $message = 'Stock movement recorded. New quantity: ' . $newQty;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    }
}

$products = $db->query('SELECT id, name, sku, quantity FROM products ORDER BY name')->fetchAll();

$movements = $db->query('
    SELECT sm.*, p.name AS product_name, p.sku, u.name AS user_name
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    ORDER BY sm.created_at DESC
    LIMIT 50
')->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0">Stock Movements</h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Stock</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="row">
          <div class="col-md-4">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Record Movement</h3>
              </div>
              <form method="post">
                <div class="card-body">
                  <div class="form-group">
                    <label>Product</label>
                    <select name="product_id" class="form-control" required>
                      <option value="">Select product</option>
                      <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>">
                          <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>) — Qty: <?= (int) $p['quantity'] ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control" required>
                      <option value="in">Stock In</option>
                      <option value="out">Stock Out</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="1" required value="1">
                  </div>
                  <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional note"></textarea>
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Save Movement</button>
                </div>
              </form>
            </div>
          </div>

          <div class="col-md-8">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Movement History</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Product</th>
                      <th>Type</th>
                      <th>Qty</th>
                      <th>User</th>
                      <th>Notes</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$movements): ?>
                      <tr><td colspan="6" class="text-center text-muted">No stock movements yet</td></tr>
                    <?php else: foreach ($movements as $m): ?>
                      <tr>
                        <td><?= htmlspecialchars($m['created_at']) ?></td>
                        <td><?= htmlspecialchars($m['product_name']) ?> <small class="text-muted">(<?= htmlspecialchars($m['sku']) ?>)</small></td>
                        <td>
                          <?php if ($m['type'] === 'in'): ?>
                            <span class="badge badge-success">IN</span>
                          <?php else: ?>
                            <span class="badge badge-danger">OUT</span>
                          <?php endif; ?>
                        </td>
                        <td><?= (int) $m['quantity'] ?></td>
                        <td><?= htmlspecialchars($m['user_name']) ?></td>
                        <td><?= htmlspecialchars($m['notes'] ?? '-') ?></td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
