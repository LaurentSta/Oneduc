@props([
    'name',
    'label',
    'current' => null,
    'accept' => 'image/jpeg,image/png',
    'help' => 'Formats acceptés : JPG, PNG. Poids maximum : 2 Mo.',
])

@php
    $currentUrl = $current ? asset('storage/'.ltrim($current, '/')) : null;
    $placeholderUrl = asset('upload/module_images/NoImage.png');
    $previewId = 'preview_'.$name;
@endphp

<div class="space-y-2" data-image-field data-placeholder="{{ $placeholderUrl }}" data-current="{{ $currentUrl ?? '' }}">
    <label for="{{ $name }}" class="form-oneduc-label">{{ $label }}</label>

    <div class="flex items-start gap-4">
        <img id="{{ $previewId }}" data-role="preview"
             src="{{ $currentUrl ?? $placeholderUrl }}"
             alt="Aperçu {{ $label }}"
             class="w-32 h-32 object-cover rounded-lg border border-gray-200 bg-gray-50 shrink-0">

        <div class="flex-1 space-y-2">
            <input id="{{ $name }}" type="file" name="{{ $name }}" accept="{{ $accept }}"
                   data-role="file" class="form-oneduc-file">
            <div class="form-oneduc-help">{{ $help }}</div>

            @error($name)
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if ($current)
                <input type="hidden" name="remove_{{ $name }}" value="0" data-role="remove-flag">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" data-role="remove-checkbox" class="rounded text-orangeone focus:ring-orangeone">
                    Supprimer l’image actuelle
                </label>
            @endif
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-image-field]').forEach((field) => {
                const fileInput = field.querySelector('[data-role="file"]');
                const preview = field.querySelector('[data-role="preview"]');
                const removeCheckbox = field.querySelector('[data-role="remove-checkbox"]');
                const removeFlag = field.querySelector('[data-role="remove-flag"]');
                const placeholder = field.dataset.placeholder;
                const current = field.dataset.current || placeholder;

                fileInput?.addEventListener('change', function () {
                    if (!this.files || !this.files[0]) return;

                    if (removeCheckbox) removeCheckbox.checked = false;
                    if (removeFlag) removeFlag.value = '0';

                    const reader = new FileReader();
                    reader.onload = (e) => preview.src = e.target.result;
                    reader.readAsDataURL(this.files[0]);
                });

                removeCheckbox?.addEventListener('change', function () {
                    if (this.checked) {
                        fileInput.value = '';
                        preview.src = placeholder;
                        removeFlag.value = '1';
                    } else {
                        preview.src = current;
                        removeFlag.value = '0';
                    }
                });
            });
        });
    </script>
@endonce
