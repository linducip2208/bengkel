<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Otorisasi berbasis permission Spatie per modul, dipetakan dari nama
 * action controller. Dipakai sebagai `can.module:<module>` pada
 * Route::resource agar edit di /roles berdampak ke SEMUA endpoint,
 * bukan cuma yang kebetulan dijaga manual.
 *
 * index/show/preview/validate -> view | create/store -> create
 * edit/update -> edit | destroy -> delete, plus pemetaan aksi custom
 * (submit/approve/convert/dll) sesuai arti bisnisnya.
 */
class AuthorizeModuleAccess
{
    private const ACTION_MAP = [
        // resource verbs
        'index' => 'view', 'show' => 'view',
        'create' => 'create', 'store' => 'create',
        'edit' => 'edit', 'update' => 'edit',
        'destroy' => 'delete',
        'void' => 'void',
        // custom actions
        'preview' => 'view', 'validate' => 'view',
        'submit' => 'submit', 'approve' => 'approve',
        'reject' => 'approve', 'convert' => 'approve',
        'convertToPurchaseOrder' => 'approve',
        'markReceived' => 'receive', 'markPaid' => 'mark_paid',
        'markPaidBatch' => 'mark_paid',
        'send' => 'send', 'sendScheduled' => 'send',
        'publish' => 'edit', 'unpublish' => 'edit', 'reply' => 'edit',
        'adjust' => 'adjust', 'resolve' => 'resolve', 'defer' => 'update',
        'start' => 'start', 'finish' => 'finish',
        'startJob' => 'start', 'finishJob' => 'finish',
        'report' => 'report',
        'transferIndex' => 'view', 'transferCreate' => 'create', 'transferStore' => 'create',
    ];

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $action = (string) $request->route()?->getActionMethod();
        $ability = self::ACTION_MAP[$action] ?? 'view';
        $user = $request->user();

        abort_unless(
            $user && $user->hasWebPermission("{$module}.{$ability}"),
            403,
            "Anda tidak berhak melakukan aksi ini ({$module}.{$ability})."
        );

        return $next($request);
    }
}
