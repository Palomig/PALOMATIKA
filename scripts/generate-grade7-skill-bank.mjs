#!/usr/bin/env node

import fs from "node:fs";

const OUT = "/home/dev/palomatika/storage/app/tasks/alg/grade_7/skills.json";
const SOURCE = "/var/www/html/grade7-topics.json";
const mul = "\\cdot";
const levels = [
  { id: "simple", title: "Простой", description: "Чистый шаблон, один основной шаг." },
  { id: "medium", title: "Средний", description: "Тот же навык с отрицательными числами, скобками или десятичными." },
  { id: "high", title: "Высокий", description: "Навык внутри проверки, ошибки ученика или обратной задачи." },
];

const clean = (n) => {
  const rounded = Math.round((n + Number.EPSILON) * 1000) / 1000;
  return Number.isInteger(rounded) ? String(rounded) : String(rounded).replace(".", ",");
};
const par = (n) => n < 0 ? `(${clean(n)})` : clean(n);
const gcd = (a, b) => b === 0 ? Math.abs(a) : gcd(b, a % b);
const shufflePick = (arr, i) => arr[i % arr.length];

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
      return task(i + 1, `${clean(aa)} + ${par(bb)}`, clean(aa + bb), level, skill.id, skill.task_type);
    });
  },

  negative_sum(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = level === "high" ? -(12 + i * 2) : -(3 + i);
      const b = level === "simple" ? -(5 + (i % 7)) : -(8 + i * 1.5);
      return task(i + 1, `${clean(a)} + (${clean(b)})`, clean(a + b), level, skill.id, skill.task_type);
    });
  },

  subtract_signed(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = level === "simple" ? 10 + i : (i % 2 ? -8 - i : 14 + i);
      const b = level === "high" ? (i % 2 ? -12 - i : 18 + i) : (i % 2 ? -4 - i : 3 + i);
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
          : `${clean(a + 7)} - ${clean(b + 4)} + ${clean(c)} - ${clean(0.3 * i + 0.8)}`;
      const answer = level === "simple" ? a + b : level === "medium" ? a + 3 - b + c : a + 7 - (b + 4) + c - (0.3 * i + 0.8);
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
          ? `${clean(a * 3)} : ${clean(0.6)}`
          : `${clean(a)} ${mul} ${clean(b)} : ${clean(c)}`;
      const answer = level === "simple" ? a * b : level === "medium" ? (a * 3) / 0.6 : a * b / c;
      return task(i + 1, expression, clean(answer), level, skill.id, skill.task_type);
    });
  },

  order_actions(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 6 + i, b = 2 + (i % 4), c = 3 + (i % 5);
      const expression = level === "simple"
        ? `${a} - ${b} ${mul} ${c}`
        : level === "medium"
          ? `(${a} - ${b}) ${mul} ${c}`
          : `${a * 3} : ${c} ${mul} ${b} - ${i}`;
      const answer = level === "simple" ? a - b * c : level === "medium" ? (a - b) * c : (a * 3) / c * b - i;
      return task(i + 1, expression, clean(answer), level, skill.id, skill.task_type);
    });
  },

  remove_plus_parentheses(skill, level, count) {
    const vars = ["x", "a", "m", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 2 + i, b = 3 + (i % 6);
      const expression = level === "simple" ? `${v} + (${a}${v} + ${b})` : level === "medium" ? `${a}${v} + (${b}${v} - ${i + 1})` : `${a}${v} - ${i} + (${b}${v} + ${i + 4} - ${v})`;
      const answer = level === "simple" ? `${a + 1}${v} + ${b}` : level === "medium" ? `${a + b}${v} - ${i + 1}` : `${a + b - 1}${v} + 4`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  remove_minus_parentheses(skill, level, count) {
    const vars = ["x", "a", "m", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 5 + i, b = 2 + (i % 5), c = 1 + (i % 4);
      const expression = level === "simple" ? `${a}${v} - (${b}${v} + ${c})` : level === "medium" ? `${a}${v} - (${b}${v} - ${c})` : `${a}${v} + ${c} - (${b}${v} - ${i + 2} + ${v})`;
      const answer = level === "simple" ? `${a - b}${v} - ${c}` : level === "medium" ? `${a - b}${v} + ${c}` : `${a - b - 1}${v} + ${c + i + 2}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  distribute(skill, level, count) {
    const vars = ["x", "a", "b", "m"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const k = level === "simple" ? 2 + (i % 7) : i % 2 ? -(2 + i % 5) : 3 + i % 6;
      const a = 2 + (i % 5), b = level === "high" ? -(1 + i % 6) : 1 + i % 8;
      const expression = `${clean(k)}(${a}${v} ${b < 0 ? "-" : "+"} ${Math.abs(b)})`;
      const answer = `${clean(k * a)}${v} ${k * b < 0 ? "-" : "+"} ${clean(Math.abs(k * b))}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  combine_like(skill, level, count) {
    const vars = ["x", "a", "b", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 3 + i, b = level === "simple" ? 5 + i : -(2 + i), c = level === "high" ? -1 * (4 + i) : 1 + (i % 4);
      const expression = `${a}${v} ${b < 0 ? "-" : "+"} ${Math.abs(b)}${v} ${c < 0 ? "-" : "+"} ${Math.abs(c)}`;
      return task(i + 1, expression, `${a + b}${v} ${c < 0 ? "-" : "+"} ${Math.abs(c)}`, level, skill.id, skill.task_type);
    });
  },

  eval_value(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 1 : level === "medium" ? -(i + 1) : (i + 1) / 2;
      const a = 2 + (i % 5), b = 3 + i;
      const expression = `${a}x ${b % 2 ? "+" : "-"} ${b}, при x = ${clean(x)}`;
      const answer = b % 2 ? a * x + b : a * x - b;
      return task(i + 1, expression, clean(answer), level, skill.id, skill.task_type);
    });
  },

  equation_ax_b_c(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 2 : level === "medium" ? -(i + 2) : (i % 2 ? -i - 2 : i + 2);
      const a = level === "high" ? 3 + (i % 5) : 2 + (i % 4);
      const b = i + 5;
      const c = a * x + b;
      return task(i + 1, `${a}x + ${b} = ${clean(c)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_one_step_add(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 3 : level === "medium" ? -(i + 3) : i % 2 ? -i - 4 : i + 4;
      const a = level === "high" ? -(5 + i) : 4 + i;
      const b = x + a;
      const expression = `x ${a < 0 ? "-" : "+"} ${Math.abs(a)} = ${clean(b)}`;
      return task(i + 1, expression, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_one_step_mul(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 2 : level === "medium" ? -(i + 2) : i % 2 ? -i - 3 : i + 3;
      const a = level === "high" ? -(2 + (i % 6)) : 2 + (i % 8);
      return task(i + 1, `${a}x = ${clean(a * x)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_decimal(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = level === "simple" ? i + 1 : level === "medium" ? -(i + 1) : (i + 2) / 2;
      const a = level === "high" ? 0.3 + (i % 5) / 10 : 0.5 + (i % 4) / 10;
      const b = 1.2 + i / 10;
      const c = a * x + b;
      return task(i + 1, `${clean(a)}x + ${clean(b)} = ${clean(c)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение с десятичными коэффициентами.");
    });
  },

  equation_both_sides(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i % 2 ? -(i + 1) : i + 1;
      const a = 5 + (i % 5), c = 2 + (i % 3), b = i + 4;
      const d = (a - c) * x + b;
      return task(i + 1, `${a}x + ${b} = ${c}x + ${clean(d)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  equation_parentheses(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i % 2 ? -i - 2 : i + 2;
      const k = level === "simple" ? 2 : 3 + (i % 4);
      const a = 2 + (i % 5), b = i + 1, c = 4 + (i % 4);
      const right = k * (a * x + b) + c;
      return task(i + 1, `${k}(${a}x + ${b}) + ${c} = ${clean(right)}`, clean(x), level, skill.id, skill.task_type, "Решите уравнение.");
    });
  },

  powers_eval(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const base = level === "simple" ? 2 + (i % 7) : level === "medium" ? -(2 + (i % 5)) : (i % 2 ? -1 : 1) * (2 + (i % 4));
      const exp = level === "simple" ? 2 + (i % 3) : 3 + (i % 3);
      return task(i + 1, `${par(base)}^${exp}`, clean(base ** exp), level, skill.id, skill.task_type);
    });
  },

  power_rules(skill, level, count) {
    const vars = ["x", "a", "m", "y"];
    return Array.from({ length: count }, (_, i) => {
      const v = shufflePick(vars, i);
      const a = 2 + (i % 6), b = 3 + (i % 5);
      const expression = skill.id.includes("divide") ? `${v}^${a + b} : ${v}^${b}` : skill.id.includes("power-of-power") ? `(${v}^${a})^${b}` : `${v}^${a} ${mul} ${v}^${b}`;
      const answer = skill.id.includes("divide") ? `${v}^${a}` : skill.id.includes("power-of-power") ? `${v}^${a * b}` : `${v}^${a + b}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  monomial_standard(skill, level, count) {
    const vars = [["a", "b"], ["x", "y"], ["m", "n"]];
    return Array.from({ length: count }, (_, i) => {
      const [v1, v2] = shufflePick(vars, i);
      const k1 = 2 + (i % 5), k2 = level === "simple" ? 3 : -(3 + i % 4);
      const expression = `${k1}${v1} ${mul} ${k2}${v2}`;
      return task(i + 1, expression, `${k1 * k2}${v1}${v2}`, level, skill.id, skill.task_type);
    });
  },

  monomial_multiply(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 7), b = level === "simple" ? 3 + (i % 5) : -(3 + (i % 5));
      const p = 1 + (i % 4), q = 2 + (i % 5);
      return task(i + 1, `${a}x^${p} ${mul} ${b}x^${q}`, `${a * b}x^${p + q}`, level, skill.id, skill.task_type);
    });
  },

  polynomial_add(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + i, b = 3 + (i % 5), c = 4 + (i % 4), d = 1 + i;
      const sign = skill.id.includes("subtract") ? "-" : "+";
      const expression = `(${a}x + ${b}) ${sign} (${c}x + ${d})`;
      const answer = sign === "+" ? `${a + c}x + ${b + d}` : `${a - c}x ${b - d < 0 ? "-" : "+"} ${Math.abs(b - d)}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  multiply_poly(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 5), b = 1 + i, c = level === "simple" ? 1 : 2 + (i % 4), d = 3 + (i % 5);
      const expression = skill.id.includes("monomial")
        ? `${a}x(${c}x + ${d})`
        : `(${a}x + ${b})(${c}x + ${d})`;
      const answer = skill.id.includes("monomial")
        ? `${a * c}x^2 + ${a * d}x`
        : `${a * c}x^2 + ${a * d + b * c}x + ${b * d}`;
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  factor_common(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = 2 + (i % 7), a = 3 + (i % 5), b = 4 + (i % 4);
      return task(i + 1, `${k * a}x + ${k * b}`, `${k}(${a}x + ${b})`, level, skill.id, skill.task_type);
    });
  },

  fsu(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = level === "simple" ? 2 + (i % 6) : 3 + (i % 7);
      const b = 1 + (i % 5);
      let expression, answer;
      if (skill.id.includes("square-sum")) {
        expression = `(${a}x + ${b})^2`; answer = `${a * a}x^2 + ${2 * a * b}x + ${b * b}`;
      } else if (skill.id.includes("square-difference")) {
        expression = `(${a}x - ${b})^2`; answer = `${a * a}x^2 - ${2 * a * b}x + ${b * b}`;
      } else {
        expression = `(${a}x - ${b})(${a}x + ${b})`; answer = `${a * a}x^2 - ${b * b}`;
      }
      return task(i + 1, expression, answer, level, skill.id, skill.task_type);
    });
  },

  fsu_factor(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 6), b = 1 + (i % 5);
      let expression, answer;
      if (skill.id.includes("factor-square-sum")) {
        expression = `${a * a}x^2 + ${2 * a * b}x + ${b * b}`; answer = `(${a}x + ${b})^2`;
      } else if (skill.id.includes("factor-square-difference")) {
        expression = `${a * a}x^2 - ${2 * a * b}x + ${b * b}`; answer = `(${a}x - ${b})^2`;
      } else {
        expression = `${a * a}x^2 - ${b * b}`; answer = `(${a}x - ${b})(${a}x + ${b})`;
      }
      return task(i + 1, expression, answer, level, skill.id, skill.task_type, "Разложите на множители.");
    });
  },

  linear_function(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = level === "simple" ? 2 + (i % 4) : -(2 + (i % 5));
      const b = i - 5;
      const x = level === "high" ? -i : i + 1;
      const expression = `y = ${k}x ${b < 0 ? "-" : "+"} ${Math.abs(b)}, x = ${clean(x)}`;
      return task(i + 1, expression, clean(k * x + b), level, skill.id, skill.task_type, "Найдите значение функции.");
    });
  },

  linear_find_x(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = level === "simple" ? 2 + (i % 5) : -(2 + (i % 5));
      const b = i - 4;
      const x = i + 1;
      const y = k * x + b;
      return task(i + 1, `y = ${k}x ${b < 0 ? "-" : "+"} ${Math.abs(b)}, y = ${clean(y)}`, clean(x), level, skill.id, skill.task_type, "Найдите x по значению y.");
    });
  },

  linear_x_intercept(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = 2 + (i % 6);
      const root = level === "simple" ? i + 1 : level === "medium" ? -(i + 1) : i + 0.5;
      const b = -k * root;
      return task(i + 1, `y = ${k}x ${b < 0 ? "-" : "+"} ${clean(Math.abs(b))}`, `(${clean(root)}; 0)`, level, skill.id, skill.task_type, "Найдите точку пересечения графика с осью x.");
    });
  },

  linear_point_check(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const k = level === "simple" ? 2 : -2 - (i % 4);
      const b = i - 3;
      const x = i % 2 ? i + 1 : -i - 1;
      const y = k * x + b + (i % 3 === 0 ? 1 : 0);
      const ok = y === k * x + b;
      return task(i + 1, `y = ${k}x ${b < 0 ? "-" : "+"} ${Math.abs(b)}, A(${clean(x)}; ${clean(y)})`, ok ? "да" : "нет", level, skill.id, skill.task_type, "Проверьте, принадлежит ли точка графику.");
    });
  },

  system_solve(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i + 1, y = level === "simple" ? i + 2 : -(i + 2);
      const a = 2 + (i % 4), b = 1 + (i % 3), c = 1 + (i % 5), d = 3 + (i % 4);
      const e = a * x + b * y, f = c * x - d * y;
      const expression = `{ ${a}x + ${b}y = ${clean(e)}, ${c}x - ${d}y = ${clean(f)} }`;
      return task(i + 1, expression, `x = ${clean(x)}, y = ${clean(y)}`, level, skill.id, skill.task_type, "Решите систему.");
    });
  },

  system_check(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const x = i + 1, y = level === "simple" ? i + 2 : -(i + 2);
      const a = 2 + (i % 4), b = 1 + (i % 3), c = 1 + (i % 5), d = 3 + (i % 4);
      const e = a * x + b * y, f = c * x - d * y + (i % 3 === 0 ? 1 : 0);
      const ok = f === c * x - d * y;
      const expression = `{ ${a}x + ${b}y = ${clean(e)}, ${c}x - ${d}y = ${clean(f)} }, (${clean(x)}; ${clean(y)})`;
      return task(i + 1, expression, ok ? "да" : "нет", level, skill.id, skill.task_type, "Проверьте, является ли пара решением системы.");
    });
  },

  system_express_variable(skill, level, count) {
    return Array.from({ length: count }, (_, i) => {
      const a = 2 + (i % 7), b = level === "simple" ? 1 : 2 + (i % 5), c = 6 + i;
      const g = gcd(c, b);
      const yTerm = b === 1 ? "y" : `${b}y`;
      const expression = `${a}x + ${yTerm} = ${c}`;
      const answer = b === 1 ? `y = ${c} - ${a}x` : `y = (${c / g} - ${a / g}x) : ${b / g}`;
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

const skills = skillCatalog.map(([group, title, slug, factoryName], index) => {
  const id = String(index + 1).padStart(2, "0");
  const skill = {
    id,
    slug,
    title,
    group,
    group_title: groupTitles[group],
    task_type: factoryName,
    source: "Макарычев Ю.Н. — Алгебра 7 класс, 2023; PALOMATIKA generated practice",
  };
  const factory = factories[factoryName];
  const levelBlocks = levels.map(level => ({
    ...level,
    tasks: factory(skill, level.id, level.id === "high" ? 18 : 20),
  }));
  return {
    ...skill,
    levels: levelBlocks,
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
