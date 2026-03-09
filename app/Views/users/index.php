<?php ob_start();

$isEdit = !empty($editUser);
$formAction = $isEdit
  ? '/users/update?id=' . (int)$editUser['id']
  : '/users/store';
  
if (!empty($_SESSION['flash_users'])): ?>
  <?php $flash = $_SESSION['flash_users']; unset($_SESSION['flash_users']); ?>
  <div class="card" style="margin-bottom:12px; border-color: <?= ($flash['type'] ?? '') === 'error' ? '#ef4444' : '#16a34a' ?>;">
    <strong><?= ($flash['type'] ?? '') === 'error' ? 'Error' : 'Success' ?></strong>
    <div style="margin-top:6px;">
      <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
  </div>
<?php endif; ?>
<div class="page-head">
  <div>
    <h1 class="page-title">Users</h1>
    <div class="page-subtitle">Manage cashier, manager, owner, and admin accounts</div>
  </div>
</div>

<div class="card" style="margin-bottom:12px;">
  <h3 class="section-title"><?= $isEdit ? 'Edit User' : 'Add New User' ?></h3>

  <form action="<?= htmlspecialchars($formAction) ?>" method="post" class="form">
    <div class="form-row">
      <div class="field">
        <label>Full Name</label>
        <input
          type="text"
          name="name"
          value="<?= htmlspecialchars($editUser['name'] ?? '') ?>"
          required
        >
      </div>

      <div class="field">
        <label>Username</label>
        <input
          type="text"
          name="username"
          value="<?= htmlspecialchars($editUser['username'] ?? '') ?>"
          required
        >
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label>Role</label>
        <select name="role" required>
          <option value="">Select role</option>
          <option value="cashier" <?= (($editUser['role'] ?? '') === 'cashier') ? 'selected' : '' ?>>Cashier</option>
          <option value="manager" <?= (($editUser['role'] ?? '') === 'manager') ? 'selected' : '' ?>>Manager</option>
          <option value="owner" <?= (($editUser['role'] ?? '') === 'owner') ? 'selected' : '' ?>>Owner</option>
          <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>

      <div class="field">
        <label><?= $isEdit ? 'New PIN / Password (leave blank to keep current)' : 'PIN / Password' ?></label>
        <input
          type="password"
          name="pin"
          <?= $isEdit ? '' : 'required' ?>
          placeholder="Enter PIN or password"
        >
        <div class="muted" style="margin-top:6px;">
            <small>
                Cashier / Manager / Owner usually use numeric PIN. Admin can use a normal password.
            </small>
        </div>
      </div>
    </div>

    <div class="field">
      <label style="display:block; margin-bottom:10px;">Status</label>
      <label style="display:inline-flex; align-items:center; gap:8px;">
        <input
          type="checkbox"
          name="is_active"
          value="1"
          <?= !isset($editUser['is_active']) || (int)$editUser['is_active'] === 1 ? 'checked' : '' ?>
          style="width:auto;"
        >
        Active
      </label>
    </div>

    <div style="margin-top:8px; display:flex; gap:8px;">
      <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update User' : 'Save User' ?></button>
      <?php if ($isEdit): ?>
        <a href="/users" class="btn">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card" style="margin-bottom:12px;">
  <div class="form-row">
    <div class="field">
      <label>Search</label>
      <input type="text" id="userSearch" placeholder="Search name or username...">
    </div>

    <div class="field">
      <label>Role Filter</label>
      <select id="roleFilter">
        <option value="">All Roles</option>
        <option value="cashier">Cashier</option>
        <option value="manager">Manager</option>
        <option value="owner">Owner</option>
        <option value="admin">Admin</option>
        <option value="super_admin">Super Admin</option>
      </select>
    </div>
  </div>
</div>

<div class="card">
  <?php if (empty($users)): ?>
    <div class="muted">No users found.</div>
  <?php else: ?>
    <table class="table" id="usersTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Status</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr
            data-name="<?= htmlspecialchars(strtolower((string)($u['name'] ?? ''))) ?>"
            data-username="<?= htmlspecialchars(strtolower((string)($u['username'] ?? ''))) ?>"
            data-role="<?= htmlspecialchars((string)($u['role'] ?? '')) ?>"
          >
            <td><?= (int)($u['id'] ?? 0) ?></td>
            <td><strong><?= htmlspecialchars($u['name'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
            <td>
              <span class="badge"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($u['role'] ?? '')))) ?></span>
            </td>
            <td>
              <?php if (!empty($u['is_active'])): ?>
                <span class="badge badge-success">Active</span>
              <?php else: ?>
                <span class="badge badge-warn">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="right">
                <a href="/users/edit?id=<?= (int)($u['id'] ?? 0) ?>" class="btn btn-ghost">Edit</a>

                <form action="/users/toggle?id=<?= (int)($u['id'] ?? 0) ?>" method="post" style="display:inline;">
                    <button type="submit" class="btn btn-ghost">
                    <?= !empty($u['is_active']) ? 'Disable' : 'Enable' ?>
                    </button>
                </form>

                <form action="/users/reset-pin?id=<?= (int)($u['id'] ?? 0) ?>" method="post" style="display:inline; margin-left:6px;">
                    <input type="hidden" name="new_pin" value="1234">
                    <button type="submit" class="btn btn-ghost">Reset PIN</button>
                </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script>
const userSearch = document.getElementById('userSearch');
const roleFilter = document.getElementById('roleFilter');
const userRows = document.querySelectorAll('#usersTable tbody tr');

function filterUsers() {
  const q = (userSearch?.value || '').toLowerCase().trim();
  const role = roleFilter?.value || '';

  userRows.forEach(row => {
    const name = row.dataset.name || '';
    const username = row.dataset.username || '';
    const rowRole = row.dataset.role || '';

    const matchSearch = !q || name.includes(q) || username.includes(q);
    const matchRole = !role || rowRole === role;

    row.style.display = (matchSearch && matchRole) ? '' : 'none';
  });
}

userSearch?.addEventListener('input', filterUsers);
roleFilter?.addEventListener('change', filterUsers);
</script>

<?php
$content = ob_get_clean();
$title = 'Users';
require __DIR__ . '/../layouts/main.php';