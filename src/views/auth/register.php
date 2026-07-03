<?php

/**
 * Auth — Register view (inner content only; wrapped by full_width.php layout).
 *
 * Expected variables:
 *   $setting  array<string,mixed>|null
 *   $flash    array{success?: string, error?: string}
 *   $errors   array<string,string>   per-field validation errors
 *   $_csrf    string
 */

declare(strict_types=1);

$_settingArr  = is_array($setting ?? null) ? $setting : (is_object($setting ?? null) ? (array)$setting : []);
$_logo        = (string)($_settingArr['logo']        ?? '');
$_loginImage  = (string)($_settingArr['login_image'] ?? '');
$_flashError  = (string)(($flash   ?? [])['error']   ?? '');
$__errors     = $errors ?? [];

function _regErr(string $field, array $errs): string
{
    return (string)($errs[$field] ?? '');
}
function _regIsInvalid(string $field, array $errs): string
{
    return $errs[$field] ?? '' ? ' is-invalid' : '';
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

        <!-- Flash error -->
        <?php if ($_flashError !== '') : ?>
            <div class="alert alert-danger"><?= e($_flashError) ?></div>
        <?php endif; ?>

        <!-- Heading -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color:var(--primary)">Create Account</h1>
            <p class="text-sm text-gray-500">Fill the form to register</p>
        </div>

        <!-- Register form -->
        <form method="POST" action="<?= route('web.auth.register.post') ?>">
            <input type="hidden" name="_csrf" value="<?= e($_csrf ?? '') ?>">

            <div class="mb-3">
                <label for="code" class="form-label fw-semibold">Code</label>
                <input id="code"
                       type="text"
                       class="form-control<?= _regIsInvalid('code', $__errors) ?>"
                       name="code"
                       value="<?= old('code') ?>"
                       autocomplete="off">
                <?php if (_regErr('code', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_regErr('code', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Name</label>
                <input id="name"
                       type="text"
                       class="form-control<?= _regIsInvalid('name', $__errors) ?>"
                       name="name"
                       value="<?= old('name') ?>"
                       autocomplete="name">
                <?php if (_regErr('name', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_regErr('name', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input id="email"
                       type="email"
                       class="form-control<?= _regIsInvalid('email', $__errors) ?>"
                       name="email"
                       value="<?= old('email') ?>"
                       autocomplete="email">
                <?php if (_regErr('email', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_regErr('email', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input id="password"
                       type="password"
                       class="form-control<?= _regIsInvalid('password', $__errors) ?>"
                       name="password"
                       autocomplete="new-password">
                <?php if (_regErr('password', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_regErr('password', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                <input id="password_confirmation"
                       type="password"
                       class="form-control<?= _regIsInvalid('password_confirmation', $__errors) ?>"
                       name="password_confirmation"
                       autocomplete="new-password">
                <?php if (_regErr('password_confirmation', $__errors) !== '') : ?>
                    <div class="invalid-feedback"><?= e(_regErr('password_confirmation', $__errors)) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary-tw w-100 py-2 mb-3">Create Account</button>
        </form>

        <hr class="my-4">
        <div class="text-center small">
            <a class="text-primary-tw text-decoration-none fw-semibold"
               href="<?= route('web.auth.login') ?>">Already have an account?</a>
        </div>

    </div>
</div>
