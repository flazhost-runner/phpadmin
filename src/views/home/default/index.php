<?php

/**
 * Default home landing page — native PHP view.
 *
 * Rendered when no custom opentailwind template is active.
 *
 * Expected variables:
 *   $setting    array<string,mixed>   app settings row
 *   $pageTitle  string
 */

declare(strict_types=1);

$_layoutDir = dirname(__DIR__, 2) . '/layouts';

// Setting helpers
$_name        = e((string)($setting['name']        ?? 'PHPAdmin'));
$_initial     = e((string)($setting['initial']      ?? 'PA'));
$_desc        = (string)($setting['description']    ?? 'A modern admin panel framework. Build, manage, and scale your application with confidence.');
$_email       = e((string)($setting['email']        ?? ''));
$_phone       = e((string)($setting['phone']        ?? ''));
$_address     = e((string)($setting['address']      ?? ''));
$_copyright   = e((string)($setting['copyright']    ?? '&copy; ' . date('Y') . ' ' . (string)($setting['name'] ?? 'PHPAdmin')));

include $_layoutDir . '/fe_head.php';
?>

<!-- ═══ NAVBAR ════════════════════════════════════════════════════════════════ -->
<nav class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-neutral-100 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <!-- Brand -->
      <a href="/" class="flex items-center gap-3 no-underline">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold"
             style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"><?= $_initial ?></div>
        <span class="text-lg font-bold text-neutral-900"><?= $_name ?></span>
      </a>

      <!-- Desktop nav links -->
      <div class="hidden md:flex items-center gap-8">
        <a href="#hero"     class="text-sm text-neutral-600 hover:text-indigo-600 transition-colors no-underline">Home</a>
        <a href="#services" class="text-sm text-neutral-600 hover:text-indigo-600 transition-colors no-underline">Services</a>
        <a href="#stats"    class="text-sm text-neutral-600 hover:text-indigo-600 transition-colors no-underline">About</a>
        <a href="#cta"      class="text-sm text-neutral-600 hover:text-indigo-600 transition-colors no-underline">Contact</a>
      </div>

      <!-- CTA button -->
      <div class="hidden md:flex items-center gap-3">
        <a href="/auth/login"
           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors no-underline">
          Login
        </a>
      </div>

      <!-- Mobile hamburger -->
      <button id="mobile-toggle" type="button"
              class="md:hidden p-2 rounded-lg text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors"
              aria-label="Toggle menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-neutral-100 pt-3 space-y-1">
      <a href="#hero"     class="block px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 rounded-lg no-underline">Home</a>
      <a href="#services" class="block px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 rounded-lg no-underline">Services</a>
      <a href="#stats"    class="block px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 rounded-lg no-underline">About</a>
      <a href="#cta"      class="block px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 rounded-lg no-underline">Contact</a>
      <a href="/auth/login"
         class="block mt-2 px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg no-underline">Login</a>
    </div>
  </div>
</nav>

<!-- ═══ HERO SECTION ══════════════════════════════════════════════════════════ -->
<section id="hero" class="py-20 sm:py-28 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <!-- Text -->
      <div>
        <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-100 text-indigo-700 text-sm font-semibold rounded-full mb-6">
          <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
          Welcome to <?= $_name ?>
        </span>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-neutral-900 leading-tight mb-6">
          Build. Manage.<br>
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">Scale.</span>
        </h1>
        <p class="text-lg text-neutral-600 mb-8 leading-relaxed">
          <?= htmlspecialchars_decode(e($_desc)) ?>
        </p>
        <div class="flex flex-wrap gap-4">
          <a href="/auth/login"
             class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-lg shadow-indigo-200 no-underline">
            Get Started
          </a>
          <a href="#services"
             class="px-6 py-3 bg-white hover:bg-neutral-50 text-neutral-800 font-semibold rounded-xl border border-neutral-200 transition-colors no-underline">
            Learn More
          </a>
        </div>
      </div>

      <!-- Visual card -->
      <div class="relative">
        <div class="bg-white rounded-3xl shadow-2xl shadow-indigo-100 p-8 border border-neutral-100">
          <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white text-lg font-bold"
                 style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"><?= $_initial ?></div>
            <div>
              <div class="font-bold text-neutral-900"><?= $_name ?></div>
              <div class="text-sm text-neutral-500">Admin Panel</div>
            </div>
          </div>
          <div class="space-y-3">
            <div class="h-2.5 bg-indigo-100 rounded-full"></div>
            <div class="h-2.5 bg-purple-100 rounded-full w-4/5"></div>
            <div class="h-2.5 bg-indigo-50 rounded-full w-3/5"></div>
          </div>
          <div class="grid grid-cols-3 gap-3 mt-6">
            <div class="p-3 bg-indigo-50 rounded-xl text-center">
              <div class="text-xl font-bold text-indigo-700">100+</div>
              <div class="text-xs text-neutral-500 mt-1">Users</div>
            </div>
            <div class="p-3 bg-purple-50 rounded-xl text-center">
              <div class="text-xl font-bold text-purple-700">50+</div>
              <div class="text-xs text-neutral-500 mt-1">Modules</div>
            </div>
            <div class="p-3 bg-green-50 rounded-xl text-center">
              <div class="text-xl font-bold text-green-700">99%</div>
              <div class="text-xs text-neutral-500 mt-1">Uptime</div>
            </div>
          </div>
        </div>
        <!-- Decorative blobs -->
        <div class="absolute -top-4 -right-4 w-24 h-24 bg-indigo-200/30 rounded-full blur-xl"></div>
        <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-purple-200/30 rounded-full blur-xl"></div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ STATS SECTION ═════════════════════════════════════════════════════════ -->
<section id="stats" class="py-16 bg-indigo-600">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center text-white">
      <div>
        <div class="text-4xl font-bold mb-1">150+</div>
        <div class="text-indigo-200 text-sm">Projects Delivered</div>
      </div>
      <div>
        <div class="text-4xl font-bold mb-1">12</div>
        <div class="text-indigo-200 text-sm">Years Experience</div>
      </div>
      <div>
        <div class="text-4xl font-bold mb-1">98%</div>
        <div class="text-indigo-200 text-sm">Client Satisfaction</div>
      </div>
      <div>
        <div class="text-4xl font-bold mb-1">35+</div>
        <div class="text-indigo-200 text-sm">Team Members</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ SERVICES SECTION ══════════════════════════════════════════════════════ -->
<section id="services" class="py-20 sm:py-24 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <span class="text-indigo-600 text-sm font-semibold tracking-wide uppercase">What We Offer</span>
      <h2 class="text-3xl sm:text-4xl font-bold text-neutral-900 mt-2">Services</h2>
      <p class="text-neutral-500 mt-3 max-w-xl mx-auto">
        Everything you need to build and manage a robust admin application.
      </p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Card 1 -->
      <div class="p-6 bg-indigo-50 rounded-2xl hover:shadow-lg transition-shadow">
        <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center mb-4">
          <i class="fas fa-shield-alt text-white text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-2">Access Control</h3>
        <p class="text-neutral-600 text-sm leading-relaxed">
          Role-based permissions with fine-grained access management for every resource.
        </p>
      </div>
      <!-- Card 2 -->
      <div class="p-6 bg-purple-50 rounded-2xl hover:shadow-lg transition-shadow">
        <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-4">
          <i class="fas fa-chart-bar text-white text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-2">Analytics</h3>
        <p class="text-neutral-600 text-sm leading-relaxed">
          Real-time dashboards and insights to track your application's performance.
        </p>
      </div>
      <!-- Card 3 -->
      <div class="p-6 bg-green-50 rounded-2xl hover:shadow-lg transition-shadow">
        <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center mb-4">
          <i class="fas fa-cogs text-white text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-2">Configuration</h3>
        <p class="text-neutral-600 text-sm leading-relaxed">
          Flexible settings management to customise every aspect of your admin panel.
        </p>
      </div>
      <!-- Card 4 -->
      <div class="p-6 bg-amber-50 rounded-2xl hover:shadow-lg transition-shadow">
        <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center mb-4">
          <i class="fas fa-user-circle text-white text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-2">User Management</h3>
        <p class="text-neutral-600 text-sm leading-relaxed">
          Create, update, and deactivate users with full audit trail support.
        </p>
      </div>
      <!-- Card 5 -->
      <div class="p-6 bg-rose-50 rounded-2xl hover:shadow-lg transition-shadow">
        <div class="w-12 h-12 bg-rose-600 rounded-xl flex items-center justify-center mb-4">
          <i class="fas fa-lock text-white text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-2">Security</h3>
        <p class="text-neutral-600 text-sm leading-relaxed">
          CSRF protection, bcrypt hashing, session management, and audit logging.
        </p>
      </div>
      <!-- Card 6 -->
      <div class="p-6 bg-sky-50 rounded-2xl hover:shadow-lg transition-shadow">
        <div class="w-12 h-12 bg-sky-600 rounded-xl flex items-center justify-center mb-4">
          <i class="fas fa-plug text-white text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-2">REST API</h3>
        <p class="text-neutral-600 text-sm leading-relaxed">
          Full JSON API alongside the web UI so integrations are always first-class.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ═══ CTA SECTION ════════════════════════════════════════════════════════════ -->
<section id="cta" class="py-20 bg-gradient-to-br from-indigo-600 to-purple-700">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
    <h2 class="text-3xl sm:text-4xl font-bold mb-4">Ready to get started?</h2>
    <p class="text-indigo-200 text-lg mb-8">
      Sign in to the admin panel and start managing your application today.
    </p>
    <a href="/auth/login"
       class="inline-flex items-center gap-2 px-8 py-4 bg-white text-indigo-700 font-bold rounded-xl hover:bg-indigo-50 transition-colors shadow-xl no-underline">
      <i class="fas fa-sign-in-alt"></i>
      Login to Admin Panel
    </a>
  </div>
</section>

<!-- ═══ FOOTER ════════════════════════════════════════════════════════════════ -->
<footer class="bg-neutral-900 text-neutral-400 py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">
      <!-- Brand column -->
      <div>
        <div class="flex items-center gap-3 mb-4">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold"
               style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"><?= $_initial ?></div>
          <span class="text-white font-bold text-lg"><?= $_name ?></span>
        </div>
        <p class="text-sm leading-relaxed">
          Modern PHP admin panel — SOLID, DI, full-stack.
        </p>
      </div>

      <!-- Contact column -->
      <div>
        <h4 class="text-white font-semibold mb-4">Contact</h4>
        <ul class="space-y-2 text-sm">
          <?php if ($_email) : ?>
          <li class="flex items-center gap-2">
            <i class="fas fa-envelope w-4 text-indigo-400"></i>
            <a href="mailto:<?= $_email ?>" class="hover:text-white transition-colors no-underline"><?= $_email ?></a>
          </li>
          <?php endif; ?>
          <?php if ($_phone) : ?>
          <li class="flex items-center gap-2">
            <i class="fas fa-phone w-4 text-indigo-400"></i>
            <span><?= $_phone ?></span>
          </li>
          <?php endif; ?>
          <?php if ($_address) : ?>
          <li class="flex items-start gap-2">
            <i class="fas fa-map-marker-alt w-4 text-indigo-400 mt-0.5"></i>
            <span><?= $_address ?></span>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Quick links -->
      <div>
        <h4 class="text-white font-semibold mb-4">Quick Links</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="/auth/login"  class="hover:text-white transition-colors no-underline">Login</a></li>
          <li><a href="#services"    class="hover:text-white transition-colors no-underline">Services</a></li>
          <li><a href="#stats"       class="hover:text-white transition-colors no-underline">About</a></li>
          <li><a href="#cta"         class="hover:text-white transition-colors no-underline">Contact</a></li>
        </ul>
      </div>
    </div>

    <!-- Bottom bar -->
    <div class="border-t border-neutral-800 pt-6 text-center text-sm">
      <?= htmlspecialchars_decode($_copyright) ?>
    </div>
  </div>
</footer>

<?php include $_layoutDir . '/fe_foot.php'; ?>
