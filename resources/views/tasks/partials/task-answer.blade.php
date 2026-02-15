@if($showTaskAnswer ?? false)
    <div class="mt-3 text-xs">
        <span class="text-slate-500">Ответ:</span>
        @if(isset($taskAnswer) && $taskAnswer !== '')
            <span class="text-emerald-400 font-mono ml-1">{{ $taskAnswer }}</span>
        @else
            <span class="text-amber-400 ml-1">нет в базе</span>
        @endif
    </div>
@endif
