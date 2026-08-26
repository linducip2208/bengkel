<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');

        if (! in_array($locale, ['id', 'en'], true)) {
            $locale = 'id';
        }

        session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()->back();
    }
}
