<?php

namespace App\Http\Middleware;

use App\Models\Workshop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectDashboardToWorkshopHome
{
    /**
     * Send users from the generic /dashboard entry (e.g. Fortify home) to the right area.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->can('create', Workshop::class)) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->can('viewAny', Workshop::class)) {
            return redirect()->route('app.dashboard');
        }

        return redirect()->route('profile.edit');
    }
}
