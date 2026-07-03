<?php

/**
 * Auth — Login view (inner content only; wrapped by full_width.php layout).
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
$_flashSuccess = (string)(($flash  ?? [])['success']  ?? '');
?>
<div class="w-full max-w-5xl tw-card overflow-hidden grid md:grid-cols-2">

    <!-- Visual panel (sidebar-gradient) -->
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

        <!-- Per-field errors -->
        <?php
        $__errors = $errors ?? [];
        $__emailErr = (string)($__errors['email'] ?? '');
        $__passErr  = (string)($__errors['password'] ?? '');
        if ($__emailErr !== '' || $__passErr !== '') :
            ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php if ($__emailErr !== '') :
                        ?><li><?= e($__emailErr) ?></li><?php
                    endif; ?>
                    <?php if ($__passErr  !== '') :
                        ?><li><?= e($__passErr)  ?></li><?php
                    endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Heading -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color:var(--primary)">Hello, Welcome Back!</h1>
            <p class="text-sm text-gray-500">Enter your credentials to continue</p>
        </div>

        <!-- Login form -->
        <form method="POST" action="<?= route('web.auth.login.post') ?>">
            <input type="hidden" name="_csrf" value="<?= e($_csrf ?? '') ?>">

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input type="email"
                       class="form-control<?= $__emailErr !== '' ? ' is-invalid' : '' ?>"
                       id="email"
                       placeholder="Email address"
                       name="email"
                       value="<?= old('email') ?>"
                       autocomplete="email">
                <?php if ($__emailErr !== '') : ?>
                    <div class="invalid-feedback"><?= e($__emailErr) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password"
                       class="form-control<?= $__passErr !== '' ? ' is-invalid' : '' ?>"
                       id="password"
                       placeholder="Password"
                       name="password"
                       autocomplete="current-password">
                <?php if ($__passErr !== '') : ?>
                    <div class="invalid-feedback"><?= e($__passErr) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary-tw w-100 py-2 mb-3">Login</button>

            <div class="d-flex justify-content-between small mb-3">
                <div>
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Keep me logged in</label>
                </div>
                <a href="<?= route('admin.v1.auth.reset.req') ?>"
                   class="text-primary-tw text-decoration-none">Forgot password</a>
            </div>
        </form>

        <hr class="my-4">
        <div class="text-center small">
            <span class="text-gray-500">Don't have an account? </span>
            <a class="text-primary-tw text-decoration-none fw-semibold"
               href="<?= route('web.auth.register') ?>">create here</a>
        </div>

    </div>
</div>
