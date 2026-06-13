<x-admin-layout title="Notifications">
    <form method="POST" action="{{ route('notifications.read-all') }}" class="mb-4">@csrf<button type="submit" class="text-sm text-brand-600">Tout marquer comme lu</button></form>
    <div class="bg-white rounded-xl border shadow-sm divide-y">
        @forelse($notifications as $notification)
            <div class="px-5 py-4 {{ $notification->read_at ? 'opacity-60' : 'bg-brand-50/30' }}">
                <div class="text-sm">{{ $notification->data['message'] ?? 'Notification' }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
            </div>
        @empty
            <div class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune notification</div>
        @endforelse
    </div>
    @if($notifications->hasPages())<div class="mt-4">{{ $notifications->links() }}</div>@endif
</x-admin-layout>
