@extends('admin.admin_dashboard')
@section('admin')

<div class="w-full px-6 lg:px-8">
  <div class="form-oneduc-card p-6 my-6 w-full">

    {{-- En-tête minimal --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-4 mb-6">
      <div>
        <h1 class="form-oneduc-title">Modifier un module</h1>
        <p class="form-oneduc-subtitle">Mets à jour les informations du module, sa catégorisation, ses médias et ses options.</p>
      </div>

      <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.modules') }}"
           class="btn-oneduc-sm btn-oneduc-sm--outline">
          <i class="ti ti-arrow-left"></i>
          Retour
        </a>

        <button type="submit" form="formModule"
                class="btn-oneduc-sm btn-oneduc-sm--primary">
          <i class="ti ti-check"></i>
          Enregistrer
        </button>
      </div>
    </div>

    {{-- Succès --}}
    @if (session('success'))
      <div class="mb-6 p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm">
        {{ session('success') }}
      </div>
    @endif

    {{-- Erreurs --}}
    @if ($errors->any())
      <div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form id="formModule" method="POST" action="{{ route('admin.modules.update', $module->id) }}" enctype="multipart/form-data" class="space-y-6">
      @csrf
      @method('PUT')

      @include('admin.backend.modules._form')

    </form>
  </div>
</div>

@endsection
