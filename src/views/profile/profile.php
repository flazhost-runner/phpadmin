<?php

/**
 * Profile self-service view.
 *
 * Expected variables:
 *   $data      array<string,mixed>   user profile data (code, name, phone, email, timezone, status, picture)
 *   $errors    array<string,string>
 *   $oldInput  array<string,mixed>
 *   $timezones string[]
 *   $_csrf     string
 */

declare(strict_types=1);

/** @var array<string,mixed> $data */
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800">Profile</h1>
</div>

<div class="tw-card p-6">
  <h2 class="text-lg font-bold mb-4" style="color:var(--primary)">User Form</h2>
  <?php
    $updateUrl = route('admin.v1.profile.update') . '?_method=PUT';
    ?>
  <form method="POST" action="<?= e($updateUrl) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- Code (read-only) -->
    <div class="mb-3">
      <label for="code" class="form-label fw-semibold">Code</label>
      <input id="code" type="text"
             class="form-control bg-gray-50 cursor-not-allowed"
             name="code" value="<?= e((string)($data['code'] ?? '')) ?>" readonly>
    </div>

    <!-- Name -->
    <div class="mb-3">
      <label for="name" class="form-label fw-semibold">Name</label>
      <input id="name" type="text"
             class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>"
             name="name" value="<?= e((string)($data['name'] ?? '')) ?>">
      <?php if (has_error('name')) :
            ?><div class="invalid-feedback"><?= get_error('name') ?></div><?php
      endif; ?>
    </div>

    <!-- Phone -->
    <div class="mb-3">
      <label for="phone" class="form-label fw-semibold">Phone Number</label>
      <input id="phone" type="text"
             class="form-control <?= has_error('phone') ? 'is-invalid' : '' ?>"
             name="phone" value="<?= e((string)($data['phone'] ?? '')) ?>">
      <?php if (has_error('phone')) :
            ?><div class="invalid-feedback"><?= get_error('phone') ?></div><?php
      endif; ?>
    </div>

    <!-- Email -->
    <div class="mb-3">
      <label for="email" class="form-label fw-semibold">Email</label>
      <input id="email" type="email"
             class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>"
             name="email" value="<?= e((string)($data['email'] ?? '')) ?>">
      <?php if (has_error('email')) :
            ?><div class="invalid-feedback"><?= get_error('email') ?></div><?php
      endif; ?>
    </div>

    <!-- Timezone -->
    <div class="mb-3">
      <label for="timezone" class="form-label fw-semibold">Timezone</label>
      <select id="timezone" name="timezone"
              class="form-control <?= has_error('timezone') ? 'is-invalid' : '' ?>">
        <?php foreach (($timezones ?? []) as $tz) : ?>
          <option value="<?= e($tz) ?>" <?= (string)($data['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (has_error('timezone')) :
            ?><div class="invalid-feedback"><?= get_error('timezone') ?></div><?php
      endif; ?>
    </div>

    <!-- Password (optional) -->
    <div class="mb-3">
      <label for="password" class="form-label fw-semibold">Password
        <small class="text-gray-400 font-normal">(leave blank to keep current)</small>
      </label>
      <input id="password" type="password"
             class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>"
             name="password" value="" autocomplete="new-password">
      <?php if (has_error('password')) :
            ?><div class="invalid-feedback"><?= get_error('password') ?></div><?php
      endif; ?>
    </div>

    <!-- Confirm Password -->
    <div class="mb-3">
      <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
      <input id="password_confirmation" type="password"
             class="form-control <?= has_error('password_confirmation') ? 'is-invalid' : '' ?>"
             name="password_confirmation" value="" autocomplete="new-password">
      <?php if (has_error('password_confirmation')) :
            ?><div class="invalid-feedback"><?= get_error('password_confirmation') ?></div><?php
      endif; ?>
    </div>

    <!-- Status -->
    <div class="mb-3">
      <label for="status" class="form-label fw-semibold">Status</label>
      <select name="status" id="status"
              class="form-control <?= has_error('status') ? 'is-invalid' : '' ?>" required>
        <option value="Active"   <?= ($data['status'] ?? '') === 'Active'   ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= ($data['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      <?php if (has_error('status')) :
            ?><div class="invalid-feedback"><?= get_error('status') ?></div><?php
      endif; ?>
    </div>

    <!-- Picture -->
    <div class="mb-4">
      <label for="picture" class="form-label fw-semibold">Picture</label>
      <div class="d-flex align-items-center gap-3">
        <img id="picturePreview"
             src="/<?= e((string)($data['picture'] ?? 'img/user-placeholder.png')) ?>"
             width="90" height="90"
             class="rounded border p-1"
             style="object-fit:contain;background:#f8fafc"
             alt="Profile picture">
        <input id="picture" type="file" name="picture" accept="image/*"
               class="form-control <?= has_error('picture') ? 'is-invalid' : '' ?>"
               onchange="previewImage(this, 'picturePreview')">
      </div>
      <?php if (has_error('picture')) :
            ?><div class="text-danger small mt-1"><?= get_error('picture') ?></div><?php
      endif; ?>
    </div>

    <button type="submit" class="btn btn-primary-tw px-4 py-2">
      <i class="fas fa-save me-1"></i> Save
    </button>
  </form>
</div>

<script>
  // Override global previewImage to update the picture preview directly.
  (function () {
    window.previewImage = function (input, previewId) {
      if (!input.files || !input.files[0]) return;
      var f = input.files[0];
      if (!f.type.startsWith('image/')) return;
      var img = document.getElementById(previewId || 'picturePreview');
      if (!img) return;
      var reader = new FileReader();
      reader.onload = function (ev) { img.src = ev.target.result; };
      reader.readAsDataURL(f);
    };
  })();
</script>
