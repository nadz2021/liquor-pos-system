<?php ob_start(); ?>

<?php
$isEdit = !empty($userForm['id']);
$formAction = $isEdit
  ? '/users/update?id=' . (int)$userForm['id']
  : '/users/store';
?>

<div class="page-head">
  <div>
    <h1 class="page-title"><?= $isEdit ? 'Edit User' : 'Add User' ?></h1>
    <div class="page-subtitle">
      <?= $isEdit ? 'Update staff account details' : 'Create a new staff account' ?>
    </div>
  </div>
  <div class="page-actions">
    <a href="/users" class="btn">Back to Users</a>
  </div>
</div>

<div class="card">
  <form action="<?= htmlspecialchars($formAction) ?>" method="post" class="form">
    <div class="form-row">
      <div class="field">
        <label>Full Name</label>
        <input
          type="text"
          name="name"
          value="<?= htmlspecialchars($userForm['name'] ?? '') ?>"
          required
        >
      </div>

      <div class="field">
        <label>Username</label>
        <input
          type="text"
          name="username"
          value="<?= htmlspecialchars($userForm['username'] ?? '') ?>"
          required
        >
      </div>
    </div>

    <div class="form-row">
      <div class="field">
        <label>Role</label>
        <select name="role" required>
          <option value="">Select role</option>
          <option value="cashier" <?= (($userForm['role'] ?? '') === 'cashier') ? 'selected' : '' ?>>Cashier</option>
          <option value="manager" <?= (($userForm['role'] ?? '') === 'manager') ? 'selected' : '' ?>>Manager</option>
          <option value="owner" <?= (($userForm['role'] ?? '') === 'owner') ? 'selected' : '' ?>>Owner</option>
          <option value="admin" <?= (($userForm['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
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
        <label>Selling Mode</label>
        <select name="selling_mode" required>
            <option value="in_store" <?= (($editUser['selling_mode'] ?? 'in_store') === 'in_store') ? 'selected' : '' ?>>In Store</option>
            <option value="field" <?= (($editUser['selling_mode'] ?? '') === 'field') ? 'selected' : '' ?>>Outside Field</option>
        </select>
    </div>

    <div class="field">
      <label style="display:block; margin-bottom:10px;">Status</label>
      <label style="display:inline-flex; align-items:center; gap:8px;">
        <input
          type="checkbox"
          name="is_active"
          value="1"
          <?= !isset($userForm['is_active']) || (int)$userForm['is_active'] === 1 ? 'checked' : '' ?>
          style="width:auto;"
        >
        Active
      </label>
    </div>

    <div style="display:flex; gap:8px; margin-top:8px;">
      <button type="submit" class="btn btn-primary">Save User</button>
      <a href="/users" class="btn">Cancel</a>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
$title = $isEdit ? 'Edit User' : 'Add User';
require __DIR__ . '/../layouts/main.php';