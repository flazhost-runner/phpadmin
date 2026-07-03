<?php

/**
 * User create view.
 *
 * Expected variables:
 *   $errors   array<string,string>
 *   $oldInput array<string,mixed>
 *   $roles    list<Role>
 *   $timezones string[]
 *   $_csrf    string
 */

declare(strict_types=1);

?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
</div>

<div class="tw-card p-6">
  <h2 class="text-lg font-bold mb-4" style="color:var(--primary)">User Form</h2>
  <form method="POST" action="<?= e(route('admin.v1.access.user.store')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label for="code" class="form-label fw-semibold">Code</label>
      <input id="code" type="text"
             class="form-control <?= has_error('code') ? 'is-invalid' : '' ?>"
             name="code" value="<?= old('code') ?>">
      <?php if (has_error('code')) :
            ?><div class="invalid-feedback"><?= get_error('code') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="name" class="form-label fw-semibold">Name</label>
      <input id="name" type="text"
             class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>"
             name="name" value="<?= old('name') ?>">
      <?php if (has_error('name')) :
            ?><div class="invalid-feedback"><?= get_error('name') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="phone" class="form-label fw-semibold">Phone Number</label>
      <input id="phone" type="text"
             class="form-control <?= has_error('phone') ? 'is-invalid' : '' ?>"
             name="phone" value="<?= old('phone') ?>">
      <?php if (has_error('phone')) :
            ?><div class="invalid-feedback"><?= get_error('phone') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="email" class="form-label fw-semibold">Email</label>
      <input id="email" type="email"
             class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>"
             name="email" value="<?= old('email') ?>">
      <?php if (has_error('email')) :
            ?><div class="invalid-feedback"><?= get_error('email') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="timezone" class="form-label fw-semibold">Timezone</label>
      <select id="timezone" name="timezone"
              class="form-control <?= has_error('timezone') ? 'is-invalid' : '' ?>">
        <?php foreach (($timezones ?? []) as $tz) : ?>
          <option value="<?= e($tz) ?>" <?= old('timezone', 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (has_error('timezone')) :
            ?><div class="invalid-feedback"><?= get_error('timezone') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label fw-semibold">Password</label>
      <input id="password" type="password"
             class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>"
             name="password" value="">
      <?php if (has_error('password')) :
            ?><div class="invalid-feedback"><?= get_error('password') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="password_confirmation" class="form-label fw-semibold">Password Confirm</label>
      <input id="password_confirmation" type="password"
             class="form-control <?= has_error('password_confirmation') ? 'is-invalid' : '' ?>"
             name="password_confirmation" value="">
      <?php if (has_error('password_confirmation')) :
            ?><div class="invalid-feedback"><?= get_error('password_confirmation') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="status" class="form-label fw-semibold">Status</label>
      <select name="status" id="status"
              class="form-control <?= has_error('status') ? 'is-invalid' : '' ?>" required>
        <option value="Active"   <?= old('status', 'Active') === 'Active'   ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= old('status', 'Active') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      <?php if (has_error('status')) :
            ?><div class="invalid-feedback"><?= get_error('status') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label for="picture" class="form-label fw-semibold">Picture</label>
      <div class="preview mb-3" id="preview"><p class="text-muted">No image selected</p></div>
      <input id="picture" type="file"
             class="form-control <?= has_error('picture') ? 'is-invalid' : '' ?>"
             name="picture" accept="image/*"
             onchange="previewImage(this, 'img-preview')">
      <?php if (has_error('picture')) :
            ?><div class="text-danger small mt-1"><?= get_error('picture') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold d-block">Blocked</label>
      <div class="d-flex flex-wrap gap-3 p-2 rounded border">
        <label class="d-flex align-items-center gap-2">
          <input id="blocked" type="checkbox" name="blocked" value="1" class="w-4 h-4">
          <span>Blokir akun</span>
        </label>
      </div>
    </div>

    <div class="mb-3" id="div_blocked_reason" style="display:none">
      <label for="blocked_reason" class="form-label fw-semibold">Blocked Reason</label>
      <input id="blocked_reason" type="text"
             class="form-control <?= has_error('blocked_reason') ? 'is-invalid' : '' ?>"
             name="blocked_reason" value="">
      <?php if (has_error('blocked_reason')) :
            ?><div class="invalid-feedback"><?= get_error('blocked_reason') ?></div><?php
      endif; ?>
    </div>

    <div class="mb-4">
      <label class="form-label fw-semibold d-block">Role</label>
      <div class="d-flex flex-wrap gap-3 p-2 rounded border <?= has_error('roles') ? 'border-danger' : '' ?>">
        <?php foreach (($roles ?? []) as $i => $role) : ?>
          <label class="d-flex align-items-center gap-2">
            <input id="roles<?= $i ?>" type="checkbox" name="roles[]"
                   value="<?= e($role->id) ?>" class="w-4 h-4">
            <span><?= e($role->name) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary-tw px-4 py-2"><i class="fas fa-save me-1"></i> Save</button>
      <a href="<?= e(route('admin.v1.access.user.index')) ?>" class="btn btn-danger px-4 py-2 text-white">Back</a>
    </div>
  </form>
</div>

<script>
  (function () {
    var blocked = document.getElementById('blocked');
    var divReason = document.getElementById('div_blocked_reason');
    if (blocked && divReason) {
      blocked.addEventListener('change', function () {
        divReason.style.display = this.checked ? '' : 'none';
        if (!this.checked) {
          var r = document.getElementById('blocked_reason');
          if (r) r.value = '';
        }
      });
    }

    // Override global previewImage to target #preview div
    window._origPreviewImage = window.previewImage;
    window.previewImage = function (input) {
      var preview = document.getElementById('preview');
      if (!preview || !input.files || !input.files[0]) return;
      preview.innerHTML = '';
      var reader = new FileReader();
      reader.onload = function (e) {
        var img = document.createElement('img');
        img.src = e.target.result;
        img.style.maxWidth = '160px';
        img.className = 'rounded border p-1';
        preview.appendChild(img);
      };
      reader.readAsDataURL(input.files[0]);
    };
  })();
</script>
