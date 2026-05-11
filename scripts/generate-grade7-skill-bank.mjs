#!/usr/bin/env node

import fs from "node:fs";

const OUT = "/home/dev/palomatika/storage/app/tasks/alg/grade_7/skills.json";
const SOURCE = "/var/www/html/grade7-topics.json";
const ZVAVICH_STATS = "/home/dev/palomatika/storage/app/tasks/alg/grade_7/_ref/zvavich_reference_stats.json";
const MAKARYCHEV_STATS = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/_stats.json";
const mul = "\\cdot";
const levels = [
  { id: "simple", title: "Простой", description: "Чистый шаблон, один основной шаг." },
  { id: "medium", title: "Средний", description: "Два-три шага: скобки, отрицательные коэффициенты, несколько однотипных преобразований." },
  { id: "high", title: "Высокий", description: "Задачи в стиле дидактических материалов: вложенность, пропуски, обратные условия, проверка тождества или обоснование." },
];

const clean = (n) => {
  const rounded = Math.round((n + Number.EPSILON) * 1000) / 1000;
  return Number.isInteger(rounded) ? String(rounded) : String(rounded).replace(".", ",");
};
const par = (n) => n < 0 ? `(${clean(n)})` : clean(n);
const gcd = (a, b) => b === 0 ? Math.abs(a) : gcd(b, a % b);
const frac = (num, den) => {
  const sign = num * den < 0 ? "-" : "";
  const n = Math.abs(num);
  const d = Math.abs(den);
  const g = gcd(n, d);
  return d / g === 1 ? `${sign}${n / g}` : `${sign}\\frac{${n / g}}{${d / g}}`;
};
const shufflePick = (arr, i) => arr[i % arr.length];
const coefVar = (coef, variable) => coef === 1 ? variable : coef === -1 ? `-${variable}` : `${coef}${variable}`;
const systemExpr = (first, second, suffix = "") => `\\begin{cases} ${first} \\\\ ${second} \\end{cases}${suffix}`;

function task(id, expression, answer, level, skill, type, prompt = null) {
  return {
    id,
    expression,
    answer: String(answer),
    status: "ready",
    level,
    skill,
    task_type: type,
    prompt: prompt ?? `Выполните: ${expression}.`,
  };
}

function withOffset(expr, answer, offset) {
  if (offset === 0) return [expr, answer];
  return [`(${expr}) ${offset > 0 ? "+" : "-"} ${Math.abs(offset)}`, clean(Number(String(answer).replace(",", ".")) + offset)];
}

function linSolve(a, b, c) {
  return clean((c - b) / a);
}

function bothSidesSolve(a, b, c, d) {
  return clean((d - b) / (a - c));
}

const factories = {
  signed_add(skill, level, count) {
    const pairs = level === "simple"
      ? [[-8, 6], [-9, 4], [-12, 5], [7, -10], [18, -25]]
      : level === "medium"
        ? [[-24, 9], [-31, 15], [44, -58], [-3.5, 1.2], [4.8, -7.1]]
        : [[-120, 75], [230, -410], [-12.6, 5.4], [0.9, -2.4], [-13.25, 8.5]];
    return Array.from({ length: count }, (_, i) => {
      const [a, b] = shufflePick(pairs, i);
      const aa = a - Math.floor(i / pairs.length) * (level === "high" ? 3 : 1);
      const bb = b + Math.floor(i / pairs.length) * (level === "simple" ? 1 : 2);
      if (level === "high") {
        const c = i % 2 ? -(7 + i) : 5 + i;
        return task(i + 1, `${clean(aa)} + ${par(bb)} - ${par(c)}`, clean(aa + bb - c), level, skill.id, skill.task_type);
      }
      return task(i + 1, `${clean(aa)} + ${par(bb)}`, clean(aa + bb), level, skill.id, skill.task_type);
    });
  },

  negative_sum(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = level === "high" ? -(12 + i * 2) : -(3 + i);
      const b = level === "simple" ? -(5 + (i % 7)) : -(8 + i * 1.5);
      if (level === "high") {
        const c = -(4 + (i % 6));
        const d = 2 + (i % 5);
        return task(i + 1, `(${clean(a)} + (${clean(b)})) + (${clean(c)}) - ${d}`, clean(a + b + c - d), level, skill.id, skill.task_type, "Найдите значение, сохранив знаки каждого слагаемого.");
      }
      return task(i + 1, `${clean(a)} + (${clean(b)})`, clean(a + b), level, skill.id, skill.task_type);
    });
  },

  subtract_signed(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = level === "simple" ? 10 + i : (i % 2 ? -8 - i : 14 + i);
      const b = level === "high" ? (i % 2 ? -12 - i : 18 + i) : (i % 2 ? -4 - i : 3 + i);
      if (level === "high") {
        const c = i % 2 ? -(6 + i) : 9 + i;
        const d = -(2 + (i % 4));
        return task(i + 1, `(${clean(a)} - ${par(b)}) - (${par(c)} - ${par(d)})`, clean(a - b - (c - d)), level, skill.id, skill.task_type, "Выполните вычитание чисел со знаками.");
      }
      return task(i + 1, `${clean(a)} - ${par(b)}`, clean(a - b), level, skill.id, skill.task_type);
    });
  },

  decimal_add(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = (12 + i * 1.7) / 10;
      const b = (8 + i * 0.9) / 10;
      const c = (5 + i * 1.1) / 10;
      const expression = level === "simple"
        ? `${clean(a)} + ${clean(b)}`
        : level === "medium"
          ? `${clean(a + 3)} - ${clean(b)} + ${clean(c)}`
          : `(${clean(a + 7)} - ${clean(b + 4)}) + (${clean(c)} - ${clean(0.3 * i + 0.8)}) - ${clean(1.25 + i / 20)}`;
      const answer = level === "simple" ? a + b : level === "medium" ? a + 3 - b + c : (a + 7 - (b + 4)) + (c - (0.3 * i + 0.8)) - (1.25 + i / 20);
      return task(i + 1, expression, clean(answer), level, skill.id, skill.task_type);
    });
  },

  decimal_mul_div(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 1.2 + i * 0.2;
      const b = level === "simple" ? 10 : 2.5;
      const c = level === "high" ? 0.4 + (i % 4) * 0.1 : 1;
      const expression = level === "simple"
        ? `${clean(a)} ${mul} ${clean(b)}`
        : level === "medium"
          ? `${clean(a * 3)} : ${clean(0.6)} - ${clean(1.5 + i / 10)}`
          : `(${clean(a)} ${mul} ${clean(b)} : ${clean(c)}) - (${clean(a)} + ${clean(c)})`;
      const answer = level === "simple" ? a * b : level === "medium" ? (a * 3) / 0.6 - (1.5 + i / 10) : a * b / c - (a + c);
      return task(i + 1, expression, clean(answer), level, skill.id, skill.task_type);
    });
  },

  order_actions(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 6 + i, b = 2 + (i % 4), c = 3 + (i % 5);
      const expression = level === "simple"
        ? `${a} - ${b} ${mul} ${c}`
        : level === "medium"
          ? `(${a} - ${b}) ${mul} ${c} + ${b}^2`
          : `(${a * 3} : (${c} - ${b % 2}) ${mul} ${b} - (${i} + ${c})) + (${b}^2 - ${c})`;
      const answer = level === "simple" ? a - b * c : level === "medium" ? (a - b) * c + b ** 2 : (a * 3) / (c - b % 2) * b - (i + c) + (b ** 2 - c);
      return task(i + 1, expression, clean(answer), level, skill.id, skill.task_type);
    });
  },

  remove_plus_parentheses(skill, level, count) {
    const vars = ["x", "a", "m", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 2 + i, b = 3 + (i % 6);
      const expression = level === "simple" ? `${v} + (${a}${v} + ${b})` : level === "medium" ? `${a}${v} + (${b}${v} - ${i + 1}) + (${v} + ${i + 2})` : `${a}${v} - ${i} + ((${b}${v} + ${i + 4}) + (${2}${v} - ${i + 1})) + (${2 * i + 1} - ${v})`;
      const answer = level === "simple" ? `${a + 1}${v} + ${b}` : level === "medium" ? `${a + b + 1}${v} + 1` : `${a + b + 1}${v} + ${i + 4}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  remove_minus_parentheses(skill, level, count) {
    const vars = ["x", "a", "m", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 5 + i, b = 2 + (i % 5), c = 1 + (i % 4);
      const expression = level === "simple" ? `${a}${v} - (${b}${v} + ${c})` : level === "medium" ? `${a}${v} - (${b}${v} - ${c}) + (${v} - ${i + 1})` : `${a}${v} + ${c} - ((${b}${v} - ${i + 2}) + (${v} - ${i})) - (${2}${v} - ${i})`;
      const answer = level === "simple" ? `${a - b}${v} - ${c}` : level === "medium" ? `${a - b + 1}${v} ${c - i - 1 < 0 ? "-" : "+"} ${Math.abs(c - i - 1)}` : `${a - b - 3}${v} + ${c + 3 * i + 2}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  distribute(skill, level, count) {
    const vars = ["x", "a", "b", "m"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const k = level === "simple" ? 2 + (i % 7) : i % 2 ? -(2 + i % 5) : 3 + i % 6;
      const a = 2 + (i % 5), b = level === "high" ? -(1 + i % 6) : 1 + i % 8;
      const expression = level === "high"
        ? `${clean(k)}(${a}${v} ${b < 0 ? "-" : "+"} ${Math.abs(b)}) - ${clean(k - 1)}(${v} - ${Math.abs(b)})`
        : `${clean(k)}(${a}${v} ${b < 0 ? "-" : "+"} ${Math.abs(b)})`;
      const answer = level === "high"
        ? `${clean(k * a - (k - 1))}${v} ${k * b + (k - 1) * Math.abs(b) < 0 ? "-" : "+"} ${clean(Math.abs(k * b + (k - 1) * Math.abs(b)))}`
        : `${clean(k * a)}${v} ${k * b < 0 ? "-" : "+"} ${clean(Math.abs(k * b))}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  combine_like(skill, level, count) {
    const vars = ["x", "a", "b", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 3 + i, b = level === "simple" ? 5 + i : -(2 + i), c = level === "high" ? -1 * (4 + i) : 1 + (i % 4);
      const expression = level === "high"
        ? `${a}${v} ${b < 0 ? "-" : "+"} ${Math.abs(b)}${v} ${c < 0 ? "-" : "+"} ${Math.abs(c)} - (${2}${v} - ${i + 1})`
        : level === "medium"
          ? `${a}${v} ${b < 0 ? "-" : "+"} ${Math.abs(b)}${v} + ${i + 2} - ${3}${v}`
        : `${a}${v} ${b < 0 ? "-" : "+"} ${Math.abs(b)}${v} ${c < 0 ? "-" : "+"} ${Math.abs(c)}`;
      const coef = level === "high" ? a + b - 2 : level === "medium" ? a + b - 3 : a + b;
      const free = level === "high" ? c + i + 1 : level === "medium" ? i + 2 : c;
      return task(i + 1, expression, `${coef}${v} ${free < 0 ? "-" : "+"} ${Math.abs(free)}`, level, skill.id, skill.task_type);
    });
  },

  eval_value(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 1 : level === "medium" ? -(i + 1) : (i + 1) / 2;
      const a = 2 + (i % 5), b = 3 + i;
      const expression = level === "high"
        ? `${a}(x - ${i + 1}) + ${b}x, при x = ${clean(x)}`
        : `${a}x ${b % 2 ? "+" : "-"} ${b}, при x = ${clean(x)}`;
      const answer = level === "high" ? a * (x - i - 1) + b * x : b % 2 ? a * x + b : a * x - b;
      return task(i + 1, expression, clean(answer), level, skill.id, skill.task_type);
    });
  },

  equation_ax_b_c(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 2 : level === "medium" ? -(i + 2) : (i % 2 ? -i - 2 : i + 2);
      const a = level === "high" ? 3 + (i % 5) : 2 + (i % 4);
      const b = i + 5;
      const c = a * x + b;
      if (level === "high") {
        const right = c + 2 * (x - 1);
        return task(i + 1, `${a}(x + 1) + ${b - a} + 2(x - 1) = ${clean(right)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение, предварительно приведя его к виду ax + b = c.");
      }
      return task(i + 1, `${a}x + ${b} = ${clean(c)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_one_step_add(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 3 : level === "medium" ? -(i + 3) : i % 2 ? -i - 4 : i + 4;
      const a = level === "high" ? -(5 + i) : 4 + i;
      const b = x + a;
      const expression = level === "high"
        ? `(x ${a < 0 ? "-" : "+"} ${Math.abs(a)}) + (${i + 2} - ${i % 3}) = ${clean(b + i + 2 - (i % 3))}`
        : level === "medium"
          ? `x ${a < 0 ? "-" : "+"} ${Math.abs(a)} - ${i + 1} = ${clean(b - i - 1)}`
        : `x ${a < 0 ? "-" : "+"} ${Math.abs(a)} = ${clean(b)}`;
      return task(i + 1, expression, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_one_step_mul(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 2 : level === "medium" ? -(i + 2) : i % 2 ? -i - 3 : i + 3;
      const a = level === "high" ? -(2 + (i % 6)) : 2 + (i % 8);
      if (level === "high") {
        const k = 2 + (i % 3);
        return task(i + 1, `${k}(${a}x) - (${i + 3} - ${i % 2}) = ${clean(k * a * x - (i + 3 - (i % 2)))}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение, предварительно приведя левую часть к виду ax = b.");
      }
      return task(i + 1, `${a}x = ${clean(a * x)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_decimal(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 1 : level === "medium" ? -(i + 1) : (i + 2) / 2;
      const a = level === "high" ? 0.3 + (i % 5) / 10 : 0.5 + (i % 4) / 10;
      const b = 1.2 + i / 10;
      const c = a * x + b;
      if (level === "high") {
        const right = c - 0.2 * (x + 1);
        return task(i + 1, `${clean(a)}(x - 1) + ${clean(b + a)} - 0,2(x + 1) = ${clean(right)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение с десятичными коэффициентами.");
      }
      return task(i + 1, `${clean(a)}x + ${clean(b)} = ${clean(c)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение с десятичными коэффициентами.");
    });
  },

  equation_both_sides(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i % 2 ? -(i + 1) : i + 1;
      const a = 5 + (i % 5), c = 2 + (i % 3), b = i + 4;
      const d = (a - c) * x + b;
      if (level === "high") {
        return task(i + 1, `${a}(x - 1) + ${b + a} - (${c}x - ${i + 1}) = ${clean(d + i + 1)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
      }
      if (level === "medium") {
        return task(i + 1, `${a}x + ${b} - ${i + 2} = ${c}x + ${clean(d - i - 2)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
      }
      return task(i + 1, `${a}x + ${b} = ${c}x + ${clean(d)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_parentheses(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i % 2 ? -i - 2 : i + 2;
      const k = level === "simple" ? 2 : 3 + (i % 4);
      const a = 2 + (i % 5), b = i + 1, c = 4 + (i % 4);
      const right = level === "high" ? k * (a * x + b) + c - (x - 2) : k * (a * x + b) + c;
      const expression = level === "high"
        ? `${k}(${a}x + ${b}) + ${c} - ((x - 2) - ${i % 3}) = ${clean(right + (i % 3))}`
        : `${k}(${a}x + ${b}) + ${c} = ${clean(right)}`;
      return task(i + 1, expression, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  powers_eval(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const base = level === "simple" ? 2 + (i % 7) : level === "medium" ? -(2 + (i % 5)) : (i % 2 ? -1 : 1) * (2 + (i % 4));
      const exp = level === "simple" ? 2 + (i % 3) : 3 + (i % 3);
      if (level === "high") {
        const second = 1 + (i % 4);
        return task(i + 1, `${par(base)}^${exp} - ${par(base)}^2 + ${second}`, clean(base ** exp - base ** 2 + second), level, skill.id, skill.task_type);
      }
      return task(i + 1, `${par(base)}^${exp}`, clean(base ** exp), level, skill.id, skill.task_type);
    });
  },

  power_rules(skill, level, count) {
    const vars = ["x", "a", "m", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 2 + (i % 6), b = 3 + (i % 5);
      const expression = skill.slug.includes("divide")
        ? level === "high" ? `${v}^${a + b + 2} : ${v}^${b} ${mul} ${v}^2` : level === "medium" ? `${v}^${a + b + 1} : ${v}^${b}` : `${v}^${a + b} : ${v}^${b}`
        : skill.slug.includes("power-of-power")
          ? level === "high" ? `(${v}^${a})^${b} : ${v}^${a} ${mul} ${v}^2` : level === "medium" ? `(${v}^${a})^${b} ${mul} ${v}` : `(${v}^${a})^${b}`
          : level === "high" ? `${v}^${a} ${mul} (${v}^${b} ${mul} ${v}^2)` : level === "medium" ? `${v}^${a} ${mul} ${v}^${b} ${mul} ${v}` : `${v}^${a} ${mul} ${v}^${b}`;
      const answer = skill.slug.includes("divide")
        ? level === "high" ? `${v}^${a + 4}` : level === "medium" ? `${v}^${a + 1}` : `${v}^${a}`
        : skill.slug.includes("power-of-power")
          ? level === "high" ? `${v}^${a * b - a + 2}` : level === "medium" ? `${v}^${a * b + 1}` : `${v}^${a * b}`
          : level === "high" ? `${v}^${a + b + 2}` : level === "medium" ? `${v}^${a + b + 1}` : `${v}^${a + b}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  monomial_standard(skill, level, count) {
    const vars = [["a", "b"], ["x", "y"], ["m", "n"]];
    return Array.from({ length: count }, (_, i) => {
      const [v1, v2] = shufflePick(vars, i);
      const k1 = 2 + (i % 5), k2 = level === "simple" ? 3 : -(3 + i % 4);
      if (level === "high") {
        const k3 = 2 + (i % 3);
        const expression = `${k1}${v1}^2 ${mul} ${k2}${v2} ${mul} ${k3}${v1}${v2}^2`;
        return task(i + 1, expression, `${k1 * k2 * k3}${v1}^3${v2}^3`, level, skill.id, skill.task_type);
      }
      const expression = level === "medium" ? `${k1}${v1}^2 ${mul} ${k2}${v1}${v2}` : `${k1}${v1} ${mul} ${k2}${v2}`;
      const answer = level === "medium" ? `${k1 * k2}${v1}^3${v2}` : `${k1 * k2}${v1}${v2}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  monomial_multiply(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 7), b = level === "simple" ? 3 + (i % 5) : -(3 + (i % 5));
      const p = 1 + (i % 4), q = 2 + (i % 5);
      if (level === "high") {
        const c = i % 2 ? -2 : 3;
        return task(i + 1, `${a}x^${p}y ${mul} ${b}x^${q}y^2 ${mul} ${c}xy`, `${a * b * c}x^${p + q + 1}y^4`, level, skill.id, skill.task_type);
      }
      const yPart = level === "medium" ? "y" : "";
      return task(i + 1, `${a}x^${p}${yPart} ${mul} ${b}x^${q}${yPart}`, `${a * b}x^${p + q}${level === "medium" ? "y^2" : ""}`, level, skill.id, skill.task_type);
    });
  },

  polynomial_add(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + i, b = 3 + (i % 5), c = 4 + (i % 4), d = 1 + i;
      const sign = skill.slug.includes("subtract") ? "-" : "+";
      const expression = level === "high"
        ? `(${a}x^2 - ${b}x + ${i + 1}) ${sign} (${c}x^2 + ${d}x - ${i + 3}) ${sign === "+" ? "+" : "-"} (${2}x^2 - ${i + 2})`
        : level === "medium"
          ? `(${a}x^2 + ${b}x - ${i + 1}) ${sign} (${c}x^2 + ${d}x + ${i + 3})`
          : `(${a}x + ${b}) ${sign} (${c}x + ${d})`;
      let answer;
      if (level === "high") {
        const s = sign === "+" ? 1 : -1;
        answer = `${a + s * c + s * 2}x^2 ${-b + s * d >= 0 ? "+" : "-"} ${Math.abs(-b + s * d)}x ${i + 1 + s * (-(i + 3)) + s * (-(i + 2)) < 0 ? "-" : "+"} ${Math.abs(i + 1 + s * (-(i + 3)) + s * (-(i + 2)))}`;
      } else if (level === "medium") {
        const s = sign === "+" ? 1 : -1;
        answer = `${a + s * c}x^2 + ${b + s * d}x ${-i - 1 + s * (i + 3) < 0 ? "-" : "+"} ${Math.abs(-i - 1 + s * (i + 3))}`;
      } else {
        answer = sign === "+" ? `${a + c}x + ${b + d}` : `${a - c}x ${b - d < 0 ? "-" : "+"} ${Math.abs(b - d)}`;
      }
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  multiply_poly(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 5), b = 1 + i, c = level === "simple" ? 1 : 2 + (i % 4), d = 3 + (i % 5);
      const cx = coefVar(c, "x");
      const expression = skill.slug.includes("monomial")
        ? level === "high" ? `${a}x(${c}x^2 - ${d}x + ${i + 2})` : level === "medium" ? `${a}x(${cx} - ${d}) + ${i + 1}x` : `${a}x(${cx} + ${d})`
        : level === "high" ? `(${a}x - ${b})(${cx} + ${d}) - ${i + 1}x` : level === "medium" ? `(${a}x - ${b})(${cx} + ${d}) + ${i + 1}` : `(${a}x + ${b})(${cx} + ${d})`;
      const answer = skill.slug.includes("monomial")
        ? level === "high" ? `${a * c}x^3 - ${a * d}x^2 + ${a * (i + 2)}x` : level === "medium" ? `${a * c}x^2 ${-a * d + i + 1 < 0 ? "-" : "+"} ${Math.abs(-a * d + i + 1)}x` : `${a * c}x^2 + ${a * d}x`
        : level === "high" ? `${a * c}x^2 + ${a * d - b * c - i - 1}x - ${b * d}` : level === "medium" ? `${a * c}x^2 + ${a * d - b * c}x ${-b * d + i + 1 < 0 ? "-" : "+"} ${Math.abs(-b * d + i + 1)}` : `${a * c}x^2 + ${a * d + b * c}x + ${b * d}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  factor_common(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = 2 + (i % 7), a = 3 + (i % 5), b = 4 + (i % 4);
      if (level === "high") {
        return task(i + 1, `${k * a}x^2y + ${k * b}xy^2 - ${k * (i + 1)}xy`, `${k}xy(${a}x + ${b}y - ${i + 1})`, level, skill.id, skill.task_type);
      }
      const expression = level === "medium" ? `${k * a}x^2 + ${k * b}x` : `${k * a}x + ${k * b}`;
      const answer = level === "medium" ? `${k}x(${a}x + ${b})` : `${k}(${a}x + ${b})`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  fsu(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = level === "simple" ? 2 + (i % 6) : 3 + (i % 7);
      const b = 1 + (i % 5);
      let expression, answer;
      if (skill.slug.includes("square-sum")) {
        expression = level === "high" ? `(${a}x + ${b})^2 - (${a}x - ${b})^2` : level === "medium" ? `(${a}x + ${coefVar(b, "y")})^2` : `(${a}x + ${b})^2`;
        answer = level === "high" ? `${4 * a * b}x` : level === "medium" ? `${a * a}x^2 + ${2 * a * b}xy + ${coefVar(b * b, "y^2")}` : `${a * a}x^2 + ${2 * a * b}x + ${b * b}`;
      } else if (skill.slug.includes("square-difference")) {
        expression = level === "high" ? `(${a}x - ${b})^2 + ${2 * a * b}x` : level === "medium" ? `(${a}x - ${coefVar(b, "y")})^2` : `(${a}x - ${b})^2`;
        answer = level === "high" ? `${a * a}x^2 + ${b * b}` : level === "medium" ? `${a * a}x^2 - ${2 * a * b}xy + ${coefVar(b * b, "y^2")}` : `${a * a}x^2 - ${2 * a * b}x + ${b * b}`;
      } else {
        expression = level === "high" ? `(${a}x - ${b})(${a}x + ${b}) + (${b} - ${a}x)^2` : level === "medium" ? `(${a}x - ${b}y)(${a}x + ${b}y)` : `(${a}x - ${b})(${a}x + ${b})`;
        answer = level === "high" ? `-${2 * a * b}x` : level === "medium" ? `${a * a}x^2 - ${b * b}y^2` : `${a * a}x^2 - ${b * b}`;
      }
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  fsu_factor(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 6), b = 1 + (i % 5);
      let expression, answer;
      if (skill.slug.includes("factor-square-sum")) {
        expression = level === "high" ? `${a * a}x^2 + ${2 * a * b}x + ${b * b} - y^2` : level === "medium" ? `${a * a}x^2 + ${2 * a * b}xy + ${b * b}y^2` : `${a * a}x^2 + ${2 * a * b}x + ${b * b}`;
        answer = level === "high" ? `(${a}x + ${b} - y)(${a}x + ${b} + y)` : level === "medium" ? `(${a}x + ${b}y)^2` : `(${a}x + ${b})^2`;
      } else if (skill.slug.includes("factor-square-difference")) {
        expression = level === "high" ? `${a * a}x^2 - ${2 * a * b}x + ${b * b} - y^2` : level === "medium" ? `${a * a}x^2 - ${2 * a * b}xy + ${b * b}y^2` : `${a * a}x^2 - ${2 * a * b}x + ${b * b}`;
        answer = level === "high" ? `(${a}x - ${b} - y)(${a}x - ${b} + y)` : level === "medium" ? `(${a}x - ${b}y)^2` : `(${a}x - ${b})^2`;
      } else {
        expression = level === "high" ? `${a * a}x^2 - (${coefVar(b, "y")} + z)^2` : level === "medium" ? `${a * a}x^2 - ${coefVar(b * b, "a^2")}` : `${a * a}x^2 - ${b * b}`;
        answer = level === "high" ? `(${a}x - ${coefVar(b, "y")} - z)(${a}x + ${coefVar(b, "y")} + z)` : level === "medium" ? `(${a}x - ${coefVar(b, "a")})(${a}x + ${coefVar(b, "a")})` : `(${a}x - ${b})(${a}x + ${b})`;
      }
      return task(i + 1, expression, answer, level, skill.id, skill.task_type, "Разложите на множители.");
    });
  },

  linear_function(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = level === "simple" ? 2 + (i % 4) : -(2 + (i % 5));
      const b = i - 5;
      const x = level === "high" ? -i : i + 1;
      if (level === "high") {
        const expression = `y = ${k}(x - ${i + 1}) ${b < 0 ? "-" : "+"} ${Math.abs(b)}, x = ${clean(x)}`;
        return task(i + 1, expression, clean(k * (x - i - 1) + b), level, skill.id, skill.task_type, "Найдите значение функции.");
      }
      const expression = `y = ${k}x ${b < 0 ? "-" : "+"} ${Math.abs(b)}, x = ${clean(x)}`;
      return task(i + 1, expression, clean(k * x + b), level, skill.id, skill.task_type, "Найдите значение функции.");
    });
  },

  linear_find_x(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = level === "simple" ? 2 + (i % 5) : -(2 + (i % 5));
      const b = i - 4;
      const x = i + 1;
      const y = level === "high" ? k * (x - 2) + b : k * x + b;
      const expression = level === "high"
        ? `y = ${k}(x - 2) ${b < 0 ? "-" : "+"} ${Math.abs(b)}, y = ${clean(y)}`
        : `y = ${k}x ${b < 0 ? "-" : "+"} ${Math.abs(b)}, y = ${clean(y)}`;
      return task(i + 1, expression, clean(x), level, skill.id, skill.task_type, "Найдите x по значению y.");
    });
  },

  linear_x_intercept(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = 2 + (i % 6);
      const root = level === "simple" ? i + 1 : level === "medium" ? -(i + 1) : i + 0.5;
      const b = -k * root;
      const shift = i % 3;
      const highB = b + k * shift;
      const expression = level === "high"
        ? `y = ${k}(x - ${shift}) ${highB < 0 ? "-" : "+"} ${clean(Math.abs(highB))}` 
        : level === "medium"
          ? `y = ${k}(x + 1) ${b - k < 0 ? "-" : "+"} ${clean(Math.abs(b - k))}`
        : `y = ${k}x ${b < 0 ? "-" : "+"} ${clean(Math.abs(b))}`;
      return task(i + 1, expression, `(${clean(root)}; 0)`, level, skill.id, skill.task_type, "Найдите точку пересечения графика с осью x.");
    });
  },

  linear_point_check(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = level === "simple" ? 2 : -2 - (i % 4);
      const b = i - 3;
      const x = i % 2 ? i + 1 : -i - 1;
      const y = level === "high" ? k * (x - 1) + b + (i % 3 === 0 ? 1 : 0) : k * x + b + (i % 3 === 0 ? 1 : 0);
      const ok = level === "high" ? y === k * (x - 1) + b : y === k * x + b;
      const expression = level === "high" ? `y = ${k}(x - 1) ${b < 0 ? "-" : "+"} ${Math.abs(b)}, A(${clean(x)}; ${clean(y)})` : `y = ${k}x ${b < 0 ? "-" : "+"} ${Math.abs(b)}, A(${clean(x)}; ${clean(y)})`;
      return task(i + 1, expression, ok ? "да" : "нет", level, skill.id, skill.task_type, "Проверьте, принадлежит ли точка графику.");
    });
  },

  system_solve(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      if (level === "medium" || level === "high") {
        const den = 2 + (i % 3);
        const xNum = i % 2 ? -(2 * i + 3) : 2 * i + 3;
        const yNum = -(i + 4);
        const a = 2 + (i % 4), b = 1 + (i % 3), c = 1 + (i % 5), d = 3 + (i % 4);
        const eNum = a * xNum + b * yNum;
        const fNum = c * xNum - d * yNum;
        if (level === "high" && i % 3 === 0) {
          const shiftedFirstNum = (a + c) * xNum + b * yNum - a * den;
          const shiftedSecondNum = c * (xNum - den) - d * yNum;
          return task(
            i + 1,
            systemExpr(`${coefVar(a * den, "x")} + ${coefVar(b * den, "y")} = ${clean(shiftedFirstNum + a * den)}`, `${coefVar(c * den, "(x - 1)")} - ${coefVar(d * den, "y")} = ${clean(shiftedSecondNum)}`),
            `x = ${frac(xNum, den)}, y = ${frac(yNum, den)}`,
            level,
            skill.id,
            skill.task_type,
            "Решите систему со скобками.",
          );
        }
        if (level === "high" && i % 3 === 1) {
          const den2 = 3 + (i % 3);
          const secondNum = c * xNum - d * yNum;
          return task(
            i + 1,
            systemExpr(`\\frac{x + y}{${den}} = ${frac(xNum + yNum, den * den)}`, `\\frac{${coefVar(c, "x")} - ${coefVar(d, "y")}}{${den2}} = ${frac(secondNum, den * den2)}`),
            `x = ${frac(xNum, den)}, y = ${frac(yNum, den)}`,
            level,
            skill.id,
            skill.task_type,
            "Решите систему с дробными коэффициентами.",
          );
        }
        const expression = level === "high"
          ? systemExpr(`${clean(a * xNum - (2 + (i % 5)) * yNum)} - ${coefVar(a * den, "x")} = ${coefVar(-(2 + (i % 5)) * den, "y")}`, `${coefVar(c * den, "x")} - ${coefVar(d * den, "y")} = ${clean(fNum)}`)
          : systemExpr(`${coefVar(a * den, "x")} + ${coefVar(b * den, "y")} = ${clean(eNum)}`, `${coefVar(c * den, "x")} - ${coefVar(d * den, "y")} = ${clean(fNum)}`);
        return task(i + 1, expression, `x = ${frac(xNum, den)}, y = ${frac(yNum, den)}`, level, skill.id, skill.task_type, "Решите систему.");
      }
      const x = i + 1, y = level === "simple" ? i + 2 : -(i + 2);
      const a = 2 + (i % 4), b = 1 + (i % 3), c = 1 + (i % 5), d = 3 + (i % 4);
      const e = a * x + b * y, f = c * x - d * y;
      const shiftedX = c === 1 ? "(x - 1)" : `${c}(x - 1)`;
      const rightCoef = -(2 + (i % 5));
      const leftConst = a * x + rightCoef * y;
      const expression = level === "high"
        ? systemExpr(`${clean(leftConst)} - ${coefVar(a, "x")} = ${coefVar(rightCoef, "y")}`, `${shiftedX} - ${coefVar(d, "y")} = ${clean(f - c)}`)
        : level === "medium"
          ? systemExpr(`${a}(x + 1) + ${coefVar(b, "y")} = ${clean(e + a)}`, `${coefVar(c, "x")} - ${coefVar(d, "y")} = ${clean(f)}`)
        : systemExpr(`${coefVar(a, "x")} + ${coefVar(b, "y")} = ${clean(e)}`, `${coefVar(c, "x")} - ${coefVar(d, "y")} = ${clean(f)}`);
      return task(i + 1, expression, `x = ${clean(x)}, y = ${clean(y)}`, level, skill.id, skill.task_type, "Решите систему.");
    });
  },

  system_check(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i + 1, y = level === "simple" ? i + 2 : -(i + 2);
      const a = 2 + (i % 4), b = 1 + (i % 3), c = 1 + (i % 5), d = 3 + (i % 4);
      const e = a * x + b * y, f = level === "high" ? c * (x - 1) - d * y + (i % 3 === 0 ? 1 : 0) : c * x - d * y + (i % 3 === 0 ? 1 : 0);
      const ok = level === "high" ? f === c * (x - 1) - d * y : f === c * x - d * y;
      const shiftedX = c === 1 ? "(x - 1)" : `${c}(x - 1)`;
      const rightCoef = -(2 + (i % 5));
      const leftConst = a * x + rightCoef * y;
      const expression = level === "high"
        ? systemExpr(`${clean(leftConst)} - ${coefVar(a, "x")} = ${coefVar(rightCoef, "y")}`, `${shiftedX} - ${coefVar(d, "y")} = ${clean(f)}`, `, (${clean(x)}; ${clean(y)})`)
        : level === "medium"
          ? systemExpr(`${a}(x + 1) + ${coefVar(b, "y")} = ${clean(e + a)}`, `${coefVar(c, "x")} - ${coefVar(d, "y")} = ${clean(f)}`, `, (${clean(x)}; ${clean(y)})`)
          : systemExpr(`${coefVar(a, "x")} + ${coefVar(b, "y")} = ${clean(e)}`, `${coefVar(c, "x")} - ${coefVar(d, "y")} = ${clean(f)}`, `, (${clean(x)}; ${clean(y)})`);
      return task(i + 1, expression, ok ? "да" : "нет", level, skill.id, skill.task_type, "Проверьте, является ли пара решением системы.");
    });
  },

  system_check_3_vars(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i + 1;
      const y = level === "simple" ? i + 2 : -(i + 2);
      const z = level === "high" ? -(i + 3) : i + 3;
      const miss = i % 3 === 0 ? 1 : 0;
      const first = level === "high"
        ? `${coefVar(2 + (i % 3), "x")} + y - z = ${clean((2 + (i % 3)) * x + y - z + miss)}`
        : `x + y - z = ${clean(x + y - z + miss)}`;
      const second = level === "simple"
        ? `x - y + z = ${clean(x - y + z)}`
        : `${coefVar(2, "x")} - y + ${coefVar(2 + (i % 2), "z")} = ${clean(2 * x - y + (2 + (i % 2)) * z)}`;
      const third = level === "high"
        ? `x + ${coefVar(2 + (i % 4), "y")} + ${coefVar(3, "z")} = ${clean(x + (2 + (i % 4)) * y + 3 * z)}`
        : `x + y + z = ${clean(x + y + z)}`;
      const ok = miss === 0;
      return task(i + 1, systemExpr(first, `${second} \\\\ ${third}`, `, (${clean(x)}; ${clean(y)}; ${clean(z)})`), ok ? "да" : "нет", level, skill.id, skill.task_type, "Проверьте, является ли тройка решением системы.");
    });
  },

  system_express_variable(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 7), b = level === "simple" ? 1 : 2 + (i % 5), c = 6 + i;
      const g = gcd(c, b);
      const yTerm = b === 1 ? "y" : `${b}y`;
      const expression = level === "high" ? `${a}(x - 1) + ${b}(y + 2) = ${c}` : level === "medium" ? `${a}(x + 1) + ${yTerm} = ${c}` : `${coefVar(a, "x")} + ${yTerm} = ${c}`;
      const answer = b === 1
        ? level === "high" ? `y = ${c + a - 2 * b} - ${a}x` : level === "medium" ? `y = ${c - a} - ${a}x` : `y = ${c} - ${a}x`
        : level === "high" ? `y = (${c + a - 2 * b} - ${a}x) : ${b}` : level === "medium" ? `y = (${c - a} - ${a}x) : ${b}` : `y = (${c / g} - ${a / g}x) : ${b / g}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type, "Выразите y через x.");
    });
  },
};

const skillCatalog = [
  ["arithmetic", "Сложение чисел с разными знаками", "signed-add", "signed_add"],
  ["arithmetic", "Сложение двух отрицательных чисел", "negative-sum", "negative_sum"],
  ["arithmetic", "Вычитание положительных и отрицательных чисел", "subtract-signed", "subtract_signed"],
  ["arithmetic", "Сложение и вычитание десятичных чисел", "decimal-add", "decimal_add"],
  ["arithmetic", "Умножение и деление десятичных чисел", "decimal-mul-div", "decimal_mul_div"],
  ["arithmetic", "Порядок действий без степеней", "order-actions", "order_actions"],
  ["expressions", "Раскрытие скобок со знаком плюс", "plus-parentheses", "remove_plus_parentheses"],
  ["expressions", "Раскрытие скобок со знаком минус", "minus-parentheses", "remove_minus_parentheses"],
  ["expressions", "Умножение числа на скобку", "distribute-number", "distribute"],
  ["expressions", "Приведение подобных слагаемых", "combine-like", "combine_like"],
  ["expressions", "Значение выражения при заданной переменной", "eval-expression", "eval_value"],
  ["equations", "Уравнения вида ax + b = c", "equation-axb-c", "equation_ax_b_c"],
  ["equations", "Уравнения вида x + a = b", "equation-x-plus-a", "equation_one_step_add"],
  ["equations", "Уравнения вида ax = b", "equation-ax-b", "equation_one_step_mul"],
  ["equations", "Уравнения с переменной в обеих частях", "equation-both-sides", "equation_both_sides"],
  ["equations", "Уравнения со скобками", "equation-parentheses", "equation_parentheses"],
  ["equations", "Уравнения с десятичными коэффициентами", "equation-decimal", "equation_decimal"],
  ["powers", "Вычисление степеней", "powers-evaluate", "powers_eval"],
  ["powers", "Умножение степеней с одинаковым основанием", "multiply-same-base", "power_rules"],
  ["powers", "Деление степеней с одинаковым основанием", "divide-same-base", "power_rules"],
  ["powers", "Степень степени", "power-of-power", "power_rules"],
  ["monomials", "Приведение одночлена к стандартному виду", "monomial-standard", "monomial_standard"],
  ["monomials", "Умножение одночленов", "monomial-multiply", "monomial_multiply"],
  ["polynomials", "Сложение многочленов", "polynomial-add", "polynomial_add"],
  ["polynomials", "Вычитание многочленов", "polynomial-subtract", "polynomial_add"],
  ["polynomials", "Умножение одночлена на многочлен", "multiply-monomial-polynomial", "multiply_poly"],
  ["polynomials", "Умножение многочлена на многочлен", "multiply-polynomials", "multiply_poly"],
  ["polynomials", "Вынесение общего множителя", "factor-common", "factor_common"],
  ["fsu", "Квадрат суммы", "square-sum", "fsu"],
  ["fsu", "Квадрат разности", "square-difference", "fsu"],
  ["fsu", "Разность квадратов", "difference-squares", "fsu"],
  ["fsu", "Разложение квадрата суммы", "factor-square-sum", "fsu_factor"],
  ["fsu", "Разложение квадрата разности", "factor-square-difference", "fsu_factor"],
  ["fsu", "Разложение разности квадратов", "factor-difference-squares", "fsu_factor"],
  ["functions", "Значение линейной функции", "linear-function-value", "linear_function"],
  ["functions", "Найти x по значению линейной функции", "linear-function-find-x", "linear_find_x"],
  ["functions", "Точка пересечения линейной функции с осью x", "linear-function-x-intercept", "linear_x_intercept"],
  ["functions", "Проверка точки на графике линейной функции", "linear-function-point-check", "linear_point_check"],
  ["systems", "Проверка решения системы", "system-check-solution", "system_check"],
  ["systems", "Выразить переменную из линейного уравнения с двумя переменными", "system-express-variable", "system_express_variable"],
  ["systems", "Решение системы линейных уравнений", "system-solve", "system_solve"],
  ["systems", "Проверка решения системы с тремя переменными", "system-check-solution-3-vars", "system_check_3_vars"],
];

const groupTitles = {
  arithmetic: "Арифметическая база",
  expressions: "Алгебраические выражения",
  equations: "Линейные уравнения",
  powers: "Степени",
  monomials: "Одночлены",
  polynomials: "Многочлены",
  fsu: "Формулы сокращённого умножения",
  functions: "Линейная функция",
  systems: "Системы линейных уравнений",
};

const source = fs.existsSync(SOURCE) ? JSON.parse(fs.readFileSync(SOURCE, "utf8")) : null;
const sourceTopics = source?.topics?.map(t => ({ id: t.topic_id, title: t.meta?.title, tasks: (t.blocks ?? []).flatMap(b => (b.zadaniya ?? []).flatMap(z => z.tasks ?? [])).length })) ?? [];
const zvavichStats = fs.existsSync(ZVAVICH_STATS) ? JSON.parse(fs.readFileSync(ZVAVICH_STATS, "utf8")) : null;
const makarychevStats = fs.existsSync(MAKARYCHEV_STATS) ? JSON.parse(fs.readFileSync(MAKARYCHEV_STATS, "utf8")) : null;

const makarychevSectionsByGroup = {
  arithmetic: ["§1 Числа и выражения"],
  expressions: ["§2 Преобразование выражений"],
  equations: ["§3 Уравнения с одной переменной"],
  powers: ["§6 Степень и её свойства"],
  monomials: ["§7 Одночлены"],
  polynomials: ["§8 Сумма и разность многочленов", "§9 Произведение одночлена и многочлена", "§10 Произведение многочленов"],
  fsu: ["§11 Квадрат суммы и квадрат разности", "§12 Разность квадратов, сумма и разность кубов", "§13 Преобразование целых выражений"],
  functions: ["§5 Линейная функция"],
  systems: ["§14 Линейные уравнения с двумя переменными", "§15 Решение систем линейных уравнений"],
};

function referenceProfile(skill) {
  const numericId = String(Number(skill.id));
  const zvavichRefs = numericId === skill.id
    ? (zvavichStats?.by_skill?.[skill.id] ?? 0)
    : (zvavichStats?.by_skill?.[skill.id] ?? 0) + (zvavichStats?.by_skill?.[numericId] ?? 0);
  const makarychevSections = makarychevSectionsByGroup[skill.group] ?? [];
  const makarychevRefs = makarychevSections.reduce((sum, section) => sum + (makarychevStats?.by_section?.[section] ?? 0), 0);
  return {
    mode: "difficulty_calibrated_from_non_verbatim_references",
    zvavich_refs: zvavichRefs,
    makarychev_sections: makarychevSections,
    makarychev_refs: makarychevRefs,
  };
}

function makeHomeworkSets(skill, levelBlocks) {
  const byLevel = Object.fromEntries(levelBlocks.map(level => [level.id, level.tasks]));
  const take = (level, start, count) => byLevel[level].slice(start, start + count).map((task, index) => ({
    ...task,
    id: index + 1,
    source_task_id: task.id,
  }));

  return [
    {
      id: "hw-simple",
      title: "Домашка: закрепить шаблон",
      target_minutes: 25,
      tasks_count: 18,
      mix: { simple: 14, medium: 4, high: 0 },
      tasks: [
        ...take("simple", 0, 14),
        ...take("medium", 0, 4),
      ].map((task, index) => ({ ...task, id: index + 1 })),
    },
    {
      id: "hw-medium",
      title: "Домашка: рабочий уровень",
      target_minutes: 35,
      tasks_count: 18,
      mix: { simple: 5, medium: 10, high: 3 },
      tasks: [
        ...take("simple", 14, 5),
        ...take("medium", 4, 10),
        ...take("high", 0, 3),
      ].map((task, index) => ({ ...task, id: index + 1 })),
    },
    {
      id: "hw-high",
      title: "Домашка: проверка прочности",
      target_minutes: 45,
      tasks_count: 20,
      mix: { simple: 4, medium: 6, high: 10 },
      tasks: [
        ...take("simple", 6, 4),
        ...take("medium", 12, 6),
        ...take("high", 3, 10),
      ].map((task, index) => ({ ...task, id: index + 1 })),
    },
  ];
}

const skills = skillCatalog.map(([group, title, slug, factoryName], index) => {
  const id = String(index + 1).padStart(2, "0");
  const skill = {
    id,
    slug,
    title,
    group,
    group_title: groupTitles[group],
    task_type: factoryName,
    source: "Макарычев Ю.Н. — Алгебра 7 класс, 2023; Звавич Л.И. и др. — Алгебра 7 класс. Дидактические материалы, 2012; PALOMATIKA generated practice",
  };
  const factory = factories[factoryName];
  const levelBlocks = levels.map(level => ({
    ...level,
    tasks: factory(skill, level.id, level.id === "high" ? 18 : 20),
  }));
  return {
    ...skill,
    reference_profile: referenceProfile(skill),
    levels: levelBlocks,
    homework_sets: makeHomeworkSets(skill, levelBlocks),
    tasks_count: levelBlocks.reduce((sum, level) => sum + level.tasks.length, 0),
  };
});

const data = {
  generated_at: new Date().toISOString(),
  grade: 7,
  subject: "algebra",
  source_topics: sourceTopics,
  levels,
  groups: Object.entries(groupTitles).map(([id, title]) => ({ id, title })),
  skills,
};

fs.mkdirSync(OUT.substring(0, OUT.lastIndexOf("/")), { recursive: true });
fs.writeFileSync(OUT, JSON.stringify(data, null, 2));
console.log(`generated ${skills.length} skills, ${skills.reduce((sum, s) => sum + s.tasks_count, 0)} tasks -> ${OUT}`);
