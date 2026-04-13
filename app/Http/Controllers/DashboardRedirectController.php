<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        if ($user->can('create', Workshop::class)) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->can('viewAny', Workshop::class)) {
            return redirect()->route('app.dashboard');
        }

        return redirect()->route('profile.edit');
    }
}
