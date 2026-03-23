<nav class="p-nav">
  <a href="{{ route('parent.dashboard') }}" class="p-nav-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
    <span class="p-nav-icon">👶</span>
    <span>Дети</span>
  </a>
  <a href="{{ route('parent.home') }}" class="p-nav-item">
    <span class="p-nav-icon">👤</span>
    <span>Профиль</span>
  </a>
</nav>
