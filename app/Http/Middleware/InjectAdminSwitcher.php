<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Injects a floating admin role-switcher widget before </body>
 * on every HTML page for admin users.
 */
class InjectAdminSwitcher
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip non-injectable response types
        if (
            $response instanceof RedirectResponse
            || $response instanceof BinaryFileResponse
            || $response instanceof StreamedResponse
        ) {
            return $response;
        }

        // Only inject into HTML responses for authenticated admin users
        if (
            !$request->user()
            || $request->user()->role !== 'admin'
            || !str_contains((string) $response->headers->get('Content-Type', ''), 'text/html')
        ) {
            return $response;
        }

        $content = $response->getContent();
        if (!$content || !str_contains($content, '</body>')) {
            return $response;
        }

        $viewAsRole = session('view_as_role');
        $csrfToken = csrf_token();
        $widget = $this->buildWidget($viewAsRole, $csrfToken);

        $content = str_replace('</body>', $widget . '</body>', $content);
        $response->setContent($content);

        return $response;
    }

    private function buildWidget(?string $viewAsRole, string $csrfToken): string
    {
        $studentActive = $viewAsRole === 'student' ? 'border-color:#ff6b6b;color:#ff6b6b;background:rgba(255,107,107,0.1)' : '';
        $teacherActive = $viewAsRole === 'teacher' ? 'border-color:#ff6b6b;color:#ff6b6b;background:rgba(255,107,107,0.1)' : '';
        $roleLabel = $viewAsRole === 'teacher' ? 'учитель' : ($viewAsRole === 'student' ? 'ученик' : '');
        $badgeHtml = $viewAsRole
            ? '<div style="position:absolute;top:-4px;right:-4px;width:10px;height:10px;background:#ff6b6b;border-radius:50%;border:2px solid #1a1a2e;"></div>'
            : '';
        $clearBtnHtml = $viewAsRole
            ? '<form method="POST" action="/view-as/clear" style="margin:0">
                <input type="hidden" name="_token" value="' . $csrfToken . '">
                <button type="submit" style="width:100%;padding:6px 12px;border-radius:8px;border:1px solid #374151;background:transparent;color:#9ca3af;font-size:12px;cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor=\'#6b7280\';this.style.color=\'#fff\'" onmouseout="this.style.borderColor=\'#374151\';this.style.color=\'#9ca3af\'">Сброс</button>
               </form>'
            : '';
        $bannerHtml = $viewAsRole
            ? '<div style="padding:8px 12px;background:rgba(255,107,107,0.1);border-radius:8px;margin-bottom:8px;font-size:11px;color:#ff6b6b;text-align:center">Режим: <b>' . $roleLabel . '</b></div>'
            : '';

        return <<<HTML
<!-- Admin Role Switcher Widget -->
<div id="admin-switcher" style="position:fixed;bottom:20px;right:20px;z-index:99999;font-family:'Inter',sans-serif">
    <!-- Toggle button -->
    <button onclick="document.getElementById('admin-switcher-panel').classList.toggle('hidden')" style="position:relative;width:44px;height:44px;border-radius:50%;background:#252542;border:1px solid #374151;color:#9ca3af;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.3);transition:all .2s;margin-left:auto" onmouseover="this.style.borderColor='#ff6b6b';this.style.color='#ff6b6b'" onmouseout="this.style.borderColor='#374151';this.style.color='#9ca3af'">
        {$badgeHtml}
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    </button>
    <!-- Panel -->
    <div id="admin-switcher-panel" class="hidden" style="position:absolute;bottom:54px;right:0;width:200px;background:#1a1a2e;border:1px solid #374151;border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(0,0,0,0.4)">
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px">Просмотр как</div>
        {$bannerHtml}
        <div style="display:flex;gap:6px;margin-bottom:8px">
            <form method="POST" action="/view-as/student" style="flex:1;margin:0">
                <input type="hidden" name="_token" value="{$csrfToken}">
                <button type="submit" style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #374151;background:transparent;color:#d1d5db;font-size:12px;font-weight:500;cursor:pointer;transition:all .2s;{$studentActive}" onmouseover="if(!this.style.borderColor||this.style.borderColor==='rgb(55, 65, 81)')this.style.borderColor='#6b7280'" onmouseout="if('{$viewAsRole}'!=='student')this.style.borderColor='#374151'">Ученик</button>
            </form>
            <form method="POST" action="/view-as/teacher" style="flex:1;margin:0">
                <input type="hidden" name="_token" value="{$csrfToken}">
                <button type="submit" style="width:100%;padding:8px 12px;border-radius:8px;border:1px solid #374151;background:transparent;color:#d1d5db;font-size:12px;font-weight:500;cursor:pointer;transition:all .2s;{$teacherActive}" onmouseover="if(!this.style.borderColor||this.style.borderColor==='rgb(55, 65, 81)')this.style.borderColor='#6b7280'" onmouseout="if('{$viewAsRole}'!=='teacher')this.style.borderColor='#374151'">Учитель</button>
            </form>
        </div>
        {$clearBtnHtml}
    </div>
</div>
<style>#admin-switcher .hidden{display:none!important}</style>
HTML;
    }
}
