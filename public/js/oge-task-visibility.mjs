function areaOfRect(rect) {
  if (!rect) return 0;
  const width = Math.max(0, (rect.right ?? 0) - (rect.left ?? 0));
  const height = Math.max(0, (rect.bottom ?? 0) - (rect.top ?? 0));
  return width * height;
}

function intersectRect(a, b) {
  if (!a || !b) return null;

  const left = Math.max(a.left, b.left);
  const top = Math.max(a.top, b.top);
  const right = Math.min(a.right, b.right);
  const bottom = Math.min(a.bottom, b.bottom);

  if (right <= left || bottom <= top) return null;

  return {
    left,
    top,
    right,
    bottom,
    width: right - left,
    height: bottom - top,
  };
}

function centerDistanceToViewport(rect, viewportRect) {
  const cardCenterY = rect.top + rect.height / 2;
  const viewportCenterY = viewportRect.top + viewportRect.height / 2;
  return Math.abs(cardCenterY - viewportCenterY);
}

function isFullyVisible(rect, viewportRect, blockerRects = []) {
  const inViewport =
    rect.top >= viewportRect.top &&
    rect.bottom <= viewportRect.bottom &&
    rect.left >= viewportRect.left &&
    rect.right <= viewportRect.right;

  if (!inViewport) return false;

  return blockerRects.every((blockerRect) => !intersectRect(rect, blockerRect));
}

export function computeVisibleRatio(cardRect, viewportRect, blockerRects = []) {
  const cardArea = areaOfRect(cardRect);
  if (cardArea <= 0) return 0;

  const visibleInsideViewport = intersectRect(cardRect, viewportRect);
  let visibleArea = areaOfRect(visibleInsideViewport);

  if (visibleArea <= 0) return 0;

  for (const blockerRect of blockerRects) {
    const overlap = intersectRect(visibleInsideViewport, blockerRect);
    visibleArea -= areaOfRect(overlap);
  }

  visibleArea = Math.max(0, visibleArea);
  return visibleArea / cardArea;
}

export function pickLeadingTask(tasks, viewportRect, blockerRects = []) {
  if (!Array.isArray(tasks) || tasks.length === 0) return null;

  const enriched = tasks
    .map((task) => {
      const ratio = computeVisibleRatio(task.rect, viewportRect, blockerRects);
      const full = isFullyVisible(task.rect, viewportRect, blockerRects);
      const centerDistance = centerDistanceToViewport(task.rect, viewportRect);
      return {
        ...task,
        visibleRatio: ratio,
        isFullyVisible: full,
        centerDistance,
      };
    })
    .filter((task) => task.visibleRatio > 0);

  if (enriched.length === 0) return null;

  const fullyVisible = enriched.filter((task) => task.isFullyVisible);

  if (fullyVisible.length > 0) {
    fullyVisible.sort((a, b) => {
      if (a.centerDistance !== b.centerDistance) {
        return a.centerDistance - b.centerDistance;
      }
      if (a.visibleRatio !== b.visibleRatio) {
        return b.visibleRatio - a.visibleRatio;
      }
      return a.taskNumber - b.taskNumber;
    });
    return fullyVisible[0];
  }

  enriched.sort((a, b) => {
    if (a.visibleRatio !== b.visibleRatio) {
      return b.visibleRatio - a.visibleRatio;
    }
    if (a.centerDistance !== b.centerDistance) {
      return a.centerDistance - b.centerDistance;
    }
    return a.taskNumber - b.taskNumber;
  });

  return enriched[0];
}

export function isScrollSettled(nowMs, lastScrollAtMs, settleMs) {
  return nowMs - lastScrollAtMs >= settleMs;
}

export function domRectToPlain(rect) {
  return {
    left: rect.left,
    top: rect.top,
    right: rect.right,
    bottom: rect.bottom,
    width: rect.width,
    height: rect.height,
  };
}

export function viewportRectFromWindow(win = window) {
  return {
    left: 0,
    top: 0,
    right: win.innerWidth,
    bottom: win.innerHeight,
    width: win.innerWidth,
    height: win.innerHeight,
  };
}
