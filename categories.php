<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Categories';
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    try {
        if ($action === 'create') {
            if ($name === '') {
                throw new Exception('Category name is required.');
            }
            $stmt = $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
            $stmt->execute([$name, $description !== '' ? $description : null]);
            $message = 'Category created successfully.';
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0 || $name === '') {
                throw new Exception('Invalid category data.');
            }
            $stmt = $db->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
            $stmt->execute([$name, $description !== '' ? $description : null, $id]);
            $message = 'Category updated successfully.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Category deleted successfully.';
        }
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            $error = 'Cannot delete: category is in use or name already exists.';
        } else {
            $error = 'Database error occurred.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$categories = $db->query('
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
')->fetchAll();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editCategory = null;
if ($editId > 0) {
    $stmt = $db->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0">Categories</h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Categories</li>
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
                <h3 class="card-title"><?= $editCategory ? 'Edit Category' : 'Add Category' ?></h3>
              </div>
              <form method="post">
                <div class="card-body">
                  <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>">
                  <?php if ($editCategory): ?>
                    <input type="hidden" name="id" value="<?= (int) $editCategory['id'] ?>">
                  <?php endif; ?>
                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary"><?= $editCategory ? 'Update' : 'Save' ?></button>
                  <?php if ($editCategory): ?>
                    <a href="categories.php" class="btn btn-default">Cancel</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </div>

          <div class="col-md-8">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">All Categories</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Description</th>
                      <th>Products</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($categories as $cat): ?>
                      <tr>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['description'] ?? '-') ?></td>
                        <td><span class="badge badge-info"><?= (int) $cat['product_count'] ?></span></td>
                        <td>
                          <a href="categories.php?edit=<?= (int) $cat['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                          <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
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
