# DEPLOYMENT — Bengkel Paten

Panduan lengkap ada di root: [`../DEPLOYMENT.md`](../DEPLOYMENT.md)
(ENV, APP_URL, DB, cache/queue/session, mail, Redis, Sanctum, CORS,
logging, license pairing, backup).

Checklist produksi: `APP_ENV=production`, `APP_DEBUG=false`, `config:cache` +
`view:cache` teruji di CI, health `/up` + `/api/v1/health` OK,
scheduler cron aktif (backup, sesi POS basi, invoice overdue, estimasi expired),
storage link (`public/storage`) ada.
