{{-- Карточка задачи банка 1-й части: используется и в плоском списке группы,
     и внутри подтипа. Ждёт $task, $selectedTopic, $isPremium. --}}
          <div class="task-item">
            @php
              $svg = is_string($task['svg'] ?? null) ? $task['svg'] : '';
              $image = is_string($task['image'] ?? null) ? $task['image'] : '';
            @endphp

            @php
              $svgMarkup = $svg !== '' ? $svg : (\Illuminate\Support\Str::startsWith($image, '<svg') ? $image : '');
              $isWide = (int)$selectedTopic === 11;
            @endphp
            @if($svgMarkup !== '')
              <div style="margin-bottom:10px; border:1px solid var(--border); border-radius:10px; background:#0a1628; padding:8px;{{ $isWide ? ' overflow-x:auto; -webkit-overflow-scrolling:touch;' : '' }}">
                <div style="{{ $isWide ? 'min-width:600px;' : '' }}">{!! $svgMarkup !!}</div>
              </div>
            @endif

            @if(!empty($task['question']))
              <div class="task-item-text" style="margin-bottom:6px; color:var(--muted); font-size:12px;">{{ $task['question'] }}</div>
            @endif

            @if(!empty($task['drawing']))
              {{-- Чертёж вынесен из таблицы условия и показан крупно сверху --}}
              <div class="fipi-drawing">{!! $task['drawing'] !!}</div>
            @endif

            @if(!empty($task['html']))
              {{-- Банк ФИПИ: условие уже свёрстано — формулы в KaTeX,
                   чертежи инлайновыми SVG. Экранировать нельзя. --}}
              <div class="task-item-text fipi-html">{!! $task['html'] !!}</div>
            @elseif($task['text'] !== '')
              <div class="task-item-text">{!! nl2br(e($task['text'])) !!}</div>
            @elseif(!empty($task['expression']))
              <div class="task-item-text" style="font-size:15px;">$${{ $task['expression'] }}$$</div>
            @endif

            @if(!empty($task['options']) && is_array($task['options']))
              <div style="margin-top:8px; display:flex; flex-direction:column; gap:6px;">
                @foreach($task['options'] as $opt)
                  @if(is_array($opt) && isset($opt['html']))
                    <div style="display:flex; gap:8px; align-items:flex-start;">
                      <span style="color:var(--muted); font-size:12px; flex:0 0 auto;">{{ $opt['n'] ?? $loop->iteration }})</span>
                      <div class="fipi-html" style="min-width:0;">{!! $opt['html'] !!}</div>
                    </div>
                  @else
                    <span style="padding:4px 10px; border:1px solid var(--border); border-radius:8px; font-size:12px; color:var(--muted);">{{ \App\Support\OptionLabelFormatter::optionLabel($opt, $loop->index) }}. {{ \App\Support\OptionLabelFormatter::optionText($opt) }}</span>
                  @endif
                @endforeach
              </div>
            @endif

            @if(!empty($task['answer']))
              <div class="answer-row">
                <span class="answer-label">Ответ:</span>
                @if($isPremium)
                  <span class="answer-value">{{ \App\Support\OptionLabelFormatter::formatAnswer($task['answer'], is_array($task['options'] ?? null) ? $task['options'] : []) }}</span>
                @else
                  <span class="answer-blur">{{ \App\Support\OptionLabelFormatter::formatAnswer($task['answer'], is_array($task['options'] ?? null) ? $task['options'] : []) }}</span>
                  <span class="premium-cta" @click="showPremium = true">Premium</span>
                @endif
              </div>
            @endif

            @if(!empty($task['id']))
              <div class="task-item-meta">#{{ $task['id'] }}</div>
            @endif
          </div>
