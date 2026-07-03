<?php

/**
 * Front-end foot layout partial.
 *
 * Expected variables:
 *   $setting  array<string,mixed>
 */

declare(strict_types=1);

?>
<!-- Minimal vanilla JS: smooth scroll & mobile nav toggle -->
<script>
  (function () {
    // Smooth-scroll anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
      });
    });

    // Mobile nav toggle (requires #mobile-menu + #mobile-toggle elements)
    var toggle = document.getElementById('mobile-toggle');
    var menu   = document.getElementById('mobile-menu');
    if (toggle && menu) {
      toggle.addEventListener('click', function () {
        menu.classList.toggle('hidden');
      });
    }
  })();
</script>
</body>
</html>
