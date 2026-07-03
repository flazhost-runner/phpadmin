<?php

/**
 * Auth — Forgot password / OTP request view (inner content only).
 *
 * Expected variables:
 *   $setting  array<string,mixed>|null
 *   $flash    array{success?: string, error?: string}
 *   $errors   array<string,string>
 *   $_csrf    string
 */

declare(strict_types=1);

$_settingArr   = is_array($setting ?? null) ? $setting : (is_object($setting ?? null) ? (array)$setting : []);
$_logo         = (string)($_settingArr['logo']        ?? '');
$_loginImage   = (string)($_settingArr['login_image'] ?? '');
$_flashError   = (string)(($flash ?? [])['error']     ?? '');
$_flashSuccess = (string)(($flash ?? [])['success']   ?? '');
$__errors      = $errors ?? [];
$__emailErr    = (string)($__errors['email'] ?? '');
?>
<div class="w-full max-w-5xl tw-card overflow-hidden grid md:grid-cols-2">

    <!-- Visual panel -->
    <div class="hidden md:flex sidebar-gradient items-center justify-center p-10">
        <img class="max-w-full max-h-80 object-contain"
             src="<?= e($_loginImage !== '' ? $_loginImage : '/media/setting/login-image.png') ?>"
             alt="">
    </div>

    <!-- Form panel -->
    <div class="p-8 md:p-12 flex flex-col justify-center">

        <!-- Logo -->
        <div class="mb-8 text-center">
            <img class="h-14 mx-auto object-contain"
                 src="<?= e($_logo !== '' ? $_logo : '/media/setting/logo.png') ?>"
                 alt="logo">
        </div>

        <!-- Flash messages -->
        <?php if ($_flashError !== '') : ?>
            <div class="alert alert-danger"><?= e($_flashError) ?></div>
        <?php endif; ?>
        <?php if ($_flashSuccess !== '') : ?>
            <div class="alert alert-success"><?= e($_flashSuccess) ?></div>
        <?php endif; ?>

        <!-- Heading -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color:var(--primary)">Forgot Password</h1>
            <p class="text-sm text-gray-500">Enter your Email to continue</p>
        </div>

        <!-- Request OTP form -->
        <form method="POST" action="<?= route('admin.v1.auth.reset.request') ?>">
            <input type="hidden" name="_csrf" value="<?= e($_csrf ?? '') ?>">

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input id="email"
                       type="email"
                       class="form-control<?= $__emailErr !== '' ? ' is-invalid' : '' ?>"
                       name="email"
                       value="<?= old('email') ?>"
                       autocomplete="email">
                <?php if ($__emailErr !== '') : ?>
                    <div class="invalid-feedback"><?= e($__emailErr) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary-tw w-100 py-2 mb-3">Send OTP</button>
        </form>

        <hr class="my-4">
        <div class="text-center small">
            <a class="text-primary-tw text-decoration-none"
               href="<?= route('web.auth.login') ?>">back?</a>
        </div>

    </div>
</div>
