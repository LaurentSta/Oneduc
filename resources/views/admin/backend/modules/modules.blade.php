{{-- /home/laurents/Oneduc_Dev/resources/views/admin/backend/modules/modules.blade.php --}}
@extends('admin.admin_dashboard')
@section('admin')

<div class="w-full px-6 lg:px-8">
  <div class="bg-white rounded-[20px] shadow-soft p-6 my-6 w-full border border-gray-100">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-4 mb-4">
      <div>
        <h1 class="text-[20px] font-varela text-bleuone">Modules</h1>
        <p class="text-sm text-gray-600">
          Gérer le catalogue de formation, les contenus pédagogiques et l'état de publication.
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.modules.add') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-orangeone text-white text-sm font-varela rounded-lg hover:bg-orangeone-hover transition cursor-pointer">
          <i class="ti ti-plus"></i>
          Ajouter un module
        </a>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table id="tableModules" class="table-oneduc w-full text-sm text-left text-gray-700">
        <thead class="text-xs uppercase">
          <tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Désignation</th>
            <th class="px-4 py-3">Catégorie</th>
            <th class="px-4 py-3">Formateur</th>
            <th class="px-4 py-3 text-center">Sections</th>
            <th class="px-4 py-3 text-center">Leçons</th>
            <th class="px-4 py-3 text-center">Quiz</th>
            <th class="px-4 py-3 text-center">Image</th>
            <th class="px-4 py-3 text-center">État</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>

        <tfoot class="text-xs uppercase">
          <tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Désignation</th>
            <th class="px-4 py-3">Catégorie</th>
            <th class="px-4 py-3">Formateur</th>
            <th class="px-4 py-3 text-center">Sections</th>
            <th class="px-4 py-3 text-center">Leçons</th>
            <th class="px-4 py-3 text-center">Quiz</th>
            <th class="px-4 py-3 text-center">Image</th>
            <th class="px-4 py-3 text-center">État</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </tfoot>

        <tbody>
          @forelse ($modules as $key => $module)
            <tr class="border-b border-gray-100 transition">
              <td class="px-4 py-3 whitespace-nowrap">{{ $key + 1 }}</td>

              <td class="px-4 py-3">
                <p class="font-medium text-gray-900 truncate max-w-[280px]" title="{{ $module->module_name }}">
                  {{ $module->module_name }}
                </p>
                <p class="text-xs text-gray-500">ID: {{ $module->id }}</p>
                <p class="mt-1">
                  <span class="inline-flex items-center rounded-full bg-orange-50 px-2 py-1 text-[11px] font-semibold text-orange-700">
                    Temps/question : {{ (int) ($module->estimated_question_seconds ?? 30) }} s
                  </span>
                </p>
              </td>

              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                  {{ optional($module->category)->category_name ?? '—' }}
                </span>
              </td>

              <td class="px-4 py-3 font-medium text-gray-700">
                {{ optional($module->formateur)->name ?? '—' }}
              </td>

              <td class="px-4 py-3 text-center font-semibold text-bleuone">{{ $module->sections_count ?? 0 }}</td>
              <td class="px-4 py-3 text-center font-semibold text-orangeone">{{ $module->lectures_count ?? 0 }}</td>
              <td class="px-4 py-3 text-center font-semibold text-green-700">{{ (int) ($module->quiz_questions_planned ?? 0) }}</td>

              <td class="px-4 py-3 text-center">
                <img src="{{ $module->module_image ? asset('storage/' . $module->module_image) : asset('upload/module_images/NoImage.png') }}"
                     alt="Image module {{ $module->module_name }}"
                     class="mx-auto h-10 w-10 rounded-full object-cover border border-gray-200">
              </td>

              <td class="px-4 py-3 text-center">
                <form action="{{ route('admin.modules.toggle-status', $module->id) }}" method="POST">
                  @csrf
                  @method('PATCH')
                  <button type="submit"
                          title="{{ $module->status ? 'Désactiver' : 'Activer' }}"
                          class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition
                                 {{ $module->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $module->status ? 'Actif' : 'Inactif' }}
                  </button>
                </form>
              </td>

              <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-2">
                  <a href="{{ route('admin.formations.constructeur.edit', $module->id) }}"
                     title="Ouvrir dans le constructeur (éditeur par blocs, IA, aperçu)"
                     class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-bleuone text-white hover:opacity-90 transition text-xs font-varela cursor-pointer">
                    <i class="ti ti-tool"></i>
                    Constructeur
                  </a>

                  <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 hover:bg-gray-50"
                            aria-haspopup="true" :aria-expanded="open.toString()" aria-label="Autres actions">
                      <i class="ti ti-dots-vertical"></i>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-60 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black/5 z-50" role="menu">
                      <div class="py-2 text-sm text-gray-700">
                        <p class="px-4 pb-1 text-[11px] font-bold uppercase tracking-wide text-gray-400">Ancien système</p>
                        <a href="{{ route('admin.modules.edit', $module->id) }}" class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50" role="menuitem">
                          <i class="ti ti-settings"></i> Config
                        </a>
                        <a href="{{ route('admin.modules.lecture.add', $module->id) }}" class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50" role="menuitem">
                          <i class="ti ti-stack-2"></i> Contenu
                        </a>
                        <div class="my-2 border-t"></div>
                        <div class="px-4">
                          <button type="button" x-on:click="$dispatch('open-modal', 'delete-module-{{ $module->id }}')"
                                  class="flex w-full items-center gap-2 px-0 py-2 text-left text-red-700 hover:text-red-800" role="menuitem">
                            <i class="ti ti-trash"></i> Supprimer
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <x-confirm-modal
                    name="delete-module-{{ $module->id }}"
                    title="Supprimer ce module ?"
                    :action="route('admin.modules.delete', ['id' => $module->id])"
                    method="DELETE"
                    confirm-label="Supprimer"
                  />
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="px-4 py-6 text-center text-gray-500">
                Aucun module trouvé.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    $('#tableModules').DataTable({
      language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' },
      order: [[1, 'asc']],
      columnDefs: [
        { targets: 0, orderable: false },
        { targets: 7, orderable: false },
        { targets: 8, orderable: false },
        { targets: 9, orderable: false }
      ]
    });

    @if(session('success'))
      Toastify({
        text: "{{ session('success') }}",
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: "#01c69c",
        close: true,
        style: { fontSize: "16px", borderRadius: "10px" }
      }).showToast();
    @endif

    @if(session('error'))
      Toastify({
        text: "{{ session('error') }}",
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: "#ef4444",
        close: true,
        style: { fontSize: "16px", borderRadius: "10px" }
      }).showToast();
    @endif
  });
</script>

@endsection
