<div {{ $attributes->merge(['class' => 'flex flex-col -mx-4 sm:-mx-6 -my-4 sm:-my-6 px-4 sm:px-6 py-4 sm:py-6 h-[calc(100dvh-4rem)] min-h-0 gap-4']) }}>
    @isset($toolbar)
        <div class="shrink-0">{{ $toolbar }}</div>
    @endisset

    <div class="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="shrink-0">{{ $footer }}</div>
    @endisset
</div>
