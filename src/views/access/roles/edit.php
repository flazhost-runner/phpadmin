<?php

/**
 * Role edit view.
 *
 * Expected variables:
 *   $data     Role           the role being edited
 *   $errors   array<string,string>
 *   $oldInput array<string,mixed>
 *   $_csrf    string
 */

declare(strict_types=1);

/** @var \PHPAdmin\Modules\Access\Models\Role $data */
$updateUrl = route('admin.v1.access.role.update', ['id' => $data->id]) . '?_method=PUT';
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800">Role Management</h1>
</div>

<div class="tw-card p-6">
  <h2 class="text-lg font-bold mb-4" style="color:var(--primary)">Role Form</h2>
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
      <a href="<?= e(route('admin.v1.access.role.index')) ?>" class="btn btn-danger px-4 py-2 text-white">Back</a>
    </div>
  </form>
</div>
