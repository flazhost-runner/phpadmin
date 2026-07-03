<?php

/**
 * Auth — Reset password (OTP verification) view (inner content only).
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

function _rstErr(string $f, array $e): string
{
    return (string)($e[$f] ?? '');
}
function _rstInv(string $f, array $e): string
{
    return isset($e[$f]) && $e[$f] !== '' ? ' is-invalid' : '';
}
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
            <h1 class="text-2xl font-bold" style="color:var(--primary)">Reset Password</h1>
            <p class="text-sm text-gray-500">Enter Your New Password</p>
        </div>

        <!-- Reset password form -->
        <form method="POST" action="<?= route('admin.v1.auth.reset.process') ?>">
            <input type="hidden" name="_csrf" value="<?= e($_csrf ?? '') ?>">

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input id="email"
                       type="email"
                       class="form-control<?= _rstInv('email', $__errors) ?>"
                       name="email"
                       value="<?= old('email') ?>"
                       autocomplete="email">
                <?php if (_rstErr('email', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_rstErr('email', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="otp" class="form-label fw-semibold">OTP</label>
                <input id="otp"
                       type="text"
                       class="form-control<?= _rstInv('otp', $__errors) ?>"
                       name="otp"
                       value="<?= old('otp') ?>"
                       autocomplete="one-time-code">
                <?php if (_rstErr('otp', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_rstErr('otp', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input id="password"
                       type="password"
                       class="form-control<?= _rstInv('password', $__errors) ?>"
                       name="password"
                       autocomplete="new-password">
                <?php if (_rstErr('password', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_rstErr('password', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label fw-semibold">Password Confirm</label>
                <input id="password_confirmation"
                       type="password"
                       class="form-control<?= _rstInv('password_confirmation', $__errors) ?>"
                       name="password_confirmation"
                       autocomplete="new-password">
                <?php if (_rstErr('password_confirmation', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_rstErr('password_confirmation', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary-tw w-100 py-2 mb-3">Reset Password</button>
        </form>

        <hr class="my-4">
        <div class="text-center small">
            <a class="text-primary-tw text-decoration-none"
               href="<?= route('web.auth.login') ?>">back?</a>
        </div>

    </div>
</div>
