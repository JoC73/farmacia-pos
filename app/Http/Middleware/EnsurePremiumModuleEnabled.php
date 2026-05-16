<?php

namespace App\Http\Middleware;

use App\Models\PremiumModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        if ($request->user()?->hasRole('Super Usuario')) {
            return $next($request);
        }

        if (PremiumModule::enabled($moduleCode)) {
            return $next($request);
        }

        return redirect()->route('premium.locked', $moduleCode);
    }
}
