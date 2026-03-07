
<h2>User Management</h2>

<form method="post" action="/users/store" class="form-inline">
<input name="name" placeholder="Name">
<input name="username" placeholder="Username">
<input name="pin" placeholder="PIN">
<select name="role">
<option value="admin">Admin</option>
<option value="cashier">Cashier</option>
</select>
<button>Create</button>
</form>

<table class="table">
<tr><th>Name</th><th>Role</th><th>Status</th><th></th></tr>
<?php foreach($users as $u): ?>
<tr>
<td><?= $u['name'] ?></td>
<td><?= $u['role'] ?></td>
<td><?= $u['is_active'] ? 'Active':'Inactive' ?></td>
<td>
<a href="/users/toggle?id=<?= $u['id'] ?>">Toggle</a>
</td>
</tr>
<?php endforeach; ?>
</table>
