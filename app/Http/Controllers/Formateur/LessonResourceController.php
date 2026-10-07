<?php

namespace App\Http\Controllers\Formateur;

use App\Domains\ModulesFormateur\Actions\AjouterRessourceLecon;
use App\Domains\ModulesFormateur\Support\AccesModule;
use App\Http\Controllers\Controller;
use App\Models\LessonResource;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonResourceController extends Controller
{
    public function __construct(private readonly AccesModule $accesModule) {}

    public function store(Request $request, Module $module, ModuleSection $section, ModuleLecture $lecture, AjouterRessourceLecon $action): RedirectResponse
    {
        $this->assertCanManage($request, $module, $section, $lecture);
        $action->execute($lecture, $request);

        return back()->with('success', 'Ressource ajoutée au module.');
    }

    public function toggleVisibility(Request $request, Module $module, ModuleSection $section, ModuleLecture $lecture, LessonResource $resource): RedirectResponse
    {
        $this->assertCanManage($request, $module, $section, $lecture, $resource);

        $validated = $request->validate([
            'is_visible_to_stagiaire' => ['required', 'boolean'],
        ]);

        $resource->update([
            'is_visible_to_stagiaire' => (bool) $validated['is_visible_to_stagiaire'],
        ]);

        return back()->with('success', 'Visibilité stagiaire mise à jour.');
    }

    public function destroy(Request $request, Module $module, ModuleSection $section, ModuleLecture $lecture, LessonResource $resource): RedirectResponse
    {
        $this->assertCanManage($request, $module, $section, $lecture, $resource);

        if (!empty($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $resource->delete();

        return back()->with('success', 'Ressource supprimée.');
    }

    private function assertCanManage(
        Request $request,
        Module $module,
        ModuleSection $section,
        ModuleLecture $lecture,
        ?LessonResource $resource = null
    ): void {
        $user = $request->user();

        abort_unless((int) $section->module_id === (int) $module->id, 404);
        abort_unless((int) $lecture->module_id === (int) $module->id, 404);
        abort_unless((int) $lecture->section_id === (int) $section->id, 404);
        $this->accesModule->assertOwner($module, (int) $user->id);

        if ($resource) {
            abort_unless((int) $resource->module_id === (int) $module->id, 404);
        }
    }
}
