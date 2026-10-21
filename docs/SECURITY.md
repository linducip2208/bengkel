# SECURITY — Bengkel Paten

## Model ancaman & kontrol

| Area | Kontrol | Bukti test |
|---|---|---|
| Branch isolation (IDOR/BOLA) | `BranchScope` + `HasBranchScope` + `BranchAccess`, fail-closed; lintas cabang = 404 | BranchIsolationTest, BranchIsolationFailClosedTest, ApiWorkshopContractTest, ApiSecurityTest |
| Auth | Sanctum Bearer, `is_active` check, token revoke saat logout, tanpa registrasi publik, guard eksplisit `web` untuk role | ApiSecurityTest (401), manual |
| Brute force / rate limit | login `throttle:10,1`, API `throttle:120,1` | ApiSecurityTest (429) |
| Mass assignment / tampering | Form-request/controller `validate()` allowlist; `branch_id`/total/harga client diabaikan; uang dihitung server | ApiSecurityTest (BOLA), ApiIntegrityTest |
| State tampering | state machine eksplisit, transisi ilegal 409/422 | StatusTransitionTest, ApiWorkshopContractTest |
| File upload | `image` + `mimes:jpeg,png,webp` + `max:5120`, nama acak (`store()`), scope parent, hapus fisik via hook | ApiWorkshopContractTest |
| Webhook payment | verifikasi signature + anti-replay (`PaymentGatewayService::handleCallback`) | PaymentWebhookIdempotencyTest |
| Approval publik | token `Str::random(40)` unik (cek tabrakan), tanpa ID sekuensial, DRAFT = 404, histori keputusan immutable | EstimateApprovalFlowTest, EstimateRevisionPreservesDecisionHistoryTest |
| Headers | `SecurityHeaders` (nosniff, SAMEORIGIN, referrer, permissions-policy) | header assertion via HTTP |
| CORS | default framework untuk `api/*` (dibutuhkan aplikasi mobile); preflight 204 + `Access-Control-Allow-Origin` | ApiSecurityTest (OPTIONS) |
| XSS/escape | Blade `{{ }}` escaped; error prod generik (tanpa stack trace saat `APP_DEBUG=false`) | bootstrap/app.php |
| CSRF | aktif web; dikecualikan hanya `payment/callback/*`, `webhooks/*` (verifikasi signature sendiri) | bootstrap/app.php |
| Secrets | tidak ada secret di repo (scan §AH); `.env` tidak dicommit; APP_KEY dari env | grep audit |

## Aturan non-negosiasi (ditegakkan kode, bukan imbauan)

Server otoritatif; `branch_id`/`user_id`/total/status pembayaran/kuantitas stok
dari client tidak dipercaya. Restricted tanpa assignment = DENIED.
Keamanan tidak dilemahkan demi kenyamanan.

## Sisa risiko tercatat

- `guzzlehttp/guzzle` 7.10.6: 9 advisory menunggu upstream 7.15.2/8.0.1.
  Mitigasi: HTTP keluar hanya ke URL gateway terkonfigurasi + timeout.
- Foto di disk `public` (mengikuti konvensi web); file privat ber-otorisasi penuh = roadmap.
