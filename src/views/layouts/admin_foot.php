<?php

/**
 * Admin foot layout partial — JS section.
 *
 * Expected variables:
 *   $flash  array{success?: string, error?: string}
 */

declare(strict_types=1);

$_flashSuccess = (string)(($flash ?? [])['success'] ?? '');
$_flashError   = (string)(($flash ?? [])['error']   ?? '');
?>
<!-- Trumbowyg rich text editor init -->
<script>
    (function () {
        if (!(window.jQuery && jQuery.fn.trumbowyg)) return;

        // CSRF token from the hidden form field rendered in every admin page
        var csrfInput = document.querySelector('input[name=_csrf]');
        var csrf = csrfInput ? csrfInput.value : '';
        if (csrf) window._csrf = csrf;

        var editorBtns = [
            ['viewHTML'],
            ['formatting'],
            ['strong', 'em', 'del'],
            ['superscript', 'subscript'],
            ['link'],
            ['filemanager'],
            ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
            ['unorderedList', 'orderedList'],
            ['horizontalRule'],
            ['removeformat'],
            ['fullscreen']
        ];

        $(".trumbowyg").trumbowyg();
        $(".trumbowyg-editor").trumbowyg({
            btns: editorBtns,
            semantic: { div: 'div' },
            removeformatPasted: true,
            autogrow: true,
            plugins: { filemanager: true },
        });

        // Sinkronkan HTML editor → textarea sumber saat form disubmit
        $("form").on("submit", function () {
            $(this).find(".trumbowyg, .trumbowyg-editor").each(function () {
                if ($(this).data('trumbowyg')) $(this).val($(this).trumbowyg('html'));
            });
        });
    })();
</script>

<!-- Toggle sidebar (mobile) -->
<script>
    (function () {
        var sb  = document.getElementById('tw-sidebar');
        var ov  = document.getElementById('tw-sidebar-overlay');
        var btn = document.getElementById('tw-sidebar-toggle');
        function open()  { if (sb) sb.classList.remove('-translate-x-full'); if (ov) ov.classList.remove('hidden'); }
        function close() { if (sb) sb.classList.add('-translate-x-full');    if (ov) ov.classList.add('hidden'); }
        if (btn) btn.addEventListener('click', open);
        if (ov)  ov.addEventListener('click',  close);
    })();
</script>

<!-- Dropdown toggle (vanilla JS, no Bootstrap) -->
<script>
    (function () {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-toggle-dd]');
            var box     = trigger ? trigger.closest('.dropdown, .btn-group') : null;
            var current = box     ? box.querySelector('.dropdown-menu')       : null;
            // Close all open menus except the one just clicked
            document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
                if (m !== current) m.classList.remove('show');
            });
            if (current) {
                e.preventDefault();
                current.classList.toggle('show');
            }
        });
    })();
</script>

<!-- Global image fallback: failed images → Font Awesome placeholder icon -->
<script>
    (function () {
        function placeholder(img) {
            if (img.dataset.imgFallback) return; // prevent loop
            img.dataset.imgFallback = '1';
            var cls      = (img.className || '') + ' ' + (img.getAttribute('alt') || '');
            var isAvatar = /img-profile|picture|avatar|user/i.test(cls);
            var isCircle = /rounded-full|rounded-circle|img-profile/i.test(cls);
            var icon     = isAvatar ? 'fa-user' : 'fa-image';
            var w = img.getAttribute('width')  || img.offsetWidth  || 40;
            var h = img.getAttribute('height') || img.offsetHeight || 40;
            var box = document.createElement('span');
            box.className = 'img-placeholder ' + (img.className || '');
            box.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;' +
                'width:' + w + 'px;height:' + h + 'px;background:#f1f5f9;color:#94a3b8;' +
                (isCircle ? 'border-radius:9999px;' : 'border-radius:.5rem;');
            box.innerHTML = '<i class="fas ' + icon + '" style="font-size:' +
                Math.max(14, Math.min(Number(w), Number(h)) * 0.45) + 'px"></i>';
            if (img.parentNode) img.parentNode.replaceChild(box, img);
        }
        // Capture errors at capture phase (error does not bubble)
        document.addEventListener('error', function (e) {
            var t = e.target;
            if (t && t.tagName === 'IMG') placeholder(t);
        }, true);
        // Images already broken before handler was attached
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('img').forEach(function (img) {
                if (img.complete && img.naturalWidth === 0) placeholder(img);
            });
        });
    })();
</script>

<!-- Toast notifications, Modal/Confirm helpers, data-confirm, #checkall, previewImage -->
<div class="toast-container" id="tw-toasts"></div>
<div class="modal-overlay" id="tw-modal">
    <div class="modal-box">
        <div class="modal-header">
            <span id="tw-modal-title">Konfirmasi</span>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body" id="tw-modal-body"></div>
        <div class="modal-footer" id="tw-modal-footer"></div>
    </div>
</div>
<script>
    (function () {
        // ── Toast ────────────────────────────────────────────────────────────
        window.Toast = function (message, type) {
            type = type || 'info';
            var c    = document.getElementById('tw-toasts');
            var t    = document.createElement('div');
            t.className = 'toast ' + type;
            var icon = type === 'success' ? 'fa-check-circle'
                     : type === 'error'   ? 'fa-times-circle'
                     :                      'fa-info-circle';
            t.innerHTML = '<i class="fas ' + icon + '"></i><span></span>';
            t.querySelector('span').textContent = message;
            c.appendChild(t);
            requestAnimationFrame(function () { t.classList.add('show'); });
            setTimeout(function () {
                t.classList.remove('show');
                setTimeout(function () { t.remove(); }, 300);
            }, 3500);
        };

        // ── Modal ─────────────────────────────────────────────────────────────
        var overlay = document.getElementById('tw-modal');
        var titleEl = document.getElementById('tw-modal-title');
        var bodyEl  = document.getElementById('tw-modal-body');
        var footEl  = document.getElementById('tw-modal-footer');

        function closeModal() { overlay.classList.remove('show'); }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.hasAttribute('data-modal-close')) closeModal();
        });

        window.showModal = function (opts) {
            titleEl.textContent = opts.title || 'Info';
            bodyEl.innerHTML    = opts.body  || '';
            footEl.innerHTML    = '';
            var btns = opts.buttons || [{ label: 'Tutup', class: 'btn btn-primary-tw px-4 py-2', onClick: closeModal }];
            btns.forEach(function (b) {
                var btn = document.createElement('button');
                btn.className   = b.class || 'btn btn-primary-tw px-4 py-2';
                btn.textContent = b.label;
                btn.addEventListener('click', function () {
                    if (b.onClick) b.onClick();
                    if (b.close !== false) closeModal();
                });
                footEl.appendChild(btn);
            });
            overlay.classList.add('show');
        };

        window.hideModal = closeModal;

        // confirmDialog → Promise<boolean>
        window.confirmDialog = function (message, opts) {
            opts = opts || {};
            return new Promise(function (resolve) {
                window.showModal({
                    title: opts.title      || 'Konfirmasi',
                    body:  '<p>' + message + '</p>',
                    buttons: [
                        {
                            label:   opts.cancelText || 'Batal',
                            class:   'btn btn-danger px-4 py-2 text-white',
                            onClick: function () { resolve(false); }
                        },
                        {
                            label:   opts.okText || 'Ya',
                            class:   'btn btn-primary-tw px-4 py-2',
                            onClick: function () { resolve(true); }
                        }
                    ]
                });
            });
        };

        // Auto data-confirm handler (form submit / link)
        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el) return;
            e.preventDefault();
            var msg = el.getAttribute('data-confirm') || 'Anda yakin?';
            confirmDialog(msg).then(function (ok) {
                if (!ok) return;
                if (el.tagName === 'A' && el.href)  { window.location.href = el.href; }
                else if (el.form)                    { el.form.submit(); }
                else if (el.closest('form'))         { el.closest('form').submit(); }
            });
        });

        // #checkall — select-all checkbox
        var checkAll = document.getElementById('checkall');
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    if (cb !== checkAll) cb.checked = checkAll.checked;
                });
            });
        }

        // File input image preview
        window.previewImage = function (input, previewId) {
            if (!input.files || !input.files[0]) return;
            var reader   = new FileReader();
            reader.onload = function (ev) {
                var img = document.getElementById(previewId);
                if (img) img.src = ev.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        };

        // ── Flash from PHP → Toast ────────────────────────────────────────────
        <?php if ($_flashSuccess !== '') : ?>
        document.addEventListener('DOMContentLoaded', function () {
            Toast(<?= json_encode($_flashSuccess, JSON_UNESCAPED_UNICODE) ?>, 'success');
        });
        <?php endif; ?>
        <?php if ($_flashError !== '') : ?>
        document.addEventListener('DOMContentLoaded', function () {
            Toast(<?= json_encode($_flashError, JSON_UNESCAPED_UNICODE) ?>, 'error');
        });
        <?php endif; ?>
    })();
</script>
</body>
</html>
