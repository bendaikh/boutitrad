@php
    $permissions = is_array($permissions ?? null) ? $permissions : [];
    $isFullAccess = $isFullAccess ?? false;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
    @foreach($permissionGroups as $group)
        <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 p-3">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-800 dark:text-brand-200 mb-2 border-b border-slate-200 dark:border-slate-600 pb-1.5">
                {{ $group['label'] }}
            </p>

            @if(! empty($group['permissions']))
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    @foreach($group['permissions'] as $permission)
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-200 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission['key'] }}"
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                @checked($isFullAccess || in_array($permission['key'], $permissions, true))
                                @disabled($isFullAccess)
                            >
                            <span>{{ $permission['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            @foreach($group['sections'] ?? [] as $section)
                <div class="@if(! empty($group['permissions']) || ! $loop->first) mt-3 pt-2 border-t border-slate-200/80 dark:border-slate-600/80 @endif">
                    <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1.5">{{ $section['label'] }}</p>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @foreach($section['permissions'] as $permission)
                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-200 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission['key'] }}"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                    @checked($isFullAccess || in_array($permission['key'], $permissions, true))
                                    @disabled($isFullAccess)
                                >
                                <span>{{ $permission['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>

@if($isFullAccess)
    <p class="mt-2 text-xs text-purple-600 dark:text-purple-400">Super admin : tous les accès sont accordés automatiquement.</p>
@endif
