import test from 'node:test';
import assert from 'node:assert/strict';

import {
  pickLeadingTask,
  computeVisibleRatio,
  isScrollSettled,
} from '../../public/js/oge-task-visibility.mjs';

function rect(left, top, right, bottom) {
  return { left, top, right, bottom, width: right - left, height: bottom - top };
}

test('computeVisibleRatio subtracts bottom bar overlap from visible area', () => {
  const viewport = rect(0, 0, 1000, 1000);
  const card = rect(0, 700, 1000, 1000); // 300k px area
  const blocker = rect(0, 900, 1000, 980); // overlaps 80k px

  const ratio = computeVisibleRatio(card, viewport, [blocker]);
  assert.equal(Number(ratio.toFixed(4)), Number((220000 / 300000).toFixed(4)));
});

test('pickLeadingTask prefers fully visible card over partially hidden one', () => {
  const viewport = rect(0, 0, 1000, 1000);
  const blocker = rect(0, 900, 1000, 980);

  const tasks = [
    { taskNumber: 7, rect: rect(0, 120, 1000, 420) },
    { taskNumber: 8, rect: rect(0, 460, 1000, 860) }, // fully visible and closer to center
    { taskNumber: 9, rect: rect(0, 700, 1000, 1040) }, // clipped by viewport and blocker
  ];

  const lead = pickLeadingTask(tasks, viewport, [blocker]);
  assert.ok(lead);
  assert.equal(lead.taskNumber, 8);
  assert.equal(lead.isFullyVisible, true);
});

test('isScrollSettled returns true only after configured pause', () => {
  assert.equal(isScrollSettled(1000, 650, 400), false);
  assert.equal(isScrollSettled(1201, 800, 400), true);
});
