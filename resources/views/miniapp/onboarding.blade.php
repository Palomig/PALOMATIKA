@extends('layouts.miniapp')
@section('title', 'Знакомство — palomatika')

@push('styles')
  .onb-page {
    min-height: 100dvh; max-width: 480px; margin: 0 auto;
    padding: calc(24px + var(--safe-top)) 20px calc(32px + var(--safe-bottom));
    display: flex; flex-direction: column;
  }

  .progress-track { display: flex; gap: 6px; margin-bottom: 36px; opacity: 0; animation: fadeDown 0.4s ease 0s forwards; }
  .progress-seg { height: 3px; flex: 1; background: var(--border); border-radius: 99px; transition: background 0.3s; }
  .progress-seg.active { background: var(--accent); }
  .progress-seg.done   { background: var(--accent); opacity: 0.4; }

  .onb-header { margin-bottom: 36px; opacity: 0; animation: fadeUp 0.4s ease 0.1s forwards; }
  .step-label {
    font-size: 11px; font-weight: 700; letter-spacing: 0.15em;
    text-transform: uppercase; color: var(--muted); margin-bottom: 10px;
  }
  .onb-header h1 { font-family: var(--display); font-size: 26px; color: var(--text); line-height: 1.2; margin-bottom: 8px; }
  .onb-header p { font-size: 14px; color: var(--muted); font-weight: 600; line-height: 1.5; }

  .form { display: flex; flex-direction: column; gap: 16px; flex: 1; opacity: 0; animation: fadeUp 0.4s ease 0.2s forwards; }
  .field { display: flex; flex-direction: column; gap: 7px; }
  .field-label { font-size: 12px; font-weight: 800; color: var(--text); letter-spacing: 0.03em; }
  .field-input {
    width: 100%; background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 15px 16px;
    font-family: var(--body); font-size: 16px; font-weight: 700; color: var(--text);
    outline: none; transition: border-color 0.2s;
  }
  .field-input::placeholder { color: var(--muted2); font-weight: 600; }
  .field-input:focus { border-color: var(--accent); }

  .class-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .chips { display: flex; flex-wrap: wrap; gap: 8px; }
  .chip {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 10px; padding: 10px 16px;
    font-family: var(--body); font-size: 15px; font-weight: 700; color: var(--muted);
    cursor: pointer; transition: border-color 0.15s, color 0.15s, background 0.15s;
    user-select: none; min-width: 48px; text-align: center;
  }
  .chip:active, .chip.selected { border-color: var(--accent); color: var(--accent); background: var(--accent-bg); }

  .city-wrap { position: relative; }
  .city-default-badge {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    font-size: 10px; font-weight: 800; color: var(--accent);
    background: var(--accent-bg); padding: 3px 8px; border-radius: 6px;
    pointer-events: none;
  }

  .btn-submit {
    width: 100%; background: var(--accent); color: #fff;
    border: none; border-radius: 14px; padding: 18px;
    font-family: var(--body); font-size: 16px; font-weight: 800;
    cursor: pointer; margin-top: 8px; transition: background 0.15s, transform 0.12s;
  }
  .btn-submit:active { filter: brightness(0.9); transform: scale(0.98); }
  .btn-submit:disabled { opacity: 0.4; cursor: default; transform: none; }
  .form-note { text-align: center; font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 6px; }

  @media (max-height: 500px) { .onb-header { margin-bottom: 20px; } .form { gap: 12px; } }
@endpush

@section('body')
<div class="onb-page" x-data="onboardingPage()">

  <div class="progress-track">
    <div class="progress-seg done"></div>
    <div class="progress-seg active"></div>
    <div class="progress-seg"></div>
  </div>

  <div class="onb-header">
    <div class="step-label">Шаг 2 из 3 · Знакомство</div>
    <h1>Расскажи о себе</h1>
    <p>Чтобы подобрать подходящие задания и отслеживать твой прогресс</p>
  </div>

  <form class="form" @submit.prevent="handleSubmit()">

    <div class="field">
      <label class="field-label" for="name">Твоё имя</label>
      <input class="field-input" id="name" type="text" placeholder="Например, Алексей"
        autocomplete="given-name" inputmode="text" x-model="name" minlength="2">
    </div>

    <div class="field">
      <div class="field-label">Класс</div>
      <div class="class-row">
        <div>
          <div class="chips">
            <div class="chip selected">9</div>
          </div>
        </div>
        <div>
          <div class="chips">
            <template x-for="l in ['А','Б','В','Г','Д']" :key="l">
              <div class="chip" :class="{ 'selected': letter === l }" @click="letter = l" x-text="l"></div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div class="field">
      <label class="field-label" for="school">Номер школы</label>
      <input class="field-input" id="school" type="text" placeholder="Например, 3"
        inputmode="numeric" x-model="school">
    </div>

    <div class="field">
      <label class="field-label" for="city">Город</label>
      <div class="city-wrap">
        <input class="field-input" id="city" type="text" placeholder="Чехов"
          autocomplete="address-level2" inputmode="text" style="padding-right: 100px;"
          x-model="city">
        <div class="city-default-badge" x-show="!city">по умолчанию</div>
      </div>
    </div>

    <button type="submit" class="btn-submit" :disabled="!isValid || submitting">
      <span x-text="submitting ? 'Сохраняю...' : 'Продолжить →'"></span>
    </button>

    <div class="form-note">Данные нужны только для подготовки к экзамену</div>

  </form>

</div>
@endsection

@push('scripts')
<script>
function onboardingPage() {
  return {
    name: '{{ auth()->user()?->name ?? "" }}',
    letter: null,
    school: '',
    city: '',
    submitting: false,

    get isValid() {
      return this.name.trim().length >= 2 && this.letter && this.school.trim().length >= 1;
    },

    async handleSubmit() {
      if (!this.isValid || this.submitting) return;
      this.submitting = true;
      try {
        const res = await window.fetchPost('/tg/onboarding', {
          name: this.name.trim(),
          grade_num: 9,
          grade_letter: this.letter,
          school_number: this.school.trim(),
          city: this.city.trim() || 'Чехов',
        });
        const data = await res.json();
        if (res.ok) {
          window.location.href = '/tg/dashboard';
        } else {
          alert(data.message || 'Ошибка сохранения');
        }
      } catch (e) {
        alert('Ошибка соединения');
      } finally {
        this.submitting = false;
      }
    },
  };
}
</script>
@endpush
