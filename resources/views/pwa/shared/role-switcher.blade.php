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
    @endphp
    <div class="pwa-role-switcher" role="group" aria-label="Режим просмотра">
      <a href="{{ $__rs_studentUrl }}"
         class="pwa-role-switcher__btn @if($__rs_isStudent) is-active @endif"
         @if($__rs_isStudent) aria-current="true" @endif>Ученик</a>
      <a href="{{ $__rs_teacherUrl }}"
         class="pwa-role-switcher__btn @if($__rs_isTeacher) is-active @endif"
         @if($__rs_isTeacher) aria-current="true" @endif>Учитель</a>
    </div>
  @endif
@endauth
