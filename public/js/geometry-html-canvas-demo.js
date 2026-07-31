(function () {
  const stage = document.getElementById('geometryStage');
  const canvas = document.getElementById('geometryCanvas');
  const ctx = canvas.getContext('2d');
  const slider = document.getElementById('pointSlider');
  const dragHandle = document.getElementById('dragHandle');
  const answerCard = document.getElementById('answerCard');
  const answerInput = document.getElementById('angleAnswer');
  const feedback = document.getElementById('feedback');
  const arcChip = document.getElementById('arcChip');
  const modeButtons = Array.from(document.querySelectorAll('[data-mode]'));
  const labels = {
    A: document.querySelector('[data-point="A"]'),
    B: document.querySelector('[data-point="B"]'),
    D: document.querySelector('[data-point="D"]'),
  };

  const state = {
    mode: 'task',
    dragging: false,
    angleD: Number(slider.value),
    dpr: Math.max(1, window.devicePixelRatio || 1),
    rect: null,
  };

  function polar(center, radius, degrees) {
    const rad = (degrees * Math.PI) / 180;
    return {
      x: center.x + Math.cos(rad) * radius,
      y: center.y + Math.sin(rad) * radius,
    };
  }

  function position(el, point, offsetX = 0, offsetY = 0) {
    el.style.left = `${point.x + offsetX}px`;
    el.style.top = `${point.y + offsetY}px`;
  }

  function scene() {
    const width = state.rect.width;
    const height = state.rect.height;
    const radius = Math.min(width, height) * 0.33;
    const center = { x: width * 0.49, y: height * 0.52 };
    const A = polar(center, radius, 150);
    const B = polar(center, radius, 30);
    const D = polar(center, radius, state.angleD);
    return { width, height, radius, center, A, B, D };
  }

  function resize() {
    state.rect = canvas.getBoundingClientRect();
    state.dpr = Math.max(1, window.devicePixelRatio || 1);
    canvas.width = Math.round(state.rect.width * state.dpr);
    canvas.height = Math.round(state.rect.height * state.dpr);
    ctx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
    render();
  }

  function drawPoint(point, color) {
    ctx.beginPath();
    ctx.arc(point.x, point.y, 5.5, 0, Math.PI * 2);
    ctx.fillStyle = color;
    ctx.fill();
  }

  function drawAngleMark(D, A, B) {
    const start = Math.atan2(A.y - D.y, A.x - D.x);
    const end = Math.atan2(B.y - D.y, B.x - D.x);
    ctx.save();
    ctx.strokeStyle = '#d94f30';
    ctx.lineWidth = 3;
    ctx.beginPath();
    ctx.arc(D.x, D.y, 38, start, end, false);
    ctx.stroke();
    ctx.restore();
  }

  function draw() {
    const s = scene();
    ctx.clearRect(0, 0, s.width, s.height);

    ctx.fillStyle = '#fffaf0';
    ctx.fillRect(0, 0, s.width, s.height);

    ctx.strokeStyle = 'rgba(31,42,46,.08)';
    ctx.lineWidth = 1;
    for (let x = 0; x < s.width; x += 28) {
      ctx.beginPath();
      ctx.moveTo(x, 0);
      ctx.lineTo(x, s.height);
      ctx.stroke();
    }
    for (let y = 0; y < s.height; y += 28) {
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.lineTo(s.width, y);
      ctx.stroke();
    }

    ctx.strokeStyle = '#274c5b';
    ctx.lineWidth = 4;
    ctx.beginPath();
    ctx.arc(s.center.x, s.center.y, s.radius, 0, Math.PI * 2);
    ctx.stroke();

    ctx.strokeStyle = '#147c72';
    ctx.lineWidth = 8;
    ctx.beginPath();
    ctx.arc(s.center.x, s.center.y, s.radius, (30 * Math.PI) / 180, (150 * Math.PI) / 180, false);
    ctx.stroke();

    ctx.strokeStyle = '#1f2a2e';
    ctx.lineWidth = 3;
    ctx.beginPath();
    ctx.moveTo(s.A.x, s.A.y);
    ctx.lineTo(s.D.x, s.D.y);
    ctx.lineTo(s.B.x, s.B.y);
    ctx.stroke();

    ctx.strokeStyle = 'rgba(217,79,48,.28)';
    ctx.setLineDash([8, 8]);
    ctx.beginPath();
    ctx.moveTo(s.center.x, s.center.y);
    ctx.lineTo(s.A.x, s.A.y);
    ctx.moveTo(s.center.x, s.center.y);
    ctx.lineTo(s.B.x, s.B.y);
    ctx.stroke();
    ctx.setLineDash([]);

    drawAngleMark(s.D, s.A, s.B);
    drawPoint(s.A, '#274c5b');
    drawPoint(s.B, '#274c5b');
    drawPoint(s.D, '#d94f30');

    if (state.mode === 'hint' || state.mode === 'answer') {
      ctx.fillStyle = 'rgba(217,79,48,.09)';
      ctx.beginPath();
      ctx.moveTo(s.center.x, s.center.y);
      ctx.arc(s.center.x, s.center.y, s.radius * .82, (30 * Math.PI) / 180, (150 * Math.PI) / 180, false);
      ctx.closePath();
      ctx.fill();
    }

    return s;
  }

  function syncHtml(s) {
    position(labels.A, s.A, -22, -18);
    position(labels.B, s.B, 22, -18);
    position(labels.D, s.D, 0, state.angleD < 270 ? 36 : -36);
    position(dragHandle, s.D);
    position(answerCard, s.D, s.D.x < s.width * .5 ? 150 : -150, s.D.y < s.height * .5 ? 110 : -110);
    position(arcChip, {
      x: s.center.x,
      y: s.center.y - s.radius - 34,
    });

    if (state.mode === 'task') {
      feedback.textContent = '';
    } else if (state.mode === 'hint') {
      feedback.textContent = 'Смотри на зелёную дугу AB.';
    } else {
      feedback.textContent = 'Вписанный угол: 120° / 2 = 60°.';
    }
  }

  function render() {
    if (!state.rect) return;
    syncHtml(draw());
  }

  function setAngleFromClient(clientX, clientY) {
    const s = scene();
    const box = canvas.getBoundingClientRect();
    const x = clientX - box.left - s.center.x;
    const y = clientY - box.top - s.center.y;
    let degrees = (Math.atan2(y, x) * 180) / Math.PI;
    if (degrees < 0) degrees += 360;
    const clamped = Math.max(205, Math.min(335, degrees));
    state.angleD = clamped;
    slider.value = String(Math.round(clamped));
    render();
  }

  slider.addEventListener('input', () => {
    state.angleD = Number(slider.value);
    render();
  });

  dragHandle.addEventListener('pointerdown', (event) => {
    state.dragging = true;
    dragHandle.setPointerCapture(event.pointerId);
    setAngleFromClient(event.clientX, event.clientY);
  });

  dragHandle.addEventListener('pointermove', (event) => {
    if (!state.dragging) return;
    setAngleFromClient(event.clientX, event.clientY);
  });

  dragHandle.addEventListener('pointerup', () => {
    state.dragging = false;
  });

  answerCard.addEventListener('submit', (event) => {
    event.preventDefault();
    const value = Number(answerInput.value);
    feedback.textContent = value === 60 ? 'Верно. Угол ADB равен 60°.' : 'Проверь: угол равен половине дуги AB.';
  });

  modeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      state.mode = button.dataset.mode;
      modeButtons.forEach((item) => item.classList.toggle('is-active', item === button));
      render();
    });
  });

  window.addEventListener('resize', resize);
  resize();
})();
