# SECURITY REVIEW V11 — hasil aktual + batas audit

## Metode
Review kode statis + regression test HTTP (bukan pentest eksternal). Tidak ada exploit ke sistem produksi. Secret tidak disentuh/dicetak.

## Terverifikasi AMAN (dengan test)
- SSRF printer: allowlist DB, input liar → 422 (`SecurityHardeningTest::test_print_unregistered_ip_rejected`).
- IDOR unduhan media: parent branch-scope, lintas cabang → 404 (`test_media_download_cross_branch_404`).
- Sesi nonaktif: per-request 401/redirect (`test_inactive_user_blocked_web_and_api`).
- Mass assignment: grep `$request->all()` → hanya SettingsService (KV aman, admin-only), PurchaseOrder normalize (merge+validate), gateway callback (diteruskan, bukan mass-assign). Model fillable luas tapi semua controller eksplisit/validated.
- Double-submit POS: idempotency key + UNIQUE + test double-POST 1 invoice.
- Over-refund: guard sold−returned + test.
- CSRF: aktif kecuali callback/webhook by design; Sanctum stateless untuk API.
- Security headers: nosniff, SAMEORIGIN, referrer, permissions-policy, COOP, HSTS (baru).
- Upload: mime+size+disk privat; nama file acak (store()).
- XSS: `{{ }}` dominan; `{!! !!}` tersisa hanya JSON hex-escaped (sales) + konten statis docs.

## Batas audit / risiko tersisa
- 2FA API: challenge OTP + verify + migrasi kolom hilang (SELESAI ronde ini, test ApiTwoFactorTest 5/5).
- Token abadi bila `SANCTUM_TOKEN_EXPIRATION` kosong di prod (ops harus set; `.env.example` sudah 57600).
- CSRF-except `payment/callback/*`, `webhooks/*` belum verifikasi HMAC.
- CSP belum dipasang (risiko merusak inline JS bila gegabah).
- Customer portal: tanpa throttle + password default lemah (P1).
- Public token routes tanpa rate-limit (mengandalkan entropi 32-char).
- `SESSION_SECURE_COOKIE`/`encrypt` tergantung env (tidak di-force agar dev lokal tetap jalan).
- Mass-assign fillable luas tetap latent (perlu test anti-mass-assign khusus — disarankan).
- Tidak ada pemindaian dependency berbayar; `composer/npm audit` non-blocking di CI.
