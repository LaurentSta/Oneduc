@props([
    'breadcrumb' => [],
    'title',
    'subtitle' => null,
    'image' => null,
    'imageAlt' => '',
])

<div class="mb-6 rounded-[20px] border border-gray-100 bg-white shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 md:px-6 md:py-5">
    <div class="min-w-0 flex-1">
      @if(!empty($breadcrumb))
        <x-oneduc.breadcrumb :items="$breadcrumb" />
      @endif

      <h1 class="font-raleway text-xl font-medium leading-tight text-bleuone md:text-2xl">
        {{ $title }}
      </h1>

      @if($subtitle)
        <p class="mt-0.5 font-varela text-sm text-orangeone md:text-base">
          {{ $subtitle }}
        </p>
      @endif

      @isset($badges)
        <div class="mt-2.5 flex flex-wrap gap-2 text-xs font-varela">
          {{ $badges }}
        </div>
      @endisset
    </div>

    @if($image)
      <img
        src="{{ $image }}"
        alt="{{ $imageAlt }}"
        loading="lazy"
        class="hidden h-24 w-auto shrink-0 object-contain opacity-90 lg:block xl:h-28"
      >
    @endif
  </div>
</div>
