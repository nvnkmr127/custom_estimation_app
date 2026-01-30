<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-500">Your recent activities and alerts</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Mark All as Read
                </button>
            </form>
        </div>
    </div>

    <div class="max-w-4xl mx-auto">
        <livewire:notification-list />
    </div>
</x-app-layout>