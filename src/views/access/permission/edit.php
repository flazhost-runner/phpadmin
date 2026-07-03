<?php

/**
 * Permission edit view.
 *
 * Expected variables:
 *   $data     Permission     the permission being edited
 *   $errors   array<string,string>
 *   $oldInput array<string,mixed>
 *   $_csrf    string
 */

declare(strict_types=1);

/** @var \PHPAdmin\Modules\Access\Models\Permission $data */
$updateUrl = route('admin.v1.access.permission.update', ['id' => $data->id]) . '?_method=PUT';
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800">Permission Management</h1>
</div>

<div class="tw-card p-6">
  <h2 class="text-lg font-bold mb-4" style="color:var(--primary)">Permission Form</h2>
  <form method="POST" action="<?= e($updateUrl) ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label for="name" class="form-label fw-semibold">Name</label>
      <input id="name" type="text"
             class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>"
             name="name" value="<?= e((string)$data->name) ?>">
      <?php if (has_error('name')) :
            ?><div class="invalid-feedback"><?= get_error('name') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="guard_name" class="form-label fw-semibold">Guard</label>
      <select name="guard_name" id="guard_name" class="form-control">
        <option value="web" <?= ($data->guard_name ?? 'web') === 'web' ? 'selected' : '' ?>>web</option>
        <option value="api" <?= ($data->guard_name ?? 'web') === 'api' ? 'selected' : '' ?>>api</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="method" class="form-label fw-semibold">Method</label>
      <select id="method" name="method"
              class="form-control <?= has_error('method') ? 'is-invalid' : '' ?>">
        <option value="">-- Select Method --</option>
        <option value="GET"    <?= ($data->method ?? '') === 'GET'    ? 'selected' : '' ?>>GET</option>
        <option value="POST"   <?= ($data->method ?? '') === 'POST'   ? 'selected' : '' ?>>POST</option>
        <option value="PUT"    <?= ($data->method ?? '') === 'PUT'    ? 'selected' : '' ?>>PUT</option>
        <option value="PATCH"  <?= ($data->method ?? '') === 'PATCH'  ? 'selected' : '' ?>>PATCH</option>
        <option value="DELETE" <?= ($data->method ?? '') === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
      </select>
      <?php if (has_error('method')) :
            ?><div class="invalid-feedback"><?= get_error('method') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="status" class="form-label fw-semibold">Status</label>
      <select name="status" id="status"
              class="form-control <?= has_error('status') ? 'is-invalid' : '' ?>" required>
        <option value="Active"   <?= $data->status === 'Active'   ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= $data->status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      <?php if (has_error('status')) :
            ?><div class="invalid-feedback"><?= get_error('status') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-4">
      <label for="desc" class="form-label fw-semibold">Description</label>
      <input id="desc" type="text"
             class="form-control <?= has_error('desc') ? 'is-invalid' : '' ?>"
             name="desc" value="<?= e((string)($data->desc ?? '')) ?>">
      <?php if (has_error('desc')) :
            ?><div class="invalid-feedback"><?= get_error('desc') ?></div><?php
      endif; ?>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary-tw px-4 py-2"><i class="fas fa-save me-1"></i> Save</button>
      <a href="<?= e(route('admin.v1.access.permission.index')) ?>" class="btn btn-danger px-4 py-2 text-white">Back</a>
    </div>
  </form>
</div>
