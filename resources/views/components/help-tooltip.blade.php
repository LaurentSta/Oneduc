@props(['message', 'align' => 'center'])

<div x-data="{ open: false }" class="relative inline-flex" @click.outside="open = false">
    <button type="button"
            @mouseenter="open = true"
            @mouseleave="open = false"
            @focus="open = true"
            @blur="open = false"
            {{ $attributes->merge(['class' => 'inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-white text-[11px] font-black text-bleuone transition hover:border-bleuone', 'aria-label' => 'Aide']) }}>
        ?
    </button>
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute top-full z-20 mt-2 w-64 rounded-xl border border-slate-200 bg-slate-900 px-3 py-2 text-[11px] leading-relaxed text-white shadow-xl {{ $align === 'right' ? 'right-0' : 'left-1/2 -translate-x-1/2' }}">
        {{ $message }}
    </div>
</div>
