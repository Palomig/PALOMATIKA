import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.164.1/build/three.module.js';

const container = document.getElementById('threeScene');
const labels = {
  A: document.querySelector('[data-label="A"]'),
  B: document.querySelector('[data-label="B"]'),
  C: document.querySelector('[data-label="C"]'),
  S: document.querySelector('[data-label="S"]'),
};
const answerCard = document.getElementById('answerCard');
const answerInput = document.getElementById('heightAnswer');
const feedback = document.getElementById('feedback');
const angleChip = document.getElementById('angleChip');
const angleSlider = document.getElementById('angleSlider');
const spinSlider = document.getElementById('spinSlider');
const modeButtons = Array.from(document.querySelectorAll('[data-mode]'));

const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, preserveDrawingBuffer: true });
renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
renderer.setClearColor(0x000000, 0);
container.appendChild(renderer.domElement);

const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
camera.position.set(6.5, 6, 9);
camera.lookAt(0, 0, 0);

scene.add(new THREE.HemisphereLight(0xffffff, 0x9aa6a0, 1.7));
const key = new THREE.DirectionalLight(0xffffff, 2.1);
key.position.set(4, 7, 5);
scene.add(key);

const group = new THREE.Group();
scene.add(group);

const materials = {
  base: new THREE.MeshStandardMaterial({ color: 0xf9f1dd, roughness: 0.78, metalness: 0.02, side: THREE.DoubleSide }),
  side: new THREE.MeshStandardMaterial({ color: 0x8ec9c1, roughness: 0.58, metalness: 0.03, transparent: true, opacity: 0.34, side: THREE.DoubleSide }),
  sideAccent: new THREE.MeshStandardMaterial({ color: 0xf2a77d, roughness: 0.62, transparent: true, opacity: 0.42, side: THREE.DoubleSide }),
  point: new THREE.MeshStandardMaterial({ color: 0x172124, roughness: 0.5 }),
  height: new THREE.LineBasicMaterial({ color: 0xd85a2a, linewidth: 2 }),
  edge: new THREE.LineBasicMaterial({ color: 0x172124 }),
  helper: new THREE.LineDashedMaterial({ color: 0xd85a2a, dashSize: 0.18, gapSize: 0.12 }),
};

const state = {
  angle: Number(angleSlider.value),
  spin: 0,
  mode: 'model',
};

const A = new THREE.Vector3(-3.2, 0, -1.9);
const B = new THREE.Vector3(3.2, 0, -1.9);
const C = new THREE.Vector3(-3.2, 0, 2.1);
const O = new THREE.Vector3(-.4, 0, -.35);
const S = new THREE.Vector3();

function makeLine(points, material) {
  const geometry = new THREE.BufferGeometry().setFromPoints(points);
  const line = new THREE.Line(geometry, material);
  if (line.computeLineDistances) line.computeLineDistances();
  return line;
}

function makeTriangle(p1, p2, p3, material) {
  const geometry = new THREE.BufferGeometry().setFromPoints([p1, p2, p3]);
  geometry.setIndex([0, 1, 2]);
  geometry.computeVertexNormals();
  return new THREE.Mesh(geometry, material);
}

const base = makeTriangle(A, B, C, materials.base);
const side1 = makeTriangle(S, A, B, materials.side);
const side2 = makeTriangle(S, B, C, materials.side);
const side3 = makeTriangle(S, C, A, materials.sideAccent);
group.add(base, side1, side2, side3);

const edges = [
  makeLine([A, B, C, A], materials.edge),
  makeLine([S, A], materials.edge),
  makeLine([S, B], materials.edge),
  makeLine([S, C], materials.edge),
  makeLine([S, O], materials.height),
  makeLine([A, O], materials.helper),
];
edges.forEach((edge) => group.add(edge));

const pointGeometry = new THREE.SphereGeometry(0.085, 24, 16);
const pointMeshes = { A, B, C, S, O };
Object.entries(pointMeshes).forEach(([name, point]) => {
  const mesh = new THREE.Mesh(pointGeometry, materials.point);
  mesh.name = name;
  mesh.position.copy(point);
  group.add(mesh);
  pointMeshes[name] = mesh;
});

const grid = new THREE.GridHelper(8, 8, 0x8fa19d, 0xd1dad5);
grid.position.y = -0.02;
group.add(grid);

const arcCurve = new THREE.EllipseCurve(0, 0, .62, .62, 0, Math.PI / 4, false, 0);
const arcGeometry = new THREE.BufferGeometry().setFromPoints(arcCurve.getPoints(36).map((p) => new THREE.Vector3(p.x, 0.02, p.y)));
const arc = new THREE.Line(arcGeometry, new THREE.LineBasicMaterial({ color: 0xd85a2a }));
group.add(arc);

function updateGeometry() {
  const ao = A.distanceTo(O);
  const height = Math.tan((state.angle * Math.PI) / 180) * ao;
  S.set(O.x, height, O.z);

  side1.geometry.dispose();
  side2.geometry.dispose();
  side3.geometry.dispose();
  side1.geometry = makeTriangle(S, A, B, materials.side).geometry;
  side2.geometry = makeTriangle(S, B, C, materials.side).geometry;
  side3.geometry = makeTriangle(S, C, A, materials.sideAccent).geometry;

  const edgePoints = [[A, B, C, A], [S, A], [S, B], [S, C], [S, O], [A, O]];
  edges.forEach((edge, index) => {
    edge.geometry.dispose();
    edge.geometry = new THREE.BufferGeometry().setFromPoints(edgePoints[index]);
    if (edge.computeLineDistances) edge.computeLineDistances();
  });

  pointMeshes.S.position.copy(S);
  pointMeshes.O.position.copy(O);
  arc.position.copy(O);
  angleChip.textContent = `угол ${state.angle}°`;
}

function project(point, element, dx = 0, dy = 0) {
  const rect = container.getBoundingClientRect();
  const projected = point.clone().project(camera);
  const x = (projected.x * 0.5 + 0.5) * rect.width;
  const y = (-projected.y * 0.5 + 0.5) * rect.height;
  element.style.left = `${x + dx}px`;
  element.style.top = `${y + dy}px`;
  element.style.opacity = projected.z > 1 ? '0' : '1';
}

function worldPoint(point) {
  group.updateMatrixWorld();
  return point.clone().applyMatrix4(group.matrixWorld);
}

function syncDom() {
  const rect = container.getBoundingClientRect();
  const answerOffsetX = rect.width < 560 ? 0 : 160;
  const answerOffsetY = rect.width < 560 ? -8 : -8;

  project(worldPoint(A), labels.A, -18, 14);
  project(worldPoint(B), labels.B, 18, 14);
  project(worldPoint(C), labels.C, -18, -18);
  project(worldPoint(S), labels.S, 0, -28);
  project(worldPoint(S), answerCard, answerOffsetX, answerOffsetY);
  project(worldPoint(O), angleChip, 26, 36);

  if (state.mode === 'model') {
    feedback.textContent = '';
  } else if (state.mode === 'hint') {
    feedback.textContent = 'Смотри на прямоугольный треугольник SAO.';
  } else {
    feedback.textContent = `SO = AO · tg(${state.angle}°). При 45° ответ 8.`;
  }
}

function resize() {
  const rect = container.getBoundingClientRect();
  renderer.setSize(rect.width, rect.height, false);
  camera.aspect = rect.width / rect.height;
  if (rect.width < 560) {
    group.scale.setScalar(0.74);
    camera.position.set(7.5, 6.8, 13.6);
  } else {
    group.scale.setScalar(1);
    camera.position.set(6.5, 6, 9);
  }
  camera.lookAt(0, 0, 0);
  camera.updateProjectionMatrix();
}

function animate() {
  requestAnimationFrame(animate);
  group.rotation.y = state.spin + Math.sin(Date.now() * 0.00035) * 0.035;
  renderer.render(scene, camera);
  syncDom();
}

angleSlider.addEventListener('input', () => {
  state.angle = Number(angleSlider.value);
  updateGeometry();
});

spinSlider.addEventListener('input', () => {
  state.spin = (Number(spinSlider.value) * Math.PI) / 180;
});

answerCard.addEventListener('submit', (event) => {
  event.preventDefault();
  const expected = Math.round(Math.tan((state.angle * Math.PI) / 180) * 8);
  const value = Number(answerInput.value);
  feedback.textContent = value === expected
    ? `Верно. Высота SO равна ${expected} см.`
    : `Почти: здесь SO = AO · tg(${state.angle}°).`;
});

modeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    state.mode = button.dataset.mode;
    modeButtons.forEach((item) => item.classList.toggle('is-active', item === button));
    side1.material.opacity = state.mode === 'model' ? 0.34 : 0.22;
    side2.material.opacity = state.mode === 'model' ? 0.34 : 0.22;
    side3.material.opacity = state.mode === 'model' ? 0.42 : 0.62;
    syncDom();
  });
});

window.addEventListener('resize', resize);
resize();
updateGeometry();
animate();
