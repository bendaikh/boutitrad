@props(['user', 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-9 h-9 text-sm',
        'lg' => 'w-20 h-20 text-xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if ($user->profilePhotoUrl())
    <img
        {{ $attributes->merge(['class' => "rounded-full object-cover ring-2 ring-brand-100 dark:ring-brand-800 shrink-0 {$sizeClass}"]) }}
        src="{{ $user->profilePhotoUrl() }}"
        alt="Photo de profil de {{ $user->name }}"
    >
@else
    <div {{ $attributes->merge(['class' => "rounded-full bg-brand-600 dark:bg-brand-500 flex items-center justify-center font-semibold text-white ring-2 ring-brand-100 dark:ring-brand-800 shrink-0 {$sizeClass}"]) }}>
        {{ $user->initials() }}
    </div>
@endif
