# SAST Security Audit — Moneta Money Management

**Scope:** Laravel 13 + Inertia/React 19 money-management application — company-scoped finance tracking, AI transaction parsing, MCP server for AI agents, Passport OAuth2 API.
**Methodology:** Manual static analysis (OWASP Top 10, authorization, input validation, cryptography, secrets, frontend security). All findings below were verified against the current source tree.

---

## Executive Summary

An initial pass flagged the absence of any `company_members`/ownership check as a Critical broken-access-control chain (any authenticated user reaching any company). Verification against git history showed this is not a defect: commit `515cee4` ("refactor!: simplify to single-user, drop roles and permissions", 2026-07-14) deliberately dropped the `company_members` pivot because the application is now single-admin by design — one admin account (created once, during install) owns every company in the instance, there is no registration route, and no code path creates a second user. Under that model, a single user having access to every company in their own instance is correct behavior, not a vulnerability, so those findings are recorded below as **informational** rather than Critical.

The real, actionable findings are a raw AI-provider response leak in error messages (High), an unsanitized `dangerouslySetInnerHTML` sink, missing security headers, and a handful of hardening gaps around sessions, install-state, token lifetimes, and password policy consistency.

### Risk Matrix

| Severity | Count | Findings |
|---|---|---|
| 🟠 High | 1 | H1 |
| 🟡 Medium | 5 | M1, M2, M3, M4, M5 |
| 🟢 Low | 6 | L1, L2, L3, L4, L6, L7 |
| ℹ️ Informational (architectural, not a vulnerability) | 4 | I1, I2, I3, I4 |

| ID | Finding | CWE | Impact | Likelihood |
|---|---|---|---|---|
| H1 | AI provider response leak | CWE-209 | Possible key/infra disclosure | Moderate |
| M1 | Stored XSS sink via QR SVG | CWE-79 | Low (self-generated content) but unsanitized sink | Low |
| M2 | Session encryption disabled | CWE-312 | Plaintext session data in DB | Low |
| M3 | Weak install-state check | CWE-912 | Install flow can be forced/bypassed | Low |
| M4 | Missing security headers | CWE-1021 | Clickjacking, MIME-sniffing, no CSP defense-in-depth | Moderate |
| M5 | EnvWriter escaping risk | CWE-116 | Malformed `.env` on edge-case secrets | Low |
| L1 | SSRF via custom AI base URL | CWE-918 | Internal network probing | Low (needs settings access) |
| L2 | 1-year PAT lifetime | CWE-324 | Extended exposure window if token leaks | Low |
| L3 | Password rules skipped outside prod | CWE-521 | Weak passwords in staging | Low |
| L4 | Installer admin password min:8 | — | Weaker bootstrap credential | Low |
| L6 | Fragile JS-context cookie interpolation | CWE-79 (defense-in-depth) | Not currently exploitable; brittle pattern | Low |
| L7 | Unscoped company listing | CWE-204 | No practical impact given single-user design; noted for completeness | Informational-adjacent |
| I1 | No membership model | CWE-862 (n/a) | Intentional: single-admin architecture | — |
| I2 | Cross-company transfer has no ownership check | CWE-862 (n/a) | Intentional: single admin owns both wallets | — |
| I3 | MCP tools resolve any company by slug | CWE-862 (n/a) | Intentional: single admin's token covers all their companies | — |
| I4 | `CompanyPolicy::delete()` checks count, not ownership | CWE-862 (n/a) | Intentional: "can't delete the last company" is the only meaningful rule for one admin | — |

---

## ℹ️ Informational — Single-User Architecture (not vulnerabilities)

These were the original candidate Critical findings (missing company membership, cross-company transfer authorization, MCP cross-company access, unscoped company listing). They are accurate descriptions of the code, but **not vulnerabilities** given the application's intended architecture — recorded here so the reasoning is auditable, not silently dropped.

### I1. No Company Membership Model

**Files:** [app/Concerns/HasCompanies.php](app/Concerns/HasCompanies.php), [app/Http/Middleware/SetCurrentCompany.php:17-27](app/Http/Middleware/SetCurrentCompany.php#L17-L27), [app/Policies/CompanyPolicy.php:11-14](app/Policies/CompanyPolicy.php#L11-L14)

There is no `company_members` table. `SetCurrentCompany` switches the authenticated user to any company resolved by slug with no ownership check.

**Why this is fine here:** commit `515cee4` intentionally dropped `company_members` and `is_personal` — "One admin owns everything, so company membership, roles, and permission checks were dead weight." There is no registration route (`Features::registration()` is absent from `config/fortify.php`) and the only place a `User` row is created is `App\Actions\Install\CreateAdminAccount`, run once during install. So there is exactly one possible authenticated principal per instance, and every company in that instance belongs to them by construction.

**If this assumption ever changes** (e.g., a future invite/multi-admin feature is added), this becomes a real Critical finding again and the original remediation — a `company_members` pivot plus membership checks in `SetCurrentCompany`, `CompanyPolicy`, and `HasCompanies` — should be revisited at that time. Not implemented now since it would reverse yesterday's deliberate simplification.

### I2. Cross-Company Transfer Has No Ownership Check

**Files:** [app/Http/Requests/Finance/SaveCrossCompanyTransferRequest.php:12-15](app/Http/Requests/Finance/SaveCrossCompanyTransferRequest.php#L12-L15), [app/Actions/Transactions/CreateCrossCompanyTransfer.php:32-38](app/Actions/Transactions/CreateCrossCompanyTransfer.php#L32-L38)

`authorize()` returns `true` unconditionally; the action only checks that the two wallets belong to *different* companies, not that the caller owns either. Same reasoning as I1: the one admin owns every wallet in every company in the instance, so there's no "other tenant" to protect against.

### I3. MCP Tools Resolve Any Company by Slug

**File:** [app/Mcp/Concerns/InteractsWithCompany.php:17-33](app/Mcp/Concerns/InteractsWithCompany.php#L17-L33)

Same reasoning: a Passport token belongs to the one admin, and MCP tools built on `InteractsWithCompany` are meant to reach all of that admin's companies.

### I4. `CompanyPolicy::delete()` Checks Company Count, Not Ownership

**File:** [app/Policies/CompanyPolicy.php:11-14](app/Policies/CompanyPolicy.php#L11-L14)

This matches the commit message's stated intent directly: "Personal-company delete protection becomes 'cannot delete the last company'." Working as designed.

---

## 🟠 High Findings

### H1. AI Provider Response Body Leak (CWE-209)

**File:** [app/Actions/Ai/ParseTransactionText.php:126,152](app/Actions/Ai/ParseTransactionText.php#L126)

```php
throw_unless($response->successful(), RuntimeException::class, 'The AI provider request failed: '.$response->body());
```
(repeated at line 152 for the Anthropic path). The raw HTTP response body from the configured AI provider is surfaced directly to the end user via the exception message. Combined with L1 (unrestricted `base_url`), this turns SSRF probing from blind to semi-blind: pointing `base_url` at an internal service lets its raw response be read back through the parse-transaction error path.

**Remediation:**
```php
throw_unless($response->successful(), RuntimeException::class, 'The AI provider request failed.');
```
Log `$response->status()` and `$response->body()` server-side via `Log::warning()` for debugging, but never include the raw body in a user-facing exception message.

**Status:** ✅ Fixed.

---

## 🟡 Medium Findings

### M1. Stored XSS Sink via QR Code SVG (CWE-79)

**File:** [resources/js/components/two-factor-setup-modal.tsx:79-83](resources/js/components/two-factor-setup-modal.tsx#L79-L83)
```tsx
<div dangerouslySetInnerHTML={{ __html: qrCodeSvg }} ... />
```
`qrCodeSvg` currently originates from Fortify's server-generated TOTP QR SVG, not directly from attacker input, so exploitability today is low. However, it is an unsanitized `dangerouslySetInnerHTML` sink with no defense if the source ever changes.

**Remediation:** Render via an `<img src="data:image/svg+xml;...">` to avoid the raw-HTML sink entirely, rather than adding a new sanitization dependency for a single call site.

**Status:** ✅ Fixed.

### M2. Session Encryption Disabled (CWE-312)

**File:** [.env.example:32](.env.example#L32) — `SESSION_ENCRYPT=false`

**Remediation:** Set `SESSION_ENCRYPT=true`.

**Status:** ✅ Fixed.

### M3. Weak Installation State Check (CWE-912)

**File:** [app/Support/InstallationState.php:9-12](app/Support/InstallationState.php#L9-L12)

Presence of a single marker file at a well-known path gates the entire install flow.

**Remediation:** Treat "an admin user exists" as authoritative, alongside the marker file.

**Status:** ✅ Fixed.

### M4. Missing CSP / Security Headers (CWE-1021)

**File:** [bootstrap/app.php](bootstrap/app.php) — no headers middleware registered.

**Remediation:** Add `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, and HSTS (when secure) via a small middleware. Full CSP deferred — Vite/Inertia's inline bootstrap script (L6) and dev-server assets make a locked-down CSP a separate, riskier change that needs its own report-only rollout.

**Status:** ✅ Fixed (headers). CSP intentionally out of scope for this pass.

### M5. EnvWriter Special-Character Escaping Risk (CWE-116)

**File:** [app/Support/EnvWriter.php:34-47](app/Support/EnvWriter.php#L34-L47)

**Remediation:** Reject newlines/carriage returns in values outright.

**Status:** ✅ Fixed.

---

## 🟢 Low Findings

### L1. SSRF via Custom AI Provider Base URL (CWE-918)

**File:** [app/Http/Requests/Settings/AiSettingsUpdateRequest.php:31,38](app/Http/Requests/Settings/AiSettingsUpdateRequest.php#L31)

Only validates well-formed URL syntax; a user can point it at `169.254.169.254`, `localhost`, etc.

**Remediation:** Reject private/loopback/reserved hosts when `provider === 'custom'`.

**Status:** ✅ Fixed.

### L2. 1-Year Personal Access Token Lifetime (CWE-324)

**File:** [app/Providers/AppServiceProvider.php:41](app/Providers/AppServiceProvider.php#L41)

**Remediation:** Shorten to 90 days.

**Status:** ✅ Fixed.

### L3. Password Strength Rules Skipped Outside Production (CWE-521)

**File:** [app/Providers/AppServiceProvider.php:59-67](app/Providers/AppServiceProvider.php#L59-L67)

**Correction during implementation:** the original claim ("no strength rules in non-production") overstated the gap. `Illuminate\Validation\Rules\Password::default()` already falls back to `Password::min(8)` when the registered callback returns `null` ([vendor/laravel/framework/.../Password.php:166-173](vendor/laravel/framework/src/Illuminate/Validation/Rules/Password.php#L166-L173)), so there was always an implicit 8-character floor outside production, not zero policy. Still worth fixing: relying on an undocumented framework fallback instead of stating the floor explicitly is fragile.

**Remediation:** Return `Password::min(8)` explicitly instead of `null` outside production.

**Status:** ✅ Fixed (made explicit).

### L4. Installer Admin Password Minimum 8 vs Production 12

**File:** [app/Http/Requests/Install/StoreAdminRequest.php:24](app/Http/Requests/Install/StoreAdminRequest.php#L24)

**Remediation:** Reuse `Password::defaults()` instead of a hardcoded `min:8`.

**Status:** ✅ Fixed.

### L6. Fragile JS-Context Cookie Interpolation (CWE-79, defense-in-depth)

**Files:** [resources/views/app.blade.php:8-10](resources/views/app.blade.php#L8-L10), [resources/views/mcp/authorize.blade.php:9-10](resources/views/mcp/authorize.blade.php#L9-L10)

**Verified not currently exploitable:** Laravel's `e()` (used by `{{ }}`) calls `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`, which escapes `'`, `"`, `<`, and `>` — this prevents both breaking out of the JS string and prematurely closing the `<script>` tag. Still worth fixing since relying on HTML-escaping to be "good enough" for a JS context is fragile — a later edit to either view could reintroduce injection silently.

**Remediation:** Use `@json()` instead of manual `{{ }}` interpolation for a JS-context value.

**Status:** ✅ Fixed.

### L7. Unscoped Company Listing (CWE-204)

**File:** [app/Concerns/HasCompanies.php:51-56](app/Concerns/HasCompanies.php#L51-L56)

Lists every company in the instance regardless of membership. Given the single-admin architecture (I1), there is no practical information-disclosure impact today — the one admin is meant to see every company. Left as-is; would only need revisiting alongside I1 if multi-admin support returns.

**Status:** Not changed (informational-adjacent, matches I1's reasoning).

---

## Remediation Priority Matrix

| Priority | Findings | Rationale |
|---|---|---|
| **P0 — Done this pass** | H1, M1–M5, L1–L4, L6 | All fixed below; no dependency chain, each independent |
| **Backlog / revisit-if** | I1–I4, L7 | Only become real findings if multi-admin/multi-user support is reintroduced — revisit the original membership-model remediation at that point |

**Note:** M4's CSP is intentionally partial (security headers only, no `Content-Security-Policy` yet) — a real CSP needs a report-only rollout against Vite/Inertia's asset and inline-script needs, which is a separate, larger change from this pass.

---

## Verification Notes

Every fix above has accompanying test coverage (new or extended existing Pest files) except L2 (one-line config value, no branching logic to test). This sandbox only has PHP 8.2 (via XAMPP) on `PATH`; this project's `composer.json` requires `^8.3`, so `php artisan test` / `vendor/bin/pint` could not be executed here. All changed PHP files were syntax-checked with `php -l` instead. **Run `php artisan test --compact` and `vendor/bin/pint --dirty --format agent` locally (via Herd) before merging.**
