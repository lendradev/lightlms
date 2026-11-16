<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Remember the locale the visitor picked.
     *
     * The locale is stored in a cookie that lasts a year. It also applies to
     * visitors who are not signed in, so the public pages follow the choice
     * as well.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string'],
        ]);

        $locale = $validated['locale'];

        abort_unless(Locale::isAvailable($locale), 422);

        Locale::remember($locale);

        return back();
    }
}
