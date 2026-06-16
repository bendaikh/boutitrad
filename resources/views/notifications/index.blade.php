<x-admin-layout title="Notifications">
    <form method="POST" action="{{ route('notifications.read-all') }}" class="mb-4">@csrf<button type="submit" class="text-sm text-brand-600 dark:text-brand-400 hover:underline">Tout marquer comme lu</button></form>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm divide-y divide-slate-100 dark:divide-slate-700">
        @forelse($notifications as $notification)
            @php
                $url = $notification->data['url'] ?? null;
                $isUnread = ! $notification->read_at;
            @endphp
            <div class="px-5 py-4 {{ $isUnread ? 'bg-brand-50/40 dark:bg-brand-900/20' : 'opacity-70' }}">
                @if($url)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="block text-left w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full text-left group">
                            <div class="text-sm text-slate-800 dark:text-slate-200 group-hover:text-brand-700 dark:group-hover:text-brand-300">
                                {{ $notification->data['message'] ?? 'Notification' }}
                            </div>
                            @if(! empty($notification->data['client_name']))
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Client : {{ $notification->data['client_name'] }}
                                    @if(! empty($notification->data['total']))
                                        · {{ number_format($notification->data['total'], 2, ',', ' ') }} MAD
                                    @endif
                                </div>
                            @endif
                            <div class="text-xs text-slate-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                        </button>
                    </form>
                @else
                    <div class="text-sm text-slate-800 dark:text-slate-200">{{ $notification->data['message'] ?? 'Notification' }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                @endif
            </div>
        @empty
            <div class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune notification</div>
        @endforelse
    </div>
    @if($notifications->hasPages())<div class="mt-4">{{ $notifications->links() }}</div>@endif
</x-admin-layout>
