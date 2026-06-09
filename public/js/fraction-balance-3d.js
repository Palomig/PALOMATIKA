import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.164.1/build/three.module.js';

const container = document.getElementById('fractionBalanceScene');
const formulaText = document.getElementById('formulaText');
const operationLine = document.getElementById('operationLine');
const statusChip = document.getElementById('statusChip');
const leftPanLabel = document.getElementById('leftPanLabel');
const rightPanLabel = document.getElementById('rightPanLabel');
const historyList = document.getElementById('historyList');
const sideButtons = Array.from(document.querySelectorAll('[data-side]'));
const opButtons = Array.from(document.querySelectorAll('[data-op]'));
const resetButton = document.getElementById('resetView');

const operations = {
  mul2: {
    label: 'Умножаем обе части на 2',
    oneSideLabel: 'Умножаем только одну часть на 2',
    left: '2 · 1/2',
    right: '2 · 2/4',
    leftValue: 1,
    rightValue: 1,
    bothRows: ['1/2 = 2/4', '2 · 1/2 = 2 · 2/4', '1 = 1'],
  },
  mul3: {
    label: 'Умножаем обе части на 3',
    oneSideLabel: 'Умножаем только одну часть на 3',
    left: '3 · 1/2',
    right: '3 · 2/4',
    leftValue: 1.5,
    rightValue: 1.5,
    bothRows: ['1/2 = 2/4', '3 · 1/2 = 3 · 2/4', '3/2 = 3/2'],
  },
  div2: {
    label: 'Делим обе части на 2',
    oneSideLabel: 'Делим только одну часть на 2',
    left: '(1/2) ÷ 2',
    right: '(2/4) ÷ 2',
    leftValue: 0.25,
    rightValue: 0.25,
    bothRows: ['1/2 = 2/4', '(1/2) ÷ 2 = (2/4) ÷ 2', '1/4 = 1/4'],
  },
  add16: {
    label: 'Прибавляем 1/6 к обеим частям',
    oneSideLabel: 'Прибавляем 1/6 только к одной части',
    left: '1/2 + 1/6',
    right: '2/4 + 1/6',
    leftValue: 2 / 3,
    rightValue: 2 / 3,
    bothRows: ['1/2 = 2/4', '1/2 + 1/6 = 2/4 + 1/6', '2/3 = 2/3'],
  },
  sub16: {
    label: 'Вычитаем 1/6 из обеих частей',
    oneSideLabel: 'Вычитаем 1/6 только из одной части',
    left: '1/2 − 1/6',
    right: '2/4 − 1/6',
    leftValue: 1 / 3,
    rightValue: 1 / 3,
    bothRows: ['1/2 = 2/4', '1/2 − 1/6 = 2/4 − 1/6', '1/3 = 1/3'],
  },
  bad0: {
    label: 'Деление на ноль не определено',
    oneSideLabel: 'Деление на ноль не определено',
    left: '(1/2) ÷ 0',
    right: '(2/4) ÷ 0',
    leftValue: 0.5,
    rightValue: 0.5,
    invalid: true,
    bothRows: ['1/2 = 2/4', 'На 0 делить нельзя', 'Новое равенство не появляется'],
  },
};

const state = {
  side: 'both',
  op: 'mul2',
  tilt: 0,
  targetTilt: 0,
  pulse: 0,
};

const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, preserveDrawingBuffer: true });
renderer.domElement.setAttribute('layoutsubtree', '');
renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
renderer.setClearColor(0x000000, 0);
container.appendChild(renderer.domElement);

const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
camera.position.set(0, 4.6, 9.2);
camera.lookAt(0, .2, 0);

scene.add(new THREE.HemisphereLight(0xfffbef, 0x8ca8a1, 1.8));
const key = new THREE.DirectionalLight(0xffffff, 2.2);
key.position.set(3.4, 5.8, 5.2);
scene.add(key);

const group = new THREE.Group();
scene.add(group);

const beamGroup = new THREE.Group();
group.add(beamGroup);

const materials = {
  ink: new THREE.MeshStandardMaterial({ color: 0x1d2a2f, roughness: .48, metalness: .14 }),
  brass: new THREE.MeshStandardMaterial({ color: 0xd99146, roughness: .46, metalness: .24 }),
  teal: new THREE.MeshStandardMaterial({ color: 0x0f8b7f, roughness: .55, metalness: .06 }),
  blue: new THREE.MeshStandardMaterial({ color: 0x285f9e, roughness: .56, metalness: .05 }),
  rose: new THREE.MeshStandardMaterial({ color: 0xb83d4a, roughness: .6, metalness: .04 }),
  paper: new THREE.MeshStandardMaterial({ color: 0xfffaf0, roughness: .72, metalness: .02 }),
  shadow: new THREE.MeshStandardMaterial({ color: 0xcfc5ad, roughness: .8, metalness: 0, transparent: true, opacity: .54 }),
};

function mesh(geometry, material, position, rotation = [0, 0, 0]) {
  const item = new THREE.Mesh(geometry, material);
  item.position.set(...position);
  item.rotation.set(...rotation);
  return item;
}

const base = mesh(new THREE.CylinderGeometry(1.25, 1.55, .24, 48), materials.ink, [0, -.94, 0]);
const post = mesh(new THREE.CylinderGeometry(.11, .16, 2.75, 32), materials.ink, [0, .38, 0]);
const cap = mesh(new THREE.SphereGeometry(.22, 32, 20), materials.brass, [0, 1.82, 0]);
const floor = mesh(new THREE.CylinderGeometry(5.8, 5.8, .035, 96), materials.shadow, [0, -1.09, 0]);
group.add(base, post, cap, floor);

const beam = mesh(new THREE.BoxGeometry(5.9, .13, .16), materials.ink, [0, 0, 0]);
const pivot = mesh(new THREE.SphereGeometry(.16, 32, 20), materials.brass, [0, 0, 0]);
beamGroup.position.set(0, 1.55, 0);
beamGroup.add(beam, pivot);

const leftPan = new THREE.Group();
const rightPan = new THREE.Group();
leftPan.position.set(-2.45, -.83, 0);
rightPan.position.set(2.45, -.83, 0);
beamGroup.add(leftPan, rightPan);

function makePan(material) {
  const pan = new THREE.Group();
  pan.add(mesh(new THREE.CylinderGeometry(.82, .98, .16, 48), material, [0, 0, 0]));
  pan.add(mesh(new THREE.TorusGeometry(.93, .035, 12, 64), materials.ink, [0, .09, 0], [Math.PI / 2, 0, 0]));
  pan.add(mesh(new THREE.CylinderGeometry(.025, .025, .92, 12), materials.ink, [-.58, .5, 0], [0, 0, -.22]));
  pan.add(mesh(new THREE.CylinderGeometry(.025, .025, .92, 12), materials.ink, [.58, .5, 0], [0, 0, .22]));
  return pan;
}

leftPan.add(makePan(materials.teal));
rightPan.add(makePan(materials.blue));

function makeBlock(x, y, z, material) {
  const block = mesh(new THREE.BoxGeometry(.44, .2, .44), material, [x, y, z]);
  block.castShadow = true;
  return block;
}

const leftBlocks = new THREE.Group();
const rightBlocks = new THREE.Group();
leftPan.add(leftBlocks);
rightPan.add(rightBlocks);

function setBlocks(target, count, material) {
  target.clear();
  const capped = Math.max(1, Math.min(6, count));
  for (let i = 0; i < capped; i += 1) {
    const row = Math.floor(i / 3);
    const col = i % 3;
    target.add(makeBlock((col - 1) * .34, .2 + row * .22, row * .16, material));
  }
}

function targetPoints() {
  group.updateMatrixWorld();
  return {
    left: leftPan.getWorldPosition(new THREE.Vector3()),
    right: rightPan.getWorldPosition(new THREE.Vector3()),
  };
}

function project(point, element, dx = 0, dy = 0) {
  const rect = container.getBoundingClientRect();
  const projected = point.clone().project(camera);
  const x = (projected.x * .5 + .5) * rect.width;
  const y = (-projected.y * .5 + .5) * rect.height;
  element.style.left = `${x + dx}px`;
  element.style.top = `${y + dy}px`;
  element.style.opacity = projected.z > 1 ? '0' : '1';
}

function displayFor(side, op) {
  const current = operations[op];
  if (current.invalid) {
    return {
      formula: '1/2 = 2/4',
      operation: current.label,
      status: 'На ноль делить нельзя: операция не имеет смысла.',
      balanced: false,
      leftLabel: '1/2',
      rightLabel: '2/4',
      leftValue: .5,
      rightValue: .5,
      rows: current.bothRows,
    };
  }

  if (side === 'both') {
    return {
      formula: `${current.left} = ${current.right}`,
      operation: current.label,
      status: 'Весы ровные: обе части изменились одинаково.',
      balanced: true,
      leftLabel: current.left,
      rightLabel: current.right,
      leftValue: current.leftValue,
      rightValue: current.rightValue,
      rows: current.bothRows,
    };
  }

  const leftChanged = side === 'left';
  return {
    formula: `${leftChanged ? current.left : '1/2'} = ${leftChanged ? '2/4' : current.right}`,
    operation: current.oneSideLabel,
    status: 'Весы наклонились: изменилась только одна часть равенства.',
    balanced: false,
    leftLabel: leftChanged ? current.left : '1/2',
    rightLabel: leftChanged ? '2/4' : current.right,
    leftValue: leftChanged ? current.leftValue : .5,
    rightValue: leftChanged ? .5 : current.rightValue,
    rows: ['1/2 = 2/4', leftChanged ? `${current.left} ≠ 2/4` : `1/2 ≠ ${current.right}`, 'Равенство разрушено'],
  };
}

function updateText() {
  const view = displayFor(state.side, state.op);
  formulaText.textContent = view.formula;
  operationLine.textContent = view.operation;
  statusChip.textContent = view.status;
  statusChip.classList.toggle('is-bad', !view.balanced);
  leftPanLabel.textContent = view.leftLabel;
  rightPanLabel.textContent = view.rightLabel;
  historyList.innerHTML = view.rows.map((row) => `<div>${row}</div>`).join('');

  const delta = view.rightValue - view.leftValue;
  state.targetTilt = view.balanced ? 0 : Math.max(-.32, Math.min(.32, delta * .42 || (state.side === 'left' ? -.24 : .24)));
  setBlocks(leftBlocks, Math.round(view.leftValue * 4), view.balanced ? materials.teal : materials.rose);
  setBlocks(rightBlocks, Math.round(view.rightValue * 4), view.balanced ? materials.blue : materials.rose);
}

function syncButtons() {
  sideButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.side === state.side));
  opButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.op === state.op));
}

function resize() {
  const rect = container.getBoundingClientRect();
  renderer.setSize(rect.width, rect.height, false);
  camera.aspect = rect.width / rect.height;
  if (rect.width < 560) {
    camera.position.set(0, 5.2, 10.8);
    group.scale.setScalar(.82);
  } else {
    camera.position.set(0, 4.6, 9.2);
    group.scale.setScalar(1);
  }
  camera.lookAt(0, .2, 0);
  camera.updateProjectionMatrix();
}

function animate() {
  requestAnimationFrame(animate);
  state.tilt += (state.targetTilt - state.tilt) * .08;
  state.pulse += .035;
  beamGroup.rotation.z = state.tilt + Math.sin(state.pulse) * .008;
  group.rotation.y = Math.sin(Date.now() * .00028) * .06;
  renderer.render(scene, camera);

  const points = targetPoints();
  project(points.left, leftPanLabel, 0, 60);
  project(points.right, rightPanLabel, 0, 60);
}

sideButtons.forEach((button) => {
  button.addEventListener('click', () => {
    state.side = button.dataset.side;
    syncButtons();
    updateText();
  });
});

opButtons.forEach((button) => {
  button.addEventListener('click', () => {
    state.op = button.dataset.op;
    syncButtons();
    updateText();
  });
});

resetButton.addEventListener('click', () => {
  state.side = 'both';
  state.op = 'mul2';
  syncButtons();
  updateText();
});

window.addEventListener('resize', resize);
resize();
syncButtons();
updateText();
animate();
