{{-- Доска зовущих: строки ведёт супер-админ вручную (teacher.palomatika.ru/friends),
     своя у каждой акции. --}}
<div class="fr-blk">
  <div class="fr-h">Доска зовущих</div>
  @if($board['top']->isEmpty())
    <div class="fr-empty">
      <div class="fr-empty-i">🏅</div>
      <div class="fr-n">Пока никто никого не позвал</div>
      <div class="fr-s" style="margin-top:4px">Позовёшь первым — окажешься на первом месте</div>
    </div>
  @else
    @foreach($board['top'] as $r)
      <div class="fr-lb {{ $r['you'] ? 'you' : '' }}">
        <div class="fr-lp">{{ [1 => '🥇', 2 => '🥈', 3 => '🥉'][$r['pos']] ?? $r['pos'] }}</div>
        <div class="fr-ln2">{{ $r['name'] }}@if($r['grade'])<small>{{ $r['grade'] }} класс</small>@endif</div>
        <div class="fr-lc">{{ $svc->friendsWord($r['friends']) }}</div>
      </div>
    @endforeach
    @if($board['me'])
      <div class="fr-lb you">
        <div class="fr-lp">{{ $board['me']['pos'] }}</div>
        <div class="fr-ln2">Ты</div>
        <div class="fr-lc">{{ $svc->friendsWord($board['me']['friends']) }}</div>
      </div>
    @endif
    <div class="fr-foot">Всего ребята привели {{ $svc->friendsWord($board['totalFriends']) }}</div>
  @endif
</div>
