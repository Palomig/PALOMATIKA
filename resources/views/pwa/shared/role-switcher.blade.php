@auth
  @php
    $__rs_user = auth()->user();
    $__rs_role = $__rs_user?->role;
  @endphp
  @if (in_array($__rs_role, ['teacher', 'admin'], true))
    @php
      $__rs_base = (string) config('app.base_domain', 'palomatika.ru');
      $__rs_host = request()->getHost();
      $__rs_isTeacher = str_starts_with($__rs_host, 'teacher.');
      $__rs_isStudent = str_starts_with($__rs_host, 'student.');
      $__rs_studentUrl = 'https://student.' . $__rs_base . '/';
      $__rs_teacherUrl = 'https://teacher.' . $__rs_base . '/dashboard';
      $__rs_exam = (string) (session('view_as_exam') ?? 'oge');
      $__rs_vprGrade = (int) (session('view_as_vpr_grade') ?? 5);
    @endphp
    <div class="pwa-role-switcher-shell">
      <div class="pwa-role-switcher" role="group" aria-label="Режим просмотра">
        <a href="{{ $__rs_studentUrl }}"
           class="pwa-role-switcher__btn @if($__rs_isStudent) is-active @endif"
           @if($__rs_isStudent) aria-current="true" @endif>Ученик</a>
        <a href="{{ $__rs_teacherUrl }}"
           class="pwa-role-switcher__btn @if($__rs_isTeacher) is-active @endif"
           @if($__rs_isTeacher) aria-current="true" @endif>Учитель</a>
      </div>

      @if($__rs_isStudent)
        <div class="pwa-context-switcher" aria-label="Экзамен ученика">
          <div class="pwa-context-switcher__row">
            <form method="POST" action="{{ route('view-as.student.exam', ['exam' => 'oge']) }}">
              @csrf
              <button type="submit" class="pwa-context-switcher__btn @if($__rs_exam === 'oge') is-active @endif">ОГЭ</button>
            </form>
            <form method="POST" action="{{ route('view-as.student.exam', ['exam' => 'vpr']) }}">
              @csrf
              <button type="submit" class="pwa-context-switcher__btn @if($__rs_exam === 'vpr') is-active @endif">ВПР</button>
            </form>
          </div>

          @if($__rs_exam === 'vpr')
            <div class="pwa-context-switcher__row pwa-context-switcher__row--compact">
              @foreach([5, 6, 7, 8] as $__rs_grade)
                <form method="POST" action="{{ route('view-as.student.vpr-grade', ['grade' => $__rs_grade]) }}">
                  @csrf
                  <button type="submit" class="pwa-context-switcher__btn pwa-context-switcher__btn--grade @if($__rs_vprGrade === $__rs_grade) is-active @endif">{{ $__rs_grade }}</button>
                </form>
              @endforeach
            </div>
          @endif
        </div>
      @endif
    </div>
  @endif
@endauth
