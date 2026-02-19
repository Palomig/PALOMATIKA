import test from 'node:test';
import assert from 'node:assert/strict';

import {
  applyCommitSuccessToCard,
  setAllCardsLocked,
} from '../../public/js/oge-attempt-ui.mjs';

function classList(initial = []) {
  const values = new Set(initial);
  return {
    add: (...classes) => classes.forEach((c) => values.add(c)),
    remove: (...classes) => classes.forEach((c) => values.delete(c)),
    contains: (value) => values.has(value),
  };
}

function makeCard() {
  const input = { disabled: false };
  const ok = { disabled: false };
  const edit = { disabled: false, classList: classList(['hidden']) };

  return {
    input,
    ok,
    edit,
    querySelector(selector) {
      if (selector === '.js-answer-input') return input;
      if (selector === '.js-answer-ok') return ok;
      if (selector === '.js-answer-edit') return edit;
      return null;
    },
  };
}

test('custom commit success reveals edit button and locks input', () => {
  const card = makeCard();

  applyCommitSuccessToCard(card);

  assert.equal(card.input.disabled, true);
  assert.equal(card.edit.classList.contains('hidden'), false);
});

test('submit lock disables all inputs and finish button', () => {
  const cardA = makeCard();
  const cardB = makeCard();
  const finishButton = { disabled: false };

  setAllCardsLocked([cardA, cardB], finishButton, true);

  assert.equal(cardA.input.disabled, true);
  assert.equal(cardA.ok.disabled, true);
  assert.equal(cardA.edit.disabled, true);
  assert.equal(cardB.input.disabled, true);
  assert.equal(finishButton.disabled, true);
});
