{{-- Teacher navigation — SugarCRM-style compact icon sidebar --}}
@php
    $navItems = [
        ['url' => '/teacher', 'match' => 'teacher', 'exact' => true, 'label' => 'Обзор', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['url' => '/teacher/students', 'match' => 'teacher/students*', 'label' => 'Ученики', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['url' => '/teacher/groups', 'match' => 'teacher/groups*', 'label' => 'Группы', 'icon' => 'M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-8 0v2m8 0H9m8-10a4 4 0 11-8 0 4 4 0 018 0z'],
        ['url' => '/teacher/homework', 'match' => 'teacher/homework*', 'label' => 'Задания', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        ['url' => '/teacher/analytics', 'match' => 'teacher/analytics*', 'label' => 'Аналитика', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['url' => '/teacher/oge/teachers', 'match' => 'teacher/oge*', 'label' => 'ОГЭ', 'icon' => 'M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z'],
        ['url' => '/teacher/earnings', 'match' => 'teacher/earnings*', 'label' => 'Заработок', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
@endphp

@foreach($navItems as $item)
    @php
        $isActive = isset($item['exact']) && $item['exact']
            ? request()->is($item['match']) && !request()->is($item['match'] . '/*')
            : request()->is($item['match']);
    @endphp
    <a href="{{ $item['url'] }}"
       class="group relative flex items-center justify-center w-11 h-11 rounded-full transition-all duration-200
              {{ $isActive
                  ? 'bg-gray-900 text-white shadow-sm shadow-black/10'
                  : 'text-gray-400 hover:bg-gray-100/90 hover:text-gray-700' }}"
       title="{{ $item['label'] }}">
        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
        </svg>
        {{-- Tooltip --}}
        <span class="tsh-nav-label hidden lg:block absolute left-full ml-3 px-2.5 py-1 bg-gray-900 text-white text-xs font-medium rounded-lg whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity shadow-lg z-50">
            {{ $item['label'] }}
        </span>
    </a>
@endforeach
