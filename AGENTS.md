# AGENTS.md — PHPAdmin Development Rules

> Source of truth. All AI tools and developers MUST follow this document.

## Stack
PHP 8.3+, Composer 2.x, PSR-4. Libraries: nikic/fast-route, php-di/php-di, illuminate/database (Eloquent standalone), robmorgan/phinx (migrations), respect/validation, firebase/php-jwt, predis/predis, phpmailer/phpmailer, aws/aws-sdk-php, vlucas/phpdotenv.

## Request Lifecycle
public/index.php → method-override → RouteRegistry dispatch → [Auth → Authorize → CSRF] middleware → Controller (thin) → Service (business logic, throw AppException) → Model/Eloquent → DB; errors → ErrorHandler (terpusat)

## WAJIB (Mandatory)
1. **e() untuk SEMUA output konten user** — <?= e($var) ?> tanpa pengecualian. <?= $safeHtml ?> HANYA untuk HTML yang sudah disanitasi server.
2. **route() untuk SEMUA URL** — JANGAN hardcode URL string di template atau controller.
3. **Service implements I*Service** — throw AppException subclass (JANGAN return error, JANGAN die()/exit()).
4. **Controller TIPIS** — parse request + call service + render/redirect/json_response. Tanpa business logic.
5. **DI via PHP-DI** — JANGAN new ServiceClass() di controller/module. Inject via constructor.
6. **Config via AppConfig** — JANGAN $_ENV[] atau getenv() di src/Modules/.
7. **Model PIN $table** — protected $table = 'users' eksplisit. JANGAN andalkan auto-pluralize Eloquent.
8. **belongsToMany PIN semua argumen** — PIN join table + foreign keys.
9. **Migration Phinx Table API** — JANGAN raw SQL, JANGAN tipe vendor.
10. **Front controller** — HANYA public/index.php yang diakses web. File src/ tidak boleh diakses langsung.

## Artefact Matrix
Selalu: I*Service + Service, Controller, Route, Test, docs update
Kondisional: Model (jika simpan data), Migration (jika ada model), DTO/Validator (jika write input), Views (jika UI), API routes (jika perlu)

## Named Routes — Konvensi
Pola: {admin.v1|web|api.v1}.{modul}.{resource}.{aksi}
Resource access: namespace 'access' + singular (user/role/permission)
Aksi lengkap: index/create/store/edit/update/delete/delete_selected

## DO NOT (CI akan gagal)
- new UserService() di controller → gunakan PHP-DI injection
- $_ENV['X'] di src/Modules/ → gunakan AppConfig
- echo $var atau <?= $var ?> tanpa e() → WAJIB e()
- Service tanpa interface atau tanpa throw AppException
- die()/exit() di controller/service
- Model tanpa explicit $table
- Modul baru tanpa test + docs update

## Security Checklist
- SESSION_SECRET + JWT_SECRET: fail-fast di production jika kosong/< 32 char
- Semua form mutasi: CSRF token validation
- Endpoint sensitif (login/register/OTP): rate limit Redis
- Upload: magic-byte validation + GD re-encode
- Password: password_hash(PASSWORD_BCRYPT, ['cost' => rounds])
- JWT blacklist: Redis setex("blacklist:{jti}", ttl, "1") saat logout

## Commands
composer start          # php -S 0.0.0.0:8000
composer test           # phpunit
composer check          # phpstan level 6 + phpcs PSR-12
composer migrate        # phinx migrate
php bin/make_module X   # generate module scaffold

## Definition of Done
- [ ] composer check passes
- [ ] composer test passes (+ new tests)
- [ ] Security checklist terpenuhi
- [ ] README + docs/API.md diperbarui
