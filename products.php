<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Products';
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $name         = trim($_POST['name'] ?? '');
    $sku          = trim($_POST['sku'] ?? '');
    $category_id  = (int) ($_POST['category_id'] ?? 0);
    $supplier_id  = (int) ($_POST['supplier_id'] ?? 0);
    $description  = trim($_POST['description'] ?? '');
    $price        = (float) ($_POST['price'] ?? 0);
    $quantity     = (int) ($_POST['quantity'] ?? 0);
    $min_stock    = (int) ($_POST['min_stock'] ?? 5);

    try {
        if ($action === 'create') {
            if ($name === '' || $sku === '' || $category_id <= 0) {
                throw new Exception('Name, SKU and category are required.');
            }
            $stmt = $db->prepare('
                INSERT INTO products (category_id, supplier_id, name, sku, description, price, quantity, min_stock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $category_id,
                $supplier_id > 0 ? $supplier_id : null,
                $name,
                $sku,
                $description !== '' ? $description : null,
                $price,
                $quantity,
                $min_stock,
            ]);
            $message = 'Product created successfully.';
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0 || $name === '' || $sku === '' || $category_id <= 0) {
                throw new Exception('Invalid product data.');
            }
            $stmt = $db->prepare('
                UPDATE products
                SET category_id = ?, supplier_id = ?, name = ?, sku = ?, description = ?, price = ?, quantity = ?, min_stock = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $category_id,
                $supplier_id > 0 ? $supplier_id : null,
                $name,
                $sku,
                $description !== '' ? $description : null,
                $price,
                $quantity,
                $min_stock,
                $id,
            ]);
            $message = 'Product updated successfully.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Product deleted successfully.';
        }
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            $error = 'SKU already exists or related data conflict.';
        } else {
            $error = 'Database error occurred.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers  = $db->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

$products = $db->query('
    SELECT p.*, c.name AS category_name, s.name AS supplier_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    ORDER BY p.name
')->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editProduct = null;
if ($editId > 0) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0">Products</h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Products</li>
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
                <h3 class="card-title"><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h3>
              </div>
              <form method="post">
                <div class="card-body">
                  <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
                  <?php if ($editProduct): ?>
                    <input type="hidden" name="id" value="<?= (int) $editProduct['id'] ?>">
                  <?php endif; ?>
                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>SKU</label>
                    <input type="text" name="sku" class="form-control" required
                           value="<?= htmlspecialchars($editProduct['sku'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control" required>
                      <option value="">Select category</option>
                      <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"
                          <?= isset($editProduct['category_id']) && (int) $editProduct['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($c['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-control">
                      <option value="">Optional</option>
                      <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"
                          <?= isset($editProduct['supplier_id']) && (int) $editProduct['supplier_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($s['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Price</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" required
                           value="<?= htmlspecialchars($editProduct['price'] ?? '0') ?>">
                  </div>
                  <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" min="0" name="quantity" class="form-control" required
                           value="<?= htmlspecialchars($editProduct['quantity'] ?? '0') ?>">
                  </div>
                  <div class="form-group">
                    <label>Min Stock</label>
                    <input type="number" min="0" name="min_stock" class="form-control" required
                           value="<?= htmlspecialchars($editProduct['min_stock'] ?? '5') ?>">
                  </div>
                  <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary"><?= $editProduct ? 'Update' : 'Save' ?></button>
                  <?php if ($editProduct): ?>
                    <a href="products.php" class="btn btn-default">Cancel</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </div>

          <div class="col-md-8">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Product List</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>SKU</th>
                      <th>Name</th>
                      <th>Category</th>
                      <th>Supplier</th>
                      <th>Price</th>
                      <th>Qty</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($products as $p): ?>
                      <tr>
                        <td><?= htmlspecialchars($p['sku']) ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category_name']) ?></td>
                        <td><?= htmlspecialchars($p['supplier_name'] ?? '-') ?></td>
                        <td>$<?= number_format((float) $p['price'], 2) ?></td>
                        <td>
                          <?php if ((int) $p['quantity'] <= (int) $p['min_stock']): ?>
                            <span class="badge badge-danger"><?= (int) $p['quantity'] ?></span>
                          <?php else: ?>
                            <span class="badge badge-success"><?= (int) $p['quantity'] ?></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="products.php?edit=<?= (int) $p['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                          <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
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
