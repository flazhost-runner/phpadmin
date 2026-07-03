<?php

/**
 * Setting index view — exact PHP port of NodeAdmin setting/index.ejs.
 *
 * Expected variables (injected by SettingController::renderAdmin()):
 *   $data          array<string,mixed>  current setting row
 *   $themes        array<string,array{primary,secondary,light,dark}>  all themes
 *   $themeName     string               currently active theme name
 *   $feTemplates   list<array{slug,name,category}>  current catalog page items
 *   $feActive      string               active fe_template slug
 *   $paginate_data array{total_data,page_size,current_page,total_page}
 *   $feCategories  list<string>         sorted unique category list
 *   $filter        array<string,mixed>  q_name/q_category/q_page_size/q_page
 *   $_csrf         string
 */

declare(strict_types=1);

/** @var array<string,mixed>  $data */
/** @var array<string,array{primary:string,secondary:string,light:string,dark:string}> $themes */
/** @var string               $themeName */
/** @var list<array{slug:string,name:string,category:string}> $feTemplates */
/** @var string               $feActive */
/** @var array{total_data:int,page_size:int,current_page:int,total_page:int} $paginate_data */
/** @var list<string>         $feCategories */
/** @var array<string,mixed>  $filter */

// ── Catalog pagination URL helper ──────────────────────────────────────────────
$feCatalogPageUrl = static function (int $p) use ($filter): string {
    $params = array_filter([
        'q_name'      => (string)($filter['q_name']      ?? ''),
        'q_category'  => (string)($filter['q_category']  ?? ''),
        'q_page_size' => (string)($filter['q_page_size'] ?? '12'),
        'q_page'      => (string)$p,
    ], static fn(string $v): bool => $v !== '');
    return route('admin.v1.setting.index') . ($params !== [] ? '?' . http_build_query($params) : '');
};

// Resolve active fe_template (DB value wins; fall back to default slug).
$feActive = (string)(($data['fe_template'] ?? '') ?: 'agency-consulting-002-creative-agency');
?>

<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800">Setting Management</h1>
</div>

<!-- FE-catalog search form (GET, separate from the POST setting form).
     Inputs inside the catalog card that carry form="fe_search" submit here. -->
<form id="fe_search" method="GET" action="<?= e(route('admin.v1.setting.index')) ?>"></form>

<form method="POST" action="<?= e(route('admin.v1.setting.update')) ?>?_method=PUT" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <!-- ===== 1. Admin Theme ===== -->
  <div class="tw-card p-6 mb-6">
    <div class="flex items-center gap-2 mb-1">
      <i class="fas fa-palette" style="color:var(--primary)"></i>
      <h2 class="text-lg font-bold" style="color:var(--primary)">Admin Theme</h2>
    </div>
    <p class="text-sm text-gray-500 mb-4">Pilih template — seluruh tampilan admin akan berubah warnanya setelah disimpan.</p>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
      <?php foreach ($themes as $thName => $p) :
            $thActive = (((string)($data['theme'] ?? '')) ?: $themeName) === $thName;
            ?>
        <label class="cursor-pointer block">
          <input type="radio" name="theme" value="<?= e($thName) ?>" class="sr-only theme-radio"
                 <?= $thActive ? 'checked' : '' ?>>
          <div class="theme-swatch rounded-xl overflow-hidden border-2 transition <?= $thActive ? 'border-gray-800' : 'border-transparent' ?>"
               style="box-shadow:0 4px 10px rgba(0,0,0,.08)">
            <div class="h-16 flex">
              <div class="flex-1" style="background:<?= e($p['dark'])      ?>"></div>
              <div class="flex-1" style="background:<?= e($p['primary'])   ?>"></div>
              <div class="flex-1" style="background:<?= e($p['secondary']) ?>"></div>
              <div class="flex-1" style="background:<?= e($p['light'])     ?>"></div>
            </div>
            <div class="bg-white py-2 px-3 flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700"><?= e($thName) ?></span>
              <i class="fas fa-check-circle check-icon <?= $thActive ? '' : 'hidden' ?>"
                 style="color:<?= e($p['primary']) ?>"></i>
            </div>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ===== 2. Frontend Template Switcher (catalog, paginated + search) ===== -->
  <div class="tw-card p-6 mb-6">
    <div class="flex items-center gap-2 mb-1">
      <i class="fas fa-window-maximize" style="color:var(--primary)"></i>
      <h2 class="text-lg font-bold" style="color:var(--primary)">Frontend Template</h2>
    </div>
    <p class="text-sm text-gray-500 mb-4">
      Pilih desain halaman depan (landing) publik dari
      <a href="https://github.com/lindoai/opentailwind" target="_blank" class="underline">opentailwind</a>
      (<?= (int)($paginate_data['total_data'] ?? 0) ?> template). Klik <b>Preview</b> untuk lihat penuh.
      Template terpilih diunduh &amp; di-cache saat <b>Save</b>. Lihat hasilnya di
      <a href="/" target="_blank" class="underline" style="color:var(--primary)">halaman depan &#x2197;</a>.
    </p>

    <!-- Active slug — submitted with POST setting form.
         Restored from localStorage so it survives catalog page changes. -->
    <input type="hidden" id="fe_template_input" name="fe_template" value="<?= e($feActive) ?>">

    <!-- Search + category filter (GET, server-side) -->
    <div class="flex flex-wrap items-end gap-2 mb-4">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Cari nama</label>
        <input form="fe_search" type="text" name="q_name"
               value="<?= e((string)($filter['q_name'] ?? '')) ?>"
               placeholder="mis. agency, saas…" class="form-control" style="min-width:220px">
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Kategori</label>
        <select form="fe_search" name="q_category" class="form-control" style="min-width:200px">
          <option value="">Semua kategori</option>
          <?php foreach ($feCategories as $cat) : ?>
            <option value="<?= e($cat) ?>"
                <?= ((string)($filter['q_category'] ?? '')) === $cat ? 'selected' : '' ?>>
                <?= e($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <input form="fe_search" type="hidden" name="q_page_size"
             value="<?= e((string)($paginate_data['page_size'] ?? '12')) ?>">
      <button form="fe_search" type="submit" class="btn btn-success btn-sm" style="height:38px">
        <i class="fas fa-search me-1"></i> Cari
      </button>
      <a href="<?= e(route('admin.v1.setting.index')) ?>" class="btn btn-danger btn-sm" style="height:38px">
        <i class="fas fa-times me-1"></i> Reset
      </a>
    </div>

    <?php if (count($feTemplates) === 0) : ?>
      <div class="text-center text-gray-400 py-10">
        <i class="fas fa-search fa-2x mb-2"></i>
        <p>Tidak ada template yang cocok dengan pencarian.</p>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
      <?php
        /** @var list<string> $feCachedSlugs */
        $feCachedSlugs = $feCachedSlugs ?? [];
        foreach ($feTemplates as $t) :
            $tActive  = $feActive === $t['slug'];
            $tCached  = in_array($t['slug'], $feCachedSlugs, true);
            ?>
        <div class="fe-card block" data-slug="<?= e($t['slug']) ?>" data-cached="<?= $tCached ? '1' : '0' ?>">
          <div class="fe-swatch rounded-xl overflow-hidden border-2 transition <?= $tActive ? 'border-gray-900' : 'border-gray-300' ?>"
               style="box-shadow:0 2px 8px rgba(0,0,0,.12)">
            <div class="fe-thumb fe-preview-trigger relative bg-gray-100 cursor-pointer group"
                 data-slug="<?= e($t['slug']) ?>"
                 data-name="<?= e($t['name']) ?>"
                 data-preview-url="<?= e(route('admin.v1.setting.fe_preview', ['slug' => $t['slug']])) ?>"
                 style="height:140px;overflow:hidden;border-bottom:1px solid #d1d5db;border-top-left-radius:.7rem;border-top-right-radius:.7rem;transform:translateZ(0)">
              <div class="fe-thumb-placeholder absolute inset-0 flex items-center justify-center text-gray-300">
                <i class="fas fa-spinner fa-spin"></i>
              </div>
              <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                   style="background:rgba(0,0,0,.45);pointer-events:none">
                <span class="text-white text-sm font-semibold"><i class="fas fa-eye me-1"></i> Preview</span>
              </div>
            </div>
            <div class="bg-white py-2 px-3">
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-800 truncate"
                      title="<?= e($t['name']) ?>"><?= e($t['name']) ?></span>
                <div class="flex items-center gap-1">
                  <?php if ($tCached) : ?>
                    <span class="text-xs text-green-600" title="Tersedia offline"><i class="fas fa-circle-check"></i></span>
                  <?php endif; ?>
                  <i class="fas fa-check-circle fe-check <?= $tActive ? '' : 'hidden' ?>"
                     style="color:var(--primary)"></i>
                </div>
              </div>
              <span class="text-xs text-gray-400"><?= e($t['category']) ?></span>
              <button type="button"
                      class="fe-select btn btn-sm w-100 mt-2 fw-bold <?= $tActive ? 'btn-primary-tw' : ($tCached ? 'btn-outline-success' : 'btn-outline-dark') ?>"
                      style="font-size:13px;letter-spacing:.3px">
                <i class="fas <?= $tActive ? 'fa-check' : ($tCached ? 'fa-circle-check' : 'fa-hand-pointer') ?> me-1"></i>
                <?= $tActive ? 'TERPILIH' : ($tCached ? 'PILIH' : 'PILIH') ?>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Windowed pagination for the FE catalog -->
    <?php
      $curPage  = (int)($paginate_data['current_page'] ?? 1);
      $totPage  = (int)($paginate_data['total_page']   ?? 1);
      $fromPage = max(1, $curPage - 2);
      $toPage   = min($totPage, $curPage + 2);
    ?>
    <?php if ($totPage > 1) : ?>
    <div class="d-flex justify-content-center mt-5">
      <nav>
        <ul class="pagination">
          <?php if ($curPage > 1) : ?>
            <li class="page-item">
              <a class="page-link" href="<?= e($feCatalogPageUrl($curPage - 1)) ?>">Previous</a>
            </li>
          <?php endif; ?>

          <?php if ($fromPage > 1) : ?>
            <li class="page-item">
              <a class="page-link" href="<?= e($feCatalogPageUrl(1)) ?>">1</a>
            </li>
                <?php if ($fromPage > 2) : ?>
              <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
          <?php endif; ?>

          <?php for ($idx = $fromPage; $idx <= $toPage; $idx++) : ?>
            <li class="page-item <?= $idx === $curPage ? 'active' : '' ?>">
              <a class="page-link" href="<?= e($feCatalogPageUrl($idx)) ?>"><?= $idx ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($toPage < $totPage) : ?>
                <?php if ($toPage < $totPage - 1) : ?>
              <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
            <li class="page-item">
              <a class="page-link" href="<?= e($feCatalogPageUrl($totPage)) ?>"><?= $totPage ?></a>
            </li>
          <?php endif; ?>

          <?php if ($curPage < $totPage) : ?>
            <li class="page-item">
              <a class="page-link" href="<?= e($feCatalogPageUrl($curPage + 1)) ?>">Next</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
  </div>

  <!-- ===== 3. Setting Form ===== -->
  <div class="tw-card p-6">
    <h2 class="text-lg font-bold mb-4" style="color:var(--primary)">Setting Form</h2>

    <div class="mb-3">
      <label for="initial" class="form-label fw-semibold">Company Initial [initial]</label>
      <input id="initial" type="text"
             class="form-control <?= has_error('initial') ? 'is-invalid' : '' ?>"
             name="initial" value="<?= e((string)($data['initial'] ?? '')) ?>">
      <?php if (has_error('initial')) : ?>
        <div class="invalid-feedback"><?= get_error('initial') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="name" class="form-label fw-semibold">Company Name [name]</label>
      <input id="name" type="text"
             class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>"
             name="name" value="<?= e((string)($data['name'] ?? '')) ?>">
      <?php if (has_error('name')) : ?>
        <div class="invalid-feedback"><?= get_error('name') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="description" class="form-label fw-semibold">Description [description]</label>
      <textarea id="description"
                class="trumbowyg-editor form-control <?= has_error('description') ? 'is-invalid' : '' ?>"
                name="description"><?= (string)($data['description'] ?? '') ?></textarea>
      <?php if (has_error('description')) : ?>
        <div class="invalid-feedback"><?= get_error('description') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-4">
      <label for="icon" class="form-label fw-semibold">Company Icon [icon]</label>
      <div class="d-flex align-items-center gap-3">
        <img id="preview-icon"
             src="/<?= e((string)($data['icon'] ?? '')) ?>"
             width="90" height="90"
             class="rounded border p-1" style="object-fit:contain" alt="icon">
        <input id="icon" type="file"
               class="form-control <?= has_error('icon') ? 'is-invalid' : '' ?>"
               name="icon" accept="image/*"
               onchange="previewImage(this,'preview-icon')">
      </div>
      <?php if (has_error('icon')) : ?>
        <div class="text-danger small mt-1"><?= get_error('icon') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-4">
      <label for="logo" class="form-label fw-semibold">Company Logo [logo]</label>
      <div class="d-flex align-items-center gap-3">
        <img id="preview-logo"
             src="/<?= e((string)($data['logo'] ?? '')) ?>"
             width="90" height="90"
             class="rounded border p-1" style="object-fit:contain" alt="logo">
        <input id="logo" type="file"
               class="form-control <?= has_error('logo') ? 'is-invalid' : '' ?>"
               name="logo" accept="image/*"
               onchange="previewImage(this,'preview-logo')">
      </div>
      <?php if (has_error('logo')) : ?>
        <div class="text-danger small mt-1"><?= get_error('logo') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-4">
      <label for="login_image" class="form-label fw-semibold">Login Image [login_image]</label>
      <div class="d-flex align-items-center gap-3">
        <img id="preview-login-image"
             src="/<?= e((string)($data['login_image'] ?? '')) ?>"
             width="90" height="90"
             class="rounded border p-1" style="object-fit:contain" alt="login image">
        <input id="login_image" type="file"
               class="form-control <?= has_error('login_image') ? 'is-invalid' : '' ?>"
               name="login_image" accept="image/*"
               onchange="previewImage(this,'preview-login-image')">
      </div>
      <?php if (has_error('login_image')) : ?>
        <div class="text-danger small mt-1"><?= get_error('login_image') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="phone" class="form-label fw-semibold">Phone [phone]</label>
      <input id="phone" type="text"
             class="form-control <?= has_error('phone') ? 'is-invalid' : '' ?>"
             name="phone" value="<?= e((string)($data['phone'] ?? '')) ?>">
      <?php if (has_error('phone')) : ?>
        <div class="invalid-feedback"><?= get_error('phone') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="address" class="form-label fw-semibold">Address [address]</label>
      <input id="address" type="text"
             class="form-control <?= has_error('address') ? 'is-invalid' : '' ?>"
             name="address" value="<?= e((string)($data['address'] ?? '')) ?>">
      <?php if (has_error('address')) : ?>
        <div class="invalid-feedback"><?= get_error('address') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="email" class="form-label fw-semibold">Email [email]</label>
      <input id="email" type="email"
             class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>"
             name="email" value="<?= e((string)($data['email'] ?? '')) ?>">
      <?php if (has_error('email')) : ?>
        <div class="invalid-feedback"><?= get_error('email') ?></div>
      <?php endif; ?>
    </div>

    <div class="mb-4">
      <label for="copyright" class="form-label fw-semibold">Copyright Text [copyright]</label>
      <input id="copyright" type="text"
             class="form-control <?= has_error('copyright') ? 'is-invalid' : '' ?>"
             name="copyright" value="<?= e((string)($data['copyright'] ?? '')) ?>">
      <?php if (has_error('copyright')) : ?>
        <div class="invalid-feedback"><?= get_error('copyright') ?></div>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary-tw px-4 py-2">
      <i class="fas fa-save me-1"></i> Save
    </button>
  </div>

</form>

<script>
  // Highlight swatch terpilih secara langsung (UX) sebelum submit
  document.querySelectorAll('.theme-radio').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('.theme-swatch').forEach(function (s) {
        s.classList.remove('border-gray-800');
        s.classList.add('border-transparent');
      });
      document.querySelectorAll('.check-icon').forEach(function (c) {
        c.classList.add('hidden');
      });
      var box = r.closest('label').querySelector('.theme-swatch');
      box.classList.add('border-gray-800');
      box.classList.remove('border-transparent');
      r.closest('label').querySelector('.check-icon').classList.remove('hidden');
    });
  });

  // ===== Frontend Template catalog: cache HTML in localStorage =====
  document.addEventListener('DOMContentLoaded', function () {
    var LS_PREFIX = 'fe_tpl_html:';   // per-slug HTML cache
    var LS_SEL    = 'fe_tpl_selected';// slug terpilih (persist lintas halaman)
    var input     = document.getElementById('fe_template_input');

    // Restore saved selection from localStorage (survives catalog page changes).
    var savedSel = localStorage.getItem(LS_SEL);
    if (savedSel && input) input.value = savedSel;

    // Force light-mode inside template iframes regardless of admin color-scheme.
    function forceLight(html) {
      var inject =
        '<meta name="color-scheme" content="light">' +
        '<style type="text/tailwindcss">@custom-variant dark (&:where(.dark, .dark *));</style>' +
        '<style>:root{color-scheme:light !important}' +
        '@media (prefers-color-scheme: dark){:root{color-scheme:light !important}}</style>';
      if (/<head[^>]*>/i.test(html)) {
        return html.replace(/<head[^>]*>/i, function (m) { return m + inject; });
      }
      return inject + html;
    }

    // Fetch one template HTML: from localStorage or upstream then cache.
    function getHtml(slug, url) {
      var cached = null;
      try { cached = localStorage.getItem(LS_PREFIX + slug); } catch (e) {}
      if (cached) return Promise.resolve(cached);
      return fetch(url, { credentials: 'same-origin' })
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.text();
        })
        .then(function (html) {
          try { localStorage.setItem(LS_PREFIX + slug, html); } catch (e) { /* quota full: skip */ }
          return html;
        });
    }

    // Render thumbnail iframe — scaled to fill card width, clipped to 140 px height.
    function renderThumb(box) {
      var slug = box.getAttribute('data-slug');
      var url  = box.getAttribute('data-preview-url');
      getHtml(slug, url).then(function (html) {
        var ph = box.querySelector('.fe-thumb-placeholder');
        if (ph) ph.remove();
        var ifr = document.createElement('iframe');
        ifr.setAttribute('scrolling', 'no');
        ifr.setAttribute('loading', 'lazy');
        var DESIGN_W = 1280;
        var scale    = (box.clientWidth || 280) / DESIGN_W;
        ifr.style.cssText =
          'width:' + DESIGN_W + 'px;height:' + Math.ceil(140 / scale) + 'px;' +
          'border:0;transform:scale(' + scale + ');transform-origin:top left;pointer-events:none';
        ifr.srcdoc = forceLight(html);
        box.appendChild(ifr);
      }).catch(function () {
        var ph = box.querySelector('.fe-thumb-placeholder');
        if (ph) ph.innerHTML = '<i class="fas fa-image text-2xl"></i>';
      });
    }

    // Lazy-load thumbnails via IntersectionObserver (saves bandwidth + CPU).
    var thumbs = document.querySelectorAll('.fe-thumb');
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) { renderThumb(en.target); io.unobserve(en.target); }
        });
      }, { rootMargin: '200px' });
      thumbs.forEach(function (t) { io.observe(t); });
    } else {
      thumbs.forEach(renderThumb);
    }

    // Select a template: update hidden input + localStorage + UI.
    function selectSlug(slug) {
      if (input) input.value = slug;
      try { localStorage.setItem(LS_SEL, slug); } catch (e) {}
      document.querySelectorAll('.fe-card').forEach(function (card) {
        var active  = card.getAttribute('data-slug') === slug;
        var swatch  = card.querySelector('.fe-swatch');
        var check   = card.querySelector('.fe-check');
        var btn     = card.querySelector('.fe-select');
        swatch.classList.toggle('border-gray-900', active);
        swatch.classList.toggle('border-gray-300', !active);
        if (check) check.classList.toggle('hidden', !active);
        if (btn) {
          btn.innerHTML = active
            ? '<i class="fas fa-check me-1"></i> TERPILIH'
            : '<i class="fas fa-hand-pointer me-1"></i> PILIH';
          btn.classList.toggle('btn-primary-tw', active);
          btn.classList.toggle('btn-outline-dark', !active);
        }
      });
    }

    document.querySelectorAll('.fe-select').forEach(function (b) {
      b.addEventListener('click', function () {
        var card   = this.closest('.fe-card');
        var slug   = card.getAttribute('data-slug');
        var cached = card.getAttribute('data-cached') === '1';
        if (!cached) {
          alert('Template ini belum tersedia secara lokal.\nHanya template dengan ikon ✓ hijau yang bisa digunakan.');
          return;
        }
        selectSlug(slug);
      });
    });
    // Sync initial UI state with server-provided selection (ignore localStorage to avoid stale slug).
    if (input && input.value) selectSlug(input.value);

    // ===== Full-page Preview Modal =====
    var modal = document.getElementById('fe-preview-modal');
    var frame = document.getElementById('fe-preview-frame');
    var title = document.getElementById('fe-preview-title');

    function openModal(slug, name, url) {
      title.textContent = name;
      frame.srcdoc = '<div style="font-family:sans-serif;padding:40px">Memuat…</div>';
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      getHtml(slug, url)
        .then(function (html) { frame.srcdoc = forceLight(html); })
        .catch(function () {
          frame.srcdoc = '<p style="padding:40px;font-family:sans-serif">Gagal memuat preview.</p>';
        });
    }
    function closeModal() {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      frame.srcdoc = '';
    }

    document.querySelectorAll('.fe-preview-trigger').forEach(function (b) {
      b.addEventListener('click', function () {
        openModal(
          this.getAttribute('data-slug'),
          this.getAttribute('data-name'),
          this.getAttribute('data-preview-url')
        );
      });
    });
    document.getElementById('fe-preview-close').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
  });
</script>

<!-- Full-page template preview modal -->
<div id="fe-preview-modal" class="hidden fixed inset-0 z-50 items-center justify-center"
     style="background:rgba(0,0,0,.6)">
  <div class="bg-white rounded-xl overflow-hidden shadow-2xl"
       style="width:92vw;height:90vh;display:flex;flex-direction:column">
    <div class="flex items-center justify-between px-4 py-3 border-b">
      <h3 id="fe-preview-title" class="font-bold text-gray-800">Preview</h3>
      <button id="fe-preview-close" type="button" class="btn btn-sm btn-danger">
        <i class="fas fa-times"></i> Tutup
      </button>
    </div>
    <iframe id="fe-preview-frame" class="flex-1 w-full" style="border:0"></iframe>
  </div>
</div>
