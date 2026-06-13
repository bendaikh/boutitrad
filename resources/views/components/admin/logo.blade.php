@props(['class' => 'h-14 w-auto'])

<img
    {{ $attributes->merge(['class' => $class]) }}
    src="{{ asset('images/beldi-malaki-logo.png') }}"
    alt="BELDI-MALAKI"
>
