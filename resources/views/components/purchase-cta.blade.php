{{-- Floating WhatsApp button + delayed purchase popup — vanilla JS --}}
@php
    $waNumber = '6281296052010';
    $waMessage = urlencode("Halo, saya tertarik dengan {{ config('app.name') }} — source code / custom");
    $waLink = "https://wa.me/{$waNumber}?text={$waMessage}";
@endphp

{{-- Floating WhatsApp button --}}
<a href="{{ $waLink }}" target="_blank" rel="noopener"
   style="position:fixed;bottom:24px;right:24px;z-index:9998;display:flex;align-items:center;gap:8px;padding:12px 20px;background:#10b981;color:#fff;border-radius:999px;font-weight:700;font-family:'Inter',system-ui,sans-serif;font-size:14px;text-decoration:none;box-shadow:0 20px 50px -10px rgba(16,185,129,0.5);transition:transform .2s;cursor:pointer;"
   onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    <span class="wa-label" style="display:none;">WhatsApp</span>
    <span style="position:absolute;top:-4px;right:-4px;width:10px;height:10px;background:#f43f5e;border-radius:50%;border:2px solid #fff;animation:pulse-wa 2s infinite;"></span>
</a>

{{-- Popup overlay + modal (vanilla JS) --}}
<div id="purchase-popup-overlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(28,25,23,0.6);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);"></div>
<div id="purchase-popup-modal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;padding:16px;">
    <div style="position:relative;width:100%;max-width:420px;background:#fff;border-radius:24px;box-shadow:0 40px 80px -20px rgba(0,0,0,0.4);overflow:hidden;font-family:'Inter',system-ui,sans-serif;">

        <button onclick="dismissPopup()" style="position:absolute;top:12px;right:12px;z-index:1;width:32px;height:32px;border-radius:50%;background:#f1f5f9;border:none;display:flex;align-items:center;justify-content:center;color:#475569;cursor:pointer;font-size:18px;">&#10005;</button>

        {{-- Hero --}}
        <div style="position:relative;background:linear-gradient(135deg,#2563eb,#1d4ed8,#0f172a);color:#fff;padding:32px;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;font-size:160px;opacity:0.08;line-height:1;">&#128295;</div>
            <div style="position:relative;">
                <div style="display:inline-flex;align-items:center;gap:8px;padding:5px 14px;background:rgba(255,255,255,0.18);border-radius:999px;font-size:11px;font-weight:600;margin-bottom:16px;">
                    <span style="width:8px;height:8px;background:#34d399;border-radius:50%;animation:pulse-wa 2s infinite;"></span>
                    Source Code &amp; Custom &middot; 2026
                </div>
                <h2 style="font-size:26px;font-weight:800;line-height:1.2;margin:0 0 8px;">Butuh Aplikasi Bengkel?</h2>
                <p style="color:#bfdbfe;font-size:13px;margin:0;">Beli source code siap pakai, atau minta custom sesuai kebutuhan. 1&times; bayar, lifetime.</p>
            </div>
        </div>

        {{-- Content --}}
        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div>
                <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:6px;">&#128187; Source Code (Siap Pakai)</div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px;">
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#10b981;font-weight:700;flex-shrink:0;">&#10003;</span> Full source code — Laravel 13 + Bootstrap 5 + MySQL</li>
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#10b981;font-weight:700;flex-shrink:0;">&#10003;</span> 44 modul: POS, booking, inventory, jobcard, loyalty</li>
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#10b981;font-weight:700;flex-shrink:0;">&#10003;</span> Multi-cabang + RBAC + reminder WA/Email</li>
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#10b981;font-weight:700;flex-shrink:0;">&#10003;</span> Lifetime update + 6 bulan support</li>
                </ul>
            </div>

            <div>
                <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:6px;">&#128736; Custom Development</div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px;">
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#3b82f6;font-weight:700;flex-shrink:0;">&#10003;</span> Tambah modul sesuai kebutuhan bisnis Anda</li>
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#3b82f6;font-weight:700;flex-shrink:0;">&#10003;</span> Ubah workflow, tampilan, atau integrasi</li>
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#3b82f6;font-weight:700;flex-shrink:0;">&#10003;</span> Mobile app Android/iOS — booking dari HP</li>
                    <li style="display:flex;align-items:start;gap:8px;font-size:13px;color:#475569;"><span style="color:#3b82f6;font-weight:700;flex-shrink:0;">&#10003;</span> Integrasi: payment gateway, IoT, telemetri</li>
                </ul>
            </div>

            <div style="background:#f8fafc;border-radius:16px;padding:16px;">
                <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.1em;font-weight:600;margin-bottom:4px;">Hubungi langsung</div>
                <div style="font-family:'JetBrains Mono','Courier New',monospace;font-weight:700;color:#0f172a;font-size:17px;">+62 812 9605 2010</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Respons cepat &middot; Demo lengkap &middot; Bisa nego</div>
            </div>

            <a href="{{ $waLink }}" target="_blank" rel="noopener"
               style="display:block;width:100%;padding:16px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;text-align:center;border-radius:16px;font-weight:700;font-size:15px;text-decoration:none;box-shadow:0 12px 30px -8px rgba(16,185,129,0.4);transition:transform .15s;"
               onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
               &#128172; Chat WhatsApp Sekarang
            </a>

            <a href="{{ route('docs.index') }}" style="display:block;width:100%;padding:12px;text-align:center;font-size:13px;color:#64748b;text-decoration:none;font-weight:600;transition:color .2s;"
               onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">
               &#128214; Baca Dokumentasi Dulu &rarr;
            </a>
        </div>
    </div>
</div>

<style>
    @keyframes pulse-wa { 0%,100%{opacity:1} 50%{opacity:0.4} }
    @media (min-width: 768px) {
        .wa-label { display: inline !important; }
    }
</style>

<script>
(function() {
    var STORAGE_KEY = 'bengkel_popup_v2';
    var DELAY_MS = 8000;

    var overlay = document.getElementById('purchase-popup-overlay');
    var modal   = document.getElementById('purchase-popup-modal');

    function show() {
        overlay.style.display = 'block';
        modal.style.display = 'flex';
    }

    function hide() {
        overlay.style.display = 'none';
        modal.style.display = 'none';
        try { sessionStorage.setItem(STORAGE_KEY, '1'); } catch(e) {}
    }

    window.dismissPopup = function() { hide(); };

    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) hide();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') hide();
    });

    var dismissed = false;
    try { dismissed = sessionStorage.getItem(STORAGE_KEY); } catch(e) {}

    if (!dismissed) {
        setTimeout(show, DELAY_MS);
    }
})();
</script>
