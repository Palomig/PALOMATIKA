export function setCardLocked(card, isLocked) {
  if (!card) return;

  const input = card.querySelector('.js-answer-input');
  const ok = card.querySelector('.js-answer-ok');
  const edit = card.querySelector('.js-answer-edit');

  if (input) input.disabled = isLocked;
  if (ok) ok.disabled = isLocked;
  if (edit) edit.disabled = isLocked;
}

export function setAllCardsLocked(cards, finishButton, isLocked) {
  (cards || []).forEach((card) => setCardLocked(card, isLocked));
  if (finishButton) finishButton.disabled = isLocked;
}

export function applyCommitSuccessToCard(card) {
  if (!card) return;

  const input = card.querySelector('.js-answer-input');
  const edit = card.querySelector('.js-answer-edit');

  if (input) input.disabled = true;
  if (edit) edit.classList.remove('hidden');
}

export function bindOptionPanelsToAnswerInput(card) {
  if (!card) return;

  const input = card.querySelector('.js-answer-input');
  const panels = [...(card.querySelectorAll?.('[data-option-index]') || [])];
  if (!input || panels.length === 0) return;

  const applyPanelValue = (panel) => {
    if (!panel || input.disabled) return;

    const value = String(panel.dataset?.optionIndex || '').trim();
    if (value === '') return;

    input.value = value;
    if (typeof input.dispatchEvent === 'function' && typeof Event === 'function') {
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    input.focus?.();
  };

  panels.forEach((panel) => {
    panel.addEventListener('click', () => applyPanelValue(panel));
    panel.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault?.();
      applyPanelValue(panel);
    });
  });
}

export function resolveAttemptTaskNumber(card) {
  if (!card) return 0;

  const attemptTaskRaw = Number(card.dataset.attemptTaskNumber || 0);
  if (Number.isFinite(attemptTaskRaw) && attemptTaskRaw > 0) {
    return attemptTaskRaw;
  }

  const fallbackTaskRaw = Number(card.dataset.taskNumber || 0);
  return Number.isFinite(fallbackTaskRaw) && fallbackTaskRaw > 0 ? fallbackTaskRaw : 0;
}

export function shouldLockForConflict(error) {
  return Number(error?.status || 0) === 409;
}
