#!/usr/bin/env node
import fs from "node:fs";
import path from "node:path";

const OUT = "/home/dev/palomatika/storage/app/tasks/alg/grade_7/topic_00.json";

function task(id, expression, answer, skill, type = "drill", difficulty = "base", extra = {}) {
  return {
    id,
    expression,
    answer: String(answer),
    status: "ready",
    skill,
    type,
    difficulty,
    ...extra,
  };
}

function zadanie(number, instruction, microSkill, tasks) {
  return {
    number,
    instruction,
    micro_skill: microSkill,
    tasks: tasks.map((t, index) => ({ ...t, id: index + 1 })),
  };
}

function block(number, title, zadaniya) {
  return { number, title, zadaniya };
}

const fmt = (n) => {
  const rounded = Math.round((n + Number.EPSILON) * 1000) / 1000;
  return String(rounded).replace(".", ",");
};

const clean = (n) => Number.isInteger(n) ? String(n) : fmt(n);
const mul = "\\cdot";

const addDifferentSignsA = [
  [-8, 6], [-9, 4], [-12, 5], [-15, 22], [-18, 7], [-24, 9], [-31, 15], [-40, 18],
  [-6, 11], [-13, 20], [-21, 8], [-35, 14], [-7, 19], [-28, 30], [-42, 17], [-56, 21],
  [7, -10], [18, -25], [4, -13], [29, -41], [15, -6], [23, -11], [32, -50], [44, -18],
];

const addDifferentSignsB = [
  [-3.5, 1.2], [4.8, -7.1], [-12.6, 5.4], [8.75, -2.3], [-9.4, 11.8], [15.2, -18.9],
  [-0.8, 1.5], [2.4, -6.7], [-13.25, 8.5], [19.6, -4.35], [-7.05, 3.8], [0.9, -2.4],
  [-21.7, 30.1], [11.11, -15.5], [-5.6, 5.1], [6.03, -9.08],
];

const addNegativeIntegers = [
  [-8, -6], [-3, -11], [-15, -4], [-20, -13], [-7, -9], [-18, -22], [-31, -6], [-45, -15],
  [-12, -18], [-24, -7], [-5, -36], [-19, -14], [-27, -23], [-40, -9], [-16, -28], [-33, -17],
];

const addNegativeDecimals = [
  [-2.5, -4.8], [-7.3, -1.9], [-0.6, -8.7], [-12.4, -3.6], [-5.05, -9.15], [-10.7, -0.8],
  [-18.25, -2.75], [-4.4, -6.06], [-3.75, -1.25], [-0.9, -2.8], [-14.6, -0.4], [-6.08, -7.92],
];

const subtractPairs = [
  [7, 10], [-4, 9], [-6, -11], [13, -8], [-2.4, 3.7], [5.6, -1.9], [-12, 7], [8, -15],
  [-20, -5], [4.5, 8.2], [-3.75, -1.25], [0.6, -2.8], [-9.4, 6.7], [11.2, -4.5],
  [-15.8, -20.1], [22, 35], [-40, -18], [6.06, -3.03],
];

const decimalAddSub = [
  [37.6, -5.84, 3.95, -8.9],
  [81, -45.34, 19.6, 21.75],
  [12.4, -8.75, 3.6],
  [5.03, 17.8, -6.125],
  [-4.7, 9.25, -1.6],
  [42.5, -13.75, -6.8],
  [0.56, 3.407, -1.9],
  [100, -37.68, 4.2, -15.99],
  [7.15, -0.8, 12.006],
  [-20.4, 5.55, 9.3],
  [63.08, -12.7, -9.005],
  [1.25, 3.75, -2.6],
  [-8.08, 11.4, -0.72],
  [17.001, -6.31, 2.9],
  [90.5, -44.44, -10.06],
  [3.3, -7.77, 12.12],
  [56.04, 8.96, -20.5],
  [-1.25, -3.75, 9.4],
  [14.8, -6.006, 0.26],
  [72.72, -7.27, -27.2],
];

const decimalMulDiv = [
  [`17,1 ${mul} 3,8 : 4,5 ${mul} 0,5`, 17.1 * 3.8 / 4.5 * 0.5],
  [`81,9 : 4,5 : 0,28 ${mul} 1,2`, 81.9 / 4.5 / 0.28 * 1.2],
  [`2,4 ${mul} 1,5 : 0,6`, 2.4 * 1.5 / 0.6],
  [`7,2 : 0,9 ${mul} 1,25`, 7.2 / 0.9 * 1.25],
  [`0,48 : 0,12 ${mul} 2,5`, 0.48 / 0.12 * 2.5],
  [`3,6 ${mul} 2,5 : 0,9`, 3.6 * 2.5 / 0.9],
  [`12,5 ${mul} 0,8 : 2`, 12.5 * 0.8 / 2],
  [`6,3 : 0,7 ${mul} 1,4`, 6.3 / 0.7 * 1.4],
  [`0,96 : 0,24 ${mul} 3,5`, 0.96 / 0.24 * 3.5],
  [`15,75 : 2,5 ${mul} 0,4`, 15.75 / 2.5 * 0.4],
  [`4,8 ${mul} 0,75 : 1,2`, 4.8 * 0.75 / 1.2],
  [`9,45 : 0,35 ${mul} 0,2`, 9.45 / 0.35 * 0.2],
  [`1,44 : 0,18 ${mul} 2,1`, 1.44 / 0.18 * 2.1],
  [`18,6 ${mul} 0,5 : 3,1`, 18.6 * 0.5 / 3.1],
  [`2,25 ${mul} 4,8 : 0,6`, 2.25 * 4.8 / 0.6],
  [`11,2 : 1,4 ${mul} 0,75`, 11.2 / 1.4 * 0.75],
];

const operationOrder = [
  [`6 - 2 ${mul} 4`, 6 - 2 * 4],
  [`(6 - 2) ${mul} 4`, (6 - 2) * 4],
  [`18 : 3 ${mul} 2`, 18 / 3 * 2],
  [`18 : (3 ${mul} 2)`, 18 / (3 * 2)],
  [`5 - 3 ${mul} (4 - 7)`, 5 - 3 * (4 - 7)],
  [`24 : 6 + 3 ${mul} 5`, 24 / 6 + 3 * 5],
  [`30 - 18 : 3 ${mul} 2`, 30 - 18 / 3 * 2],
  [`(30 - 18) : 3 ${mul} 2`, (30 - 18) / 3 * 2],
  [`7 ${mul} (2 - 5) + 4`, 7 * (2 - 5) + 4],
  ["40 : 5 : 2", 40 / 5 / 2],
  ["40 : (5 : 2)", 40 / (5 / 2)],
  [`3 - 4 ${mul} 5 + 18`, 3 - 4 * 5 + 18],
  [`2,5 ${mul} (8 - 12)`, 2.5 * (8 - 12)],
  ["12,6 : 0,3 - 15", 12.6 / 0.3 - 15],
  [`4 ${mul} (-3 + 8) - 7`, 4 * (-3 + 8) - 7],
  [`-6 + 2 ${mul} (9 - 14)`, -6 + 2 * (9 - 14)],
];

const realContexts = [
  ["Температура была -8 °C, затем повысилась на 6 °C. Какая стала температура?", "-2 °C", "negative_number_meaning"],
  ["На карте счета было 1200 рублей. Списали 1580 рублей. Какой баланс стал на счете?", "-380 рублей", "negative_number_meaning"],
  ["Лифт был на этаже -2 и поднялся на 7 этажей. На каком этаже он оказался?", "5", "add_different_signs"],
  ["Маша купила 3 тетради по 37,6 рубля и ручку за 45,8 рубля. Сколько она заплатила?", "158,6 рубля", "decimal_multiply_divide"],
  ["В городе утром было -5 °C, днем стало на 12 °C теплее. Какая температура днем?", "7 °C", "add_different_signs"],
  ["Баланс был -240 рублей, на счет внесли 500 рублей. Какой стал баланс?", "260 рублей", "add_different_signs"],
  ["Команда потеряла 8 очков штрафа и получила 11 очков бонуса. Какой итог?", "3 очка", "add_different_signs"],
  ["Подвал находится на этаже -3. Человек поднялся на 5 этажей. Где он оказался?", "2 этаж", "add_different_signs"],
  ["Товар стоил 199,9 рубля. Купили 4 товара. Сколько заплатили?", "799,6 рубля", "decimal_multiply_divide"],
  ["Было 2,5 литра воды, вылили 3,2 литра с учетом долга по емкости. Какой баланс воды?", "-0,7 литра", "add_different_signs"],
  ["Температура была 4 °C, затем снизилась на 9 °C. Какая стала температура?", "-5 °C", "subtract_as_opposite"],
  ["Счет был -150 рублей, списали еще 80 рублей. Какой стал счет?", "-230 рублей", "subtract_as_opposite"],
];

function makePairTasks(pairs, skill, difficulty = "base") {
  return pairs.map(([a, b], i) => {
    const op = b < 0 ? `+ (${clean(b)})` : `+ ${clean(b)}`;
    return task(i + 1, `${clean(a)} ${op}`, clean(a + b), skill, "drill", difficulty);
  });
}

function makeSubtractTasks(pairs, skill, difficulty = "base") {
  return pairs.map(([a, b], i) => {
    const rhs = b < 0 ? `(${clean(b)})` : clean(b);
    return task(i + 1, `${clean(a)} - ${rhs}`, clean(a - b), skill, "drill", difficulty);
  });
}

function makeDecimalAddTasks(items) {
  return items.map((values, i) => {
    const expression = values.map((v, index) => {
      if (index === 0) return clean(v);
      return v < 0 ? `- ${clean(Math.abs(v))}` : `+ ${clean(v)}`;
    }).join(" ");
    const sum = values.reduce((s, v) => s + v, 0);
    return task(i + 1, expression, clean(sum), "decimal_add_subtract", "drill", "standard", {
      hint: i < 2 ? "Сначала сделай прикидку, затем считай точно." : undefined,
    });
  });
}

function makeMulDivTasks(items) {
  return items.map(([expression, answer], i) =>
    task(i + 1, expression, clean(answer), "decimal_multiply_divide", "drill", "standard", {
      hint: i < 2 ? "Умножение и деление выполняются слева направо." : undefined,
    })
  );
}

function makeOperationTasks(items) {
  return items.map(([expression, answer], i) =>
    task(i + 1, expression, clean(answer), "operation_order", "drill", i < 4 ? "intro" : "base")
  );
}

function makeErrorTasks() {
  const cases = [
    ["Ученик написал: -8 + 6 = -14. Объясните ошибку.", "-8 + 6 = -2. При разных знаках вычитаем модули.", "-14"],
    ["Ученик написал: -9 + 12 = -3. Объясните ошибку.", "-9 + 12 = 3. Модуль 12 больше, значит ответ положительный.", "-3"],
    ["Ученик написал: -5 - 7 = 2. Объясните ошибку.", "-5 - 7 = -12. Вычитание 7 увеличивает долг.", "2"],
    [`Ученик написал: 18 : 3 ${mul} 2 = 3. Объясните ошибку.`, `18 : 3 ${mul} 2 = 12. Деление и умножение идут слева направо.`, "3"],
    [`Ученик написал: 6 - 2 ${mul} 4 = 16. Объясните ошибку.`, `6 - 2 ${mul} 4 = -2. Сначала выполняем умножение.`, "16"],
    ["Ученик написал: -6 - (-11) = -17. Объясните ошибку.", "-6 - (-11) = 5. Вычесть -11 значит прибавить 11.", "-17"],
    ["Ученик написал: 0,5 + 0,25 = 0,30. Объясните ошибку.", "0,5 + 0,25 = 0,75. Нужно складывать разряды.", "0,30"],
    ["Ученик написал: 4,8 : 0,6 = 0,8. Объясните ошибку.", "4,8 : 0,6 = 8. Деление на число меньше 1 увеличивает результат.", "0,8"],
  ];
  return cases.map(([expression, answer, common], i) => task(i + 1, expression, answer, "estimate_and_error_check", "error_analysis", "base", {
    common_mistake: common,
  }));
}

function repeatVariants(basePairs, transforms) {
  const out = [];
  for (const [a, b] of basePairs) {
    for (const fn of transforms) out.push(fn(a, b));
  }
  return out;
}

const expandedDifferentSigns = [
  ...addDifferentSignsA,
  ...repeatVariants(addDifferentSignsA.slice(0, 12), [
    (a, b) => [a - 10, b + 3],
    (a, b) => [a * 2, b * 2 - 1],
  ]),
];

const expandedDecimals = [
  ...decimalAddSub,
  ...decimalAddSub.map(values => values.map(v => Math.round((v + (v >= 0 ? 1.7 : -1.3)) * 1000) / 1000)),
  ...decimalAddSub.map(values => values.map(v => Math.round((v * 1.5) * 1000) / 1000)),
];

const blocks = [
  block(1, "Знаки и отрицательные числа", [
    zadanie(1, "Вычислите и объясните знак ответа:", "add_different_signs", makePairTasks(expandedDifferentSigns.slice(0, 48), "add_different_signs", "intro")),
    zadanie(2, "Вычислите сумму двух отрицательных чисел:", "negative_number_meaning", makePairTasks([...addNegativeIntegers, ...addNegativeIntegers.map(([a, b]) => [a - 10, b - 5])], "negative_number_meaning", "intro")),
    zadanie(3, "Вычислите десятичные числа с разными знаками:", "add_different_signs", makePairTasks(addDifferentSignsB, "add_different_signs", "base")),
    zadanie(4, "Вычислите сумму двух отрицательных десятичных чисел:", "negative_number_meaning", makePairTasks(addNegativeDecimals, "negative_number_meaning", "base")),
    zadanie(5, "Найдите ошибку в рассуждении ученика:", "estimate_and_error_check", makeErrorTasks()),
  ]),
  block(2, "Вычитание и противоположные числа", [
    zadanie(1, "Вычислите, заменяя вычитание прибавлением противоположного числа:", "subtract_as_opposite", makeSubtractTasks([...subtractPairs, ...subtractPairs.map(([a, b]) => [a + 10, b - 4])], "subtract_as_opposite", "base")),
    zadanie(2, "Вычислите выражения с двумя минусами:", "subtract_as_opposite", makeSubtractTasks([[3, -5], [-7, -12], [0, -9], [18, -20], [-14, -3], [2.5, -7.5], [-4.6, -1.2], [11.8, -0.9], [-20.5, -30.1], [6.4, -8.8]], "subtract_as_opposite", "base")),
  ]),
  block(3, "Десятичные дроби: сложение и вычитание", [
    zadanie(1, "Найдите значение выражения:", "decimal_add_subtract", makeDecimalAddTasks(expandedDecimals.slice(0, 40))),
    zadanie(2, "Вычислите, контролируя запятую и разряды:", "decimal_add_subtract", makeDecimalAddTasks(expandedDecimals.slice(40, 60))),
  ]),
  block(4, "Десятичные дроби: умножение и деление", [
    zadanie(1, "Вычислите, соблюдая порядок действий слева направо:", "decimal_multiply_divide", makeMulDivTasks([...decimalMulDiv, ...decimalMulDiv.map(([e, a]) => [e.replace(/,/g, ","), a + 0])].slice(0, 32))),
    zadanie(2, "Сначала сделайте прикидку, затем вычислите точно:", "estimate_and_error_check", makeMulDivTasks(decimalMulDiv.slice(0, 12)).map(t => ({ ...t, skill: "estimate_and_error_check", type: "guided" }))),
  ]),
  block(5, "Порядок действий", [
    zadanie(1, "Вычислите по действиям:", "operation_order", makeOperationTasks([...operationOrder, ...operationOrder.map(([e, a]) => [`(${e}) + 5`, a + 5])])),
    zadanie(2, "Сравните выражения с разными скобками:", "operation_order", makeOperationTasks([
      [`6 - 2 ${mul} 4`, -2], [`(6 - 2) ${mul} 4`, 16], ["40 : 5 : 2", 4], ["40 : (5 : 2)", 16],
      [`18 : 3 ${mul} 2`, 12], [`18 : (3 ${mul} 2)`, 3], [`20 - 4 ${mul} 3`, 8], [`(20 - 4) ${mul} 3`, 48],
      [`2 ${mul} (7 - 10)`, -6], [`2 ${mul} 7 - 10`, 4], [`5 + 3 ${mul} (2 - 8)`, -13], [`(5 + 3) ${mul} (2 - 8)`, -48],
    ])),
  ]),
  block(6, "Прикидка и проверка ответа", [
    zadanie(1, "Сначала сделайте прикидку, затем вычислите точно:", "estimate_and_error_check", makeDecimalAddTasks([
      [49.8, -21.3, 10.1], [-19.7, 8.9], [3.98, 4.2], [42.3, -6.1], [98.5, -49.2, -10.4],
      [0.97, 1.99, -0.51], [-30.2, 12.8], [19.99, -5.01, 4.02], [100.1, -99.8], [-8.4, 2.2, 1.1],
      [56.6, -12.4, 8.8], [7.07, -9.9, 4.4], [-14.5, 20.2, -3.1], [0.5, -1.25, 2.75],
    ]).map(t => ({ ...t, skill: "estimate_and_error_check", type: "guided" }))),
    zadanie(2, "Выберите, какой ответ невозможен по прикидке, и исправьте вычисление:", "estimate_and_error_check", makeErrorTasks()),
  ]),
  block(7, "Реальные ситуации", [
    zadanie(1, "Решите задачу и объясните, почему знак ответа именно такой:", "negative_number_meaning", realContexts.map(([expression, answer, skill], i) => task(i + 1, expression, answer, skill, "real_context", i < 4 ? "intro" : "base"))),
  ]),
];

let number = 8;
for (const skillBlock of [
  ["Дополнительная серия: разные знаки", "add_different_signs", makePairTasks(expandedDifferentSigns.slice(12, 36).map(([a, b]) => [a - 5, b + 9]), "add_different_signs", "base")],
  ["Дополнительная серия: отрицательные суммы", "negative_number_meaning", makePairTasks(addNegativeIntegers.map(([a, b]) => [a * 2, b - 3]), "negative_number_meaning", "base")],
  ["Дополнительная серия: вычитание", "subtract_as_opposite", makeSubtractTasks(subtractPairs.map(([a, b]) => [a * 2, b + 1]), "subtract_as_opposite", "base")],
  ["Дополнительная серия: десятичные суммы", "decimal_add_subtract", makeDecimalAddTasks(expandedDecimals.slice(10, 34).map(values => values.map(v => Math.round((v - 2.2) * 1000) / 1000)))],
  ["Дополнительная серия: порядок действий", "operation_order", makeOperationTasks(operationOrder.map(([e, a]) => [`${e} - 3`, a - 3]))],
]) {
  blocks.push(block(number++, skillBlock[0], [
    zadanie(1, "Вычислите:", skillBlock[1], skillBlock[2]),
  ]));
}

const homeworkTasks = [
  task(1, "-8 + 6", "-2", "add_different_signs", "concept", "intro", { prompt: "Вычисли и объясни через долг или температуру: -8 + 6." }),
  task(2, "-8 + (-6)", "-14", "negative_number_meaning", "concept", "intro", { prompt: "Вычисли: -8 + (-6). Чем этот пример отличается от -8 + 6?" }),
  task(3, "9 + (-14)", "-5", "add_different_signs"),
  task(4, "-15 + 22", "7", "add_different_signs"),
  task(5, "7 - 10", "-3", "subtract_as_opposite"),
  task(6, "-6 - (-11)", "5", "subtract_as_opposite"),
  task(7, "37,6 - 5,84 + 3,95 - 8,9", "26,81", "decimal_add_subtract", "drill", "standard", { hint: "Сделай прикидку: около 27." }),
  task(8, "81 - 45,34 + 19,6 + 21,75", "77,01", "decimal_add_subtract", "drill", "standard"),
  task(9, `17,1 ${mul} 3,8 : 4,5 ${mul} 0,5`, "7,22", "decimal_multiply_divide", "drill", "standard"),
  task(10, `81,9 : 4,5 : 0,28 ${mul} 1,2`, "78", "decimal_multiply_divide", "drill", "standard"),
  task(11, `6 - 2 ${mul} 4`, "-2", "operation_order"),
  task(12, `(6 - 2) ${mul} 4`, "16", "operation_order"),
  task(13, `18 : 3 ${mul} 2`, "12", "operation_order"),
  task(14, `18 : (3 ${mul} 2)`, "3", "operation_order"),
  task(15, "49,8 - 21,3 + 10,1", "38,6", "estimate_and_error_check", "guided"),
  task(16, "-19,7 + 8,9", "-10,8", "estimate_and_error_check", "guided"),
  task(17, "Температура была -8 °C, затем повысилась на 6 °C. Какая стала температура?", "-2 °C", "negative_number_meaning", "real_context", "intro"),
  task(18, "На карте счета было 1200 рублей. Списали 1580 рублей. Какой баланс стал на счете?", "-380 рублей", "negative_number_meaning", "real_context", "base"),
  task(19, "Ученик написал: -8 + 6 = -14. Найди ошибку и объясни правильно.", "-8 + 6 = -2. При разных знаках нужно вычесть модули, а не сложить.", "estimate_and_error_check", "error_analysis"),
  task(20, `Ученик написал: 18 : 3 ${mul} 2 = 3. Найди ошибку.`, `18 : 3 ${mul} 2 = 12. Умножение и деление выполняются слева направо.`, "operation_order", "error_analysis"),
].map((t, i) => ({ ...t, id: i + 1, prompt: t.prompt ?? `Вычисли: ${t.expression}.` }));

const data = {
  topic_id: "00",
  meta: {
    title: "Арифметическая база перед алгеброй",
    description: "Знаки, отрицательные числа, десятичные дроби, порядок действий, прикидка и проверка ответа",
    color: "emerald",
    icon: "calculator",
    grade: 7,
    subject: "algebra",
    strand: "arithmetic_foundation",
    skills: [
      "Понимать отрицательное число как долг, температуру или движение влево по числовой прямой",
      "Складывать и вычитать числа с разными знаками",
      "Выполнять действия с десятичными дробями",
      "Соблюдать порядок действий",
      "Оценивать примерный ответ до вычисления",
      "Проверять результат обратным действием или подстановкой",
      "Находить типичные ошибки со знаками",
    ],
  },
  curriculum: {
    year_position: "weeks_1_2_or_diagnostic",
    lesson_hours: 2,
    homework_size: "15-20",
    main_idea: "Перед алгеброй ученик должен уверенно считать с числами: понимать знак, порядок действий и примерный размер ответа.",
    prerequisites: ["Натуральные числа", "Обычные вычисления в столбик", "Понимание десятичной записи числа"],
    common_misconceptions: [
      "-8 + 6 считают как -14 или 14",
      "Вычитание отрицательного числа воспринимают как обычное вычитание",
      "Умножение и деление выполняют не слева направо",
      "Запятую в десятичных дробях переносят механически",
      "Не делают прикидку и не замечают невозможный ответ",
    ],
    lesson_flow: [
      { minutes: "0-15", activity: "Диагностика: знаки, простые отрицательные числа, десятичные дроби." },
      { minutes: "15-35", activity: "Отрицательные числа через долг, температуру и числовую прямую." },
      { minutes: "35-55", activity: "Сложение и вычитание чисел с разными знаками." },
      { minutes: "55-75", activity: "Десятичные дроби и аккуратная запись." },
      { minutes: "75-95", activity: "Порядок действий и вычисление слева направо для умножения/деления." },
      { minutes: "95-110", activity: "Прикидка ответа и поиск невозможных результатов." },
      { minutes: "110-120", activity: "Разбор ошибок и домашка." },
    ],
  },
  micro_skills: [
    { id: "negative_number_meaning", title: "Смысл отрицательного числа", goal: "Ученик объясняет отрицательное число через долг, температуру или движение по числовой прямой." },
    { id: "add_different_signs", title: "Сложение чисел с разными знаками", goal: "Ученик находит разность модулей и ставит знак числа с большим модулем." },
    { id: "subtract_as_opposite", title: "Вычитание как прибавление противоположного", goal: "Ученик заменяет вычитание прибавлением противоположного числа." },
    { id: "decimal_add_subtract", title: "Сложение и вычитание десятичных дробей", goal: "Ученик записывает запятую под запятой и контролирует разряды." },
    { id: "decimal_multiply_divide", title: "Умножение и деление десятичных дробей", goal: "Ученик выполняет умножение и деление десятичных дробей без потери смысла результата." },
    { id: "operation_order", title: "Порядок действий", goal: "Ученик выполняет умножение и деление слева направо, затем сложение и вычитание слева направо." },
    { id: "estimate_and_error_check", title: "Прикидка и поиск ошибок", goal: "Ученик заранее оценивает ответ и замечает ошибки со знаком или размером числа." },
  ],
  exported_at: new Date().toISOString(),
  source: "PALOMATIKA generated arithmetic foundation bank",
  blocks,
  homework_sets: [{
    id: "hw_00_arithmetic_diagnostic",
    title: "Арифметическая база: знаки, дроби, порядок действий",
    target_minutes: 35,
    tasks_count: homeworkTasks.length,
    tasks: homeworkTasks,
  }],
};

fs.mkdirSync(path.dirname(OUT), { recursive: true });
fs.writeFileSync(OUT, JSON.stringify(data, null, 2) + "\n");

const total = blocks.reduce((s, b) => s + b.zadaniya.reduce((zs, z) => zs + z.tasks.length, 0), 0);
console.log(`generated topic_00: ${blocks.length} blocks, ${total} tasks -> ${OUT}`);
