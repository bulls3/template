<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Suppliers';
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    try {
        if ($action === 'create') {
            if ($name === '') {
                throw new Exception('Supplier name is required.');
            }
            $stmt = $db->prepare('INSERT INTO suppliers (name, email, phone, address) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $name,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
            ]);
            $message = 'Supplier created successfully.';
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0 || $name === '') {
                throw new Exception('Invalid supplier data.');
            }
            $stmt = $db->prepare('UPDATE suppliers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
            $stmt->execute([
                $name,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                $id,
            ]);
            $message = 'Supplier updated successfully.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM suppliers WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Supplier deleted successfully.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$suppliers = $db->query('
    SELECT s.*, COUNT(p.id) AS product_count
    FROM suppliers s
    LEFT JOIN products p ON p.supplier_id = s.id
    GROUP BY s.id
    ORDER BY s.name
')->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editSupplier = null;
if ($editId > 0) {
    $stmt = $db->prepare('SELECT * FROM suppliers WHERE id = ?');
    $stmt->execute([$editId]);
    $editSupplier = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0">Suppliers</h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Suppliers</li>
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
                <h3 class="card-title"><?= $editSupplier ? 'Edit Supplier' : 'Add Supplier' ?></h3>
              </div>
              <form method="post">
                <div class="card-body">
                  <input type="hidden" name="action" value="<?= $editSupplier ? 'update' : 'create' ?>">
                  <?php if ($editSupplier): ?>
                    <input type="hidden" name="id" value="<?= (int) $editSupplier['id'] ?>">
                  <?php endif; ?>
                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($editSupplier['name'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($editSupplier['email'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($editSupplier['phone'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($editSupplier['address'] ?? '') ?></textarea>
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary"><?= $editSupplier ? 'Update' : 'Save' ?></button>
                  <?php if ($editSupplier): ?>
                    <a href="suppliers.php" class="btn btn-default">Cancel</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </div>

          <div class="col-md-8">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">All Suppliers</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Products</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($suppliers as $s): ?>
                      <tr>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                        <td><span class="badge badge-info"><?= (int) $s['product_count'] ?></span></td>
                        <td>
                          <a href="suppliers.php?edit=<?= (int) $s['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                          <form method="post" class="d-inline" onsubmit="return confirm('Delete this supplier?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
