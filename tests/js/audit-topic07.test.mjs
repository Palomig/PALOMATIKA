import test from 'node:test';
import assert from 'node:assert/strict';

import {
  auditChoiceTask,
  auditSimpleChoiceTask,
  parsePointLookupInstruction,
  validateNonComputableAnswer,
} from '../../scripts/audit-topic07-lib.mjs';

test('auditChoiceTask finds exactly one true inequality option and matches answer', () => {
  const result = auditChoiceTask({
    id: 't07-b1-z1-i5',
    type: 'choice',
    point_value: 5.3,
    point_label: 'a',
    answer: 'b',
    options: [
      { id: 'a', label: '$4 - a > 0$' },
      { id: 'b', label: '$a - 7 < 0$' },
      { id: 'c', label: '$a - 8 > 0$' },
      { id: 'd', label: '$8 - a < 0$' },
    ],
  });

  assert.equal(result.trueOptionIds.length, 1);
  assert.equal(result.trueOptionIds[0], 'b');
  assert.equal(result.status, 'ok');
});

test('auditSimpleChoiceTask resolves the correct option from named point values', () => {
  const result = auditSimpleChoiceTask({
    id: 't07-b1-z3',
    instruction:
      'На координатной прямой отмечены числа p, q и r. Какая из разностей q − p, q − r, r − p положительна?',
    points: [
      { label: 'r', value: 0 },
      { label: 'q', value: 1 },
      { label: 'p', value: 3 },
    ],
    options: ['$q - p$', '$q - r$', '$r - p$', 'невозможно определить'],
  });

  assert.equal(result.trueOptionIds.length, 1);
  assert.equal(result.trueOptionIds[0], 'b');
  assert.equal(result.expectedAnswer, 'b');
});

test('auditChoiceTask supports implicit multiplication in options', () => {
  const result = auditChoiceTask({
    id: 't07-b1-z2-i1',
    type: 'choice',
    answer: 'a',
    points: [
      { label: 'x', value: 4 },
      { label: 'y', value: -1 },
    ],
    options: [
      { id: 'a', label: '$x + y < 0$' },
      { id: 'b', label: '$xy < 0$' },
      { id: 'c', label: '$y - x > 0$' },
      { id: 'd', label: '$x 2y > 0$' },
    ],
  });

  assert.equal(result.trueOptionIds.length, 1);
  assert.equal(result.trueOptionIds[0], 'b');
});

test('parsePointLookupInstruction infers ordered point mapping from fractions in instruction', () => {
  const parsed = parsePointLookupInstruction(
    'На координатной прямой точки A, B, C и D соответствуют числам $-\\frac{3}{8}$; $\\frac{3}{10}$; $-\\frac{3}{7}$; $\\frac{3}{14}$. Какой точке соответствует число $\\frac{3}{10}$?'
  );

  assert.equal(parsed.expectedAnswer, 'd');
});

test('validateNonComputableAnswer reports missing answer', () => {
  const result = validateNonComputableAnswer({
    id: 't07-b2-z4-i1',
    type: 'false_statements',
    answer: '',
    options: [
      { id: 'a', label: 'A' },
      { id: 'b', label: 'B' },
      { id: 'c', label: 'C' },
      { id: 'd', label: 'D' },
    ],
  });

  assert.equal(result.status, 'error');
  assert.match(result.message, /empty answer/i);
});
