{{-- resources/views/miniapp/partials/migration-banner.blade.php --}}
<div style="background:var(--accent-bg);border:1px solid var(--accent-bd);border-radius:14px;padding:14px 16px;display:flex;gap:12px;align-items:flex-start;">
  <span style="font-size:20px;flex-shrink:0;">🚀</span>
  <div>
    <div style="font-family:var(--display);font-size:13px;color:var(--accent);margin-bottom:4px;">Palomatika переезжает!</div>
    <div style="font-size:11px;color:var(--muted);line-height:1.5;margin-bottom:10px;">
      Мы запустили отдельное приложение. Перейдите и установите его на телефон.
    </div>
    <a href="https://student.palomatika.ru/migrate?token={{ $migrationToken ?? '' }}"
       style="display:inline-block;background:var(--accent);color:#fff;padding:8px 16px;border-radius:10px;font-family:var(--display);font-size:12px;text-decoration:none;">
      Перейти в новое приложение →
    </a>
  </div>
</div>
