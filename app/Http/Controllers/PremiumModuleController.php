<?php

namespace App\Http\Controllers;

use App\Models\PremiumModule;
use Illuminate\Http\Request;

class PremiumModuleController extends Controller
{
    public function index()
    {
        PremiumModule::seedCatalog();

        $modules = PremiumModule::orderBy('name')->get();

        return view('premium.index', compact('modules'));
    }

    public function toggle(Request $request, PremiumModule $module)
    {
        $module->update([
            'enabled' => ! $module->enabled,
            'enabled_by' => ! $module->enabled ? auth()->id() : null,
            'enabled_at' => ! $module->enabled ? now() : null,
        ]);

        return redirect()
            ->route('premium.index')
            ->with('success', $module->enabled
                ? "Modulo {$module->name} activado."
                : "Modulo {$module->name} desactivado.");
    }

    public function locked(string $moduleCode)
    {
        PremiumModule::seedCatalog();

        $module = PremiumModule::where('code', $moduleCode)->first();

        return view('premium.locked', compact('module'));
    }
}
