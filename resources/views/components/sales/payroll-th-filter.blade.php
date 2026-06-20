@props([
    'field',
    'filters' => [],
    'class' => 'admin-th-filter--narrow',
])

<form method="GET" {{ $attributes->merge(['class' => 'admin-th-filter relative '.$class]) }}>
    @foreach($filters as $name => $value)
        @if($name !== $field && $value !== null && $value !== '')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    @if(request('selected'))
        <input type="hidden" name="selected" value="{{ request('selected') }}">
    @endif
    {{ $slot }}
    <svg class="admin-th-filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
</form>
