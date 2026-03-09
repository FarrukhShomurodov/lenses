@extends('admin.layouts.app')

@section('title')
    <title>Пользователи Telegram Бота</title>
@endsection

@section('content')

    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
            Пользователи Telegram
        </h1>
    </div>

    <div class="mb-6 bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
        <form method="GET" action="{{ route('bot.users.index') }}" class="grid sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700  mb-1">Дата от</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="w-full rounded-lg border border-slate-300  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700  mb-1">Дата до</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="w-full rounded-lg border border-slate-300  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                    Фильтровать
                </button>
                <a href="{{ route('bot.users.index') }}"
                   class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-300 transition">
                    Сбросить
                </a>
            </div>
        </form>
    </div>

    <!-- 📊 Статистика -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            <div class="text-sm text-slate-500">Всего пользователей</div>
            <div class="text-2xl font-semibold mt-1 text-slate-800 dark:text-navy-50">
                {{ $users->count() }}
            </div>
        </div>

        <div
            class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-700 p-4">
            <div class="text-sm text-emerald-700">Активные</div>
            <div class="text-2xl font-semibold text-emerald-700 dark:text-emerald-700 mt-1">
                {{ $users->where('is_active', 1)->count() }}
            </div>
        </div>

        <div
            class="bg-rose-50 dark:bg-rose-900/20 rounded-xl shadow-sm border border-rose-200 dark:border-rose-700 p-4">
            <div class="text-sm text-rose-600 dark:text-rose-700">Неактивные</div>
            <div class="text-2xl font-semibold text-rose-700 dark:text-rose-700 mt-1">
                {{ $users->where('is_active', 0)->count() }}
            </div>
        </div>

        <div
            class="bg-blue-50 dark:bg-blue-900/20 rounded-xl shadow-sm border border-blue-200 dark:border-blue-700 p-4">
            <div class="text-sm text-blue-600 dark:text-blue-700">Шаг регистрации</div>
            <div class="text-sm mt-1 text-slate-700 dark:text-navy-100">
                <span class="font-medium">Уникальных шагов:</span>
                {{ $users->pluck('step')->unique()->count() }}
            </div>
        </div>
    </div>

    <!-- 🧾 Таблица -->
    <div
        class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Имя Фамилия</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Telegram</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Телефон</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Статус</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Шаг</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Язык</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
            @foreach($users as $user)
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">

                    <!-- ID -->
                    <td class="px-4 py-3 text-sm font-medium">
                        {{ $user->id }}
                    </td>

                    <!-- Имя -->
                    <td class="px-4 py-3 text-sm font-medium">
                        {{ $user->first_name ?? '—' }} {{ $user->second_name ?? '' }}
                    </td>

                    <!-- Username -->
                    <td class="px-4 py-3 text-sm">
                        @if($user->uname)
                            <a href="https://t.me/{{ $user->uname }}" target="_blank"
                               class="text-blue-600 hover:underline">
                                {{ $user->uname }}
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif

                        <div class="text-xs text-slate-400">
                            chat_id: {{ $user->chat_id }}
                        </div>
                    </td>

                    <!-- Phone -->
                    <td class="px-4 py-3 text-sm">
                        {{ $user->phone ?? '—' }}
                    </td>

                    <!-- Active -->
                    <td class="px-4 py-3">
                        <button
                            class="toggle-user-btn px-3 py-1 rounded text-xs font-medium
        {{ $user->is_active
            ? 'bg-rose-100 text-rose-700 hover:bg-rose-200'
            : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }}"
                            data-id="{{ $user->id }}">

                            {{ $user->is_active ? 'Заблокировать' : 'Разблокировать' }}
                        </button>
                    </td>


                    <!-- Step -->
                    <td class="px-4 py-3 text-sm font-medium">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-navy-700 text-xs">
                            {{ $user->step }}
                        </span>
                    </td>

                    <td class="px-4 py-3 text-sm font-medium">
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-navy-700 text-xs">
                            {{ $user->lang }}
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

@endsection

@section('scripts')
    <script>
        function showToast(message, type = 'success') {
            const colors = {
                success: 'bg-emerald-500',
                error: 'bg-red-500',
            };
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-[10050] flex items-center ${colors[type]} text-white px-4 py-3 rounded-xl shadow-lg transition-opacity duration-300`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Показать уведомление после перезагрузки страницы
        const toastMsg = sessionStorage.getItem('toast_message');
        if (toastMsg) {
            showToast(toastMsg);
            sessionStorage.removeItem('toast_message');
        }

        document.querySelectorAll('.toggle-user-btn').forEach(btn => {
            btn.addEventListener('click', function () {

                if (!confirm('Вы уверены?')) return;

                const userId = this.dataset.id;

                fetch(`/dashboard/admin/bot-users/${userId}/toggle-block`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) return;

                        const message = data.is_active
                            ? 'Аккаунт разблокирован!'
                            : 'Аккаунт заблокирован!';

                        sessionStorage.setItem('toast_message', message);
                        location.reload();
                    });
            });
        });
    </script>
@endsection
