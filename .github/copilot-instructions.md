**Repo Overview**
- **What it is:** Laravel (v12+) application with Filament admin, scheduled reminders, and Wablas WhatsApp integration. See `README.md` for deployment notes.
- **Primary areas:** `app/Services` (integration logic, e.g. `WablasService.php`), `app/Filament/Resources` (admin UI), `app/Imports` (Excel imports), `app/Support` (access helpers such as `RoleAccess.php`).

**Key Integration Points**
- **Wablas (WhatsApp):** configuration in `config/wablas.php` and runtime logic in `app/Services/WablasService.php`. Wablas requires `WABLAS_BASE_URL`, `WABLAS_TOKEN`, and `WABLAS_GROUP_ID` in env. The public webhook routes are defined in `routes/web.php` (`/wablas/webhook`) and README documents `/api/wablas/webhook`.
- **File imports:** `app/Imports/PersonilsImport.php` uses Maatwebsite Excel; expected column headers include `nama`, `jabatan`, `no_wa`. Phone normalization converts leading `0` → `62`.
- **Filament admin & role access:** Filament resources under `app/Filament/Resources`. Access rules are computed by `app/Support/RoleAccess.php` and stored in `app/Models/RoleAccessSetting.php`.

**Developer Workflows (concrete commands)**
- Install dependencies and basic setup (PowerShell):
  - `composer install`
  - `Copy-Item .env.example .env` (or `cp .env.example .env` on *nix)
  - `composer run setup` (runs migrations, installs npm, builds assets) or run the steps manually shown in `composer.json` `scripts.setup`.
- Run locally (development):
  - `php artisan serve` and in another shell `npm run dev` (or `composer run dev` to run them concurrently as defined).
- Build assets for production: `npm run build`
- Run tests: `composer test` (Pest) or `./vendor/bin/pest` on *nix.

**Patterns & Conventions (specific to this codebase)**
- Services: put external/integration logic in `app/Services` (example: message formatting and sending lives in `WablasService`). Avoid putting HTTP details into controllers; controllers act as thin request handlers.
- Imports: classes in `app/Imports` implement Maatwebsite concerns (see `PersonilsImport.php`) and return Eloquent models directly.
- Routes: public-facing short-letter PDF route uses `Route::get('/u/{kegiatan}', ...)` with route name `kegiatan.surat.short` — services rely on this route to generate shareable links.
- Filament route names begin with `filament.` and `RoleAccess` matches prefixes (see `RoleAccess::pageOptions()` for canonical keys).

**Files to Inspect First (examples)**
- `app/Services/WablasService.php` — message construction, group vs individual send, mention formatting.
- `config/wablas.php` — which env keys are required.
- `app/Imports/PersonilsImport.php` — expected Excel headers and phone normalization.
- `app/Support/RoleAccess.php` and `app/Models/RoleAccessSetting.php` — how permitted Filament pages are resolved.
- `app/Filament/Resources/*` — concrete UI, actions, and resource conventions (naming and route prefixes).
- `routes/web.php` — where public/webhook endpoints are mounted.

**When editing or adding features**
- Prefer adding new domain logic to `app/Services` (testable, isolated) rather than controllers.
- When integrating external APIs, mirror existing patterns in `WablasService` (config-driven, `isConfigured()` guard, `client()` wrapper using `Http::withHeaders`).
- If you add Filament pages/resources, register route keys compatible with `RoleAccess::pageOptions()` to make them selectable in admin settings.

**Quick troubleshooting hints**
- If reminders don't send: confirm `WABLAS_*` env vars, check `storage/logs/*` and `schedule` logs per README. `WablasService::isConfigured()` fails when `token`/`group_id` missing.
- If imported contacts have bad WA numbers: inspect `normalizePhone()` in `PersonilsImport.php` (leading zeros → `62`).

**Example snippets (for reference)**
- Webhook route: see `routes/web.php` — `Route::post('/wablas/webhook', WablasWebhookController::class)->withoutMiddleware([...])`.
- Group message composition: `WablasService::buildGroupMessage()` builds plain-text with mentions, used when sending agenda recap.

If anything here is unclear or you'd like more detail (e.g., a longer example of a Filament resource, or sample .env values), tell me which section to expand.
