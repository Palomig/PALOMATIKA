#!/usr/bin/env node
// Convert curated Makarychev problems → 11 topic_XX.json files in OGE-bank format.
// Output: storage/app/tasks/grade_7/topic_XX.json
import fs from "node:fs";
import path from "node:path";

const SRC = "/home/dev/palomatika/storage/app/tasks/grade_7/_ref/_curated.json";
const OUT_DIR = "/home/dev/palomatika/storage/app/tasks/grade_7";

// Topic catalog — order, source sections, meta, skills (что должен уметь ученик)
const TOPICS = [
  {
    id: "01", title: "Раскрытие скобок и подобные",
    description: "Преобразование выражений: раскрытие скобок, приведение подобных слагаемых",
    color: "blue", icon: "calculator",
    sections: ["§2 Преобразование выражений"],
    skills: [
      "Раскрывать скобки со знаком плюс перед скобкой",
      "Раскрывать скобки со знаком минус перед скобкой",
      "Умножать число на сумму/разность (распределительное свойство)",
      "Приводить подобные слагаемые",
      "Упрощать выражения с несколькими скобками",
      "Доказывать тождества преобразованием"
    ]
  },
  {
    id: "02", title: "Линейные уравнения",
    description: "Решение линейных уравнений с одной переменной и текстовых задач",
    color: "indigo", icon: "equation",
    sections: ["§3 Уравнения с одной переменной"],
    skills: [
      "Решать простейшие уравнения вида ax + b = c",
      "Решать уравнения вида ax + b = cx + d (с переменной в обеих частях)",
      "Решать уравнения с раскрытием скобок",
      "Решать уравнения с дробями (приведение к общему знаменателю)",
      "Определять, имеет ли уравнение корни (нет / один / бесконечно)",
      "Составлять уравнение по условию текстовой задачи",
      "Решать текстовые задачи на движение, работу, проценты через уравнение"
    ]
  },
  {
    id: "03", title: "Степень с натуральным показателем",
    description: "Свойства степеней, умножение и деление, возведение в степень",
    color: "purple", icon: "exponent",
    sections: ["§6 Степень и её свойства"],
    skills: [
      "Возводить число в натуральную степень",
      "Применять свойство xᵃ · xᵇ = x^(a+b)",
      "Применять свойство xᵃ / xᵇ = x^(a-b)",
      "Применять свойство (xᵃ)ᵇ = x^(a·b)",
      "Возводить произведение в степень: (xy)ⁿ = xⁿyⁿ",
      "Сравнивать значения степеней",
      "Определять знак степени"
    ]
  },
  {
    id: "04", title: "Одночлены",
    description: "Стандартный вид одночлена, умножение, возведение в степень",
    color: "violet", icon: "monomial",
    sections: ["§7 Одночлены"],
    skills: [
      "Приводить одночлен к стандартному виду",
      "Определять степень одночлена",
      "Умножать одночлены",
      "Возводить одночлен в степень",
      "Находить значение одночлена при заданных переменных",
      "Различать одночлен и многочлен"
    ]
  },
  {
    id: "05", title: "Сумма и разность многочленов",
    description: "Многочлен и его стандартный вид, сложение и вычитание",
    color: "pink", icon: "polynomial",
    sections: ["§8 Сумма и разность многочленов"],
    skills: [
      "Приводить многочлен к стандартному виду",
      "Определять степень многочлена",
      "Складывать многочлены",
      "Вычитать многочлены",
      "Раскрывать скобки в многочленах со знаком плюс/минус"
    ]
  },
  {
    id: "06", title: "Умножение многочленов и вынесение множителя",
    description: "Умножение одночлена/многочлена на многочлен, вынесение общего множителя, группировка",
    color: "rose", icon: "multiply",
    sections: ["§9 Произведение одночлена и многочлена", "§10 Произведение многочленов"],
    skills: [
      "Умножать одночлен на многочлен",
      "Умножать многочлен на многочлен",
      "Выносить общий множитель за скобки",
      "Раскладывать многочлен на множители способом группировки",
      "Решать уравнения через разложение на множители (axⁿ + bxᵐ = 0)"
    ]
  },
  {
    id: "07", title: "Квадрат суммы и квадрат разности",
    description: "(a+b)², (a-b)² — развёртка и свёртка",
    color: "orange", icon: "square",
    sections: ["§11 Квадрат суммы и квадрат разности"],
    skills: [
      "Применять формулу (a + b)² = a² + 2ab + b² (развёртка)",
      "Применять формулу (a - b)² = a² - 2ab + b² (развёртка)",
      "Свёртывать трёхчлен в квадрат суммы или разности",
      "Распознавать «полный квадрат» в выражении",
      "Применять ФСУ к числам (51², 99², 102² и т.п.)"
    ]
  },
  {
    id: "08", title: "Разность квадратов и кубы",
    description: "a²-b², a³±b³ — формулы сокращённого умножения",
    color: "amber", icon: "cube",
    sections: ["§12 Разность квадратов, сумма и разность кубов"],
    skills: [
      "Применять формулу a² - b² = (a - b)(a + b) (развёртка)",
      "Раскладывать разность квадратов на множители",
      "Применять формулу куба суммы (a + b)³",
      "Применять формулу куба разности (a - b)³",
      "Раскладывать сумму кубов: a³ + b³ = (a + b)(a² - ab + b²)",
      "Раскладывать разность кубов: a³ - b³ = (a - b)(a² + ab + b²)"
    ]
  },
  {
    id: "09", title: "Применение ФСУ",
    description: "Преобразование целых выражений, многоступенчатое разложение на множители",
    color: "yellow", icon: "formula",
    sections: ["§13 Преобразование целых выражений"],
    skills: [
      "Преобразовывать целое выражение в многочлен",
      "Применять ФСУ для упрощения сложных выражений",
      "Раскладывать на множители комбинацией способов (вынесение + ФСУ + группировка)",
      "Раскладывать выражения вида x⁴ - 16 (двукратное применение ФСУ)",
      "Доказывать тождества с применением ФСУ"
    ]
  },
  {
    id: "10", title: "Линейная функция",
    description: "y=kx+b: график, чтение коэффициентов, точки пересечения",
    color: "green", icon: "graph",
    sections: ["§5 Линейная функция"],
    skills: [
      "Находить значение функции y = kx + b по аргументу x",
      "Находить аргумент x по заданному значению y",
      "Определять, проходит ли график через данную точку",
      "Находить точки пересечения графика с осями координат",
      "Находить k и b по двум точкам, через которые проходит прямая",
      "Находить точку пересечения двух прямых",
      "Определять параллельность прямых по коэффициентам",
      "Строить график линейной функции по двум точкам"
    ]
  },
  {
    id: "11", title: "Системы линейных уравнений",
    description: "Линейные уравнения с двумя переменными, методы подстановки и сложения",
    color: "teal", icon: "system",
    sections: ["§14 Линейные уравнения с двумя переменными", "§15 Решение систем линейных уравнений"],
    skills: [
      "Решать систему методом подстановки",
      "Решать систему методом сложения",
      "Определять количество решений системы (одно / нет / бесконечно)",
      "Решать систему графическим способом",
      "Составлять систему по условию текстовой задачи",
      "Решать задачи на две неизвестные через систему (билеты, сплавы, движение)"
    ]
  }
];

// === PRACTICE FILTER ===
// Drop problems whose instruction asks for verbal answer (theory) instead of computation.

const THEORY_INSTR_RE = /^(сформулируйте|дайте\s+определение|расскажите|объясните,?\s+почему|обсудите|обоснуйте,?\s+почему|опишите|перечислите\s+(свойства|правила|типы)|что\s+называется|что\s+такое|какие\s+\w+\s+называются|какие\s+свойства|приведите\s+пример(ы)?\s+(числов|рациональн|выражени|двойн|многочлен|одночлен)|подумайте|расскажи|сравните\s+(способ|решен|вычислен)|проверьте\s+друг|распределите|кто\s+будет|для\s+работы\s+в\s+парах|(\(|^)для\s+работы\s+в\s+парах|прочитайте\s+(выражение|неравенство|многочлен)\s*[:.]?\s*$|обобщите|сделайте\s+вывод)/i;

const ACTION_VERBS = [
  "решите", "найдите", "вычислите", "упростите", "разложите", "преобразуйте",
  "докажите", "постройте", "запишите", "представьте", "выполните", "выясните",
  "сравните", "выпишите", "подберите", "задайте", "умножьте", "возведите",
  "сократите", "выразите", "установите", "отметьте", "изобразите", "укажите",
  "при\\s+как", "имеет\\s+ли", "является\\s+ли"
];
const ACTION_RE = new RegExp("^(" + ACTION_VERBS.join("|") + ")", "i");

// SUBITEM-LEVEL filter:
// Keep the subitem if it has a math expression OR a real word problem (with question + length).
// Drop verbal fragments like "x — отрицательное число" or "сумму чисел a и b" — these are
// "translate verbal to algebraic notation" exercises, not practice we want.
const QUESTION_WORDS_RE = /(сколько|какова|какое|каково|какой|какая|какие|найди|вычисли|реши|постро|докажи|на\s+сколько|во\s+сколько|какова\s+стоимость|какое\s+число|чему\s+равно|равны\s+ли)/i;

// Definition-style questions disguised as task text (no expression, just words asking what something means)
const DEFINITION_Q_RE = /(какие\s+(?:из\s+)?\w+\s+называ(?:ются|ют))|(что\s+называ(?:ется|ют))|(что\s+такое)|(что\s+означает\s+выражение)|(дайте\s+определение)|(сформулируйте)|(приведите\s+пример(?:ы)?)|(в\s+чём\s+(?:состоит|заключается))/i;

function isPracticeSubitem(s) {
  const e = (s.expression || "").trim();
  if (e) {
    // expression is non-empty but just a verbal description fragment with no math operators
    // (rare, since expression is usually math; but happens when OCR put a phrase there)
    const hasMathSymbols = /[+\-*/=^()0-9xyzabcmnpqrt]/i.test(e);
    if (!hasMathSymbols) return false;
    // Even with expression, drop if it's basically a definition question
    if (DEFINITION_Q_RE.test(e) && !/[+\-*/=^]/.test(e)) return false;
    return true;
  }

  const t = (s.text || "").trim();
  if (!t) return false;

  // Theory disguised as task text (definition questions, formulation requests)
  if (DEFINITION_Q_RE.test(t)) return false;
  if (THEORY_INSTR_RE.test(t)) return false;

  // Real word problem: substantial AND contains a computation question
  const hasQuestion = /\?/.test(t) || QUESTION_WORDS_RE.test(t);
  if (t.length >= 60 && hasQuestion) return true;

  return false;
}

function isPracticeProblem(p) {
  const instr = (p.instruction || "").trim();

  // Hard theory? skip
  if (instr && THEORY_INSTR_RE.test(instr)) return false;

  // Must have at least one practice subitem
  const goodSubs = (p.subitems || []).filter(isPracticeSubitem);
  return goodSubs.length > 0;
}

const curated = JSON.parse(fs.readFileSync(SRC, "utf8"));
const allProblems = curated.problems;

// --- Helpers ---

// Combine subitems into a single expression string for a task
function combineSubitemsToTasks(problem) {
  const subs = problem.subitems || [];
  if (subs.length === 0) {
    return [{
      expression: problem.instruction || "",
      text: null,
      label: null
    }];
  }
  return subs.map(s => ({
    expression: s.expression || null,
    text: s.text || null,
    label: s.label || null
  }));
}

// Normalize instruction for grouping: lowercase, strip punct, take first 3 significant words.
// Maps "Решите уравнение, преобразовав ..." and "Решите уравнение" to the same group.
const SHORT_VERBS = new Set(["укажите", "запишите", "сравните", "вычислите", "выполните",
                             "решите", "докажите", "упростите", "разложите", "представьте",
                             "найдите", "постройте", "задайте", "выясните", "при"]);
function normInstr(s) {
  if (!s) return "(без инструкции)";
  const words = String(s)
    .toLowerCase()
    .replace(/[.,!?:;()«»"'—–-]/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .split(" ")
    .filter(w => w.length > 0);
  // take 3 words (or 4 if first is a short verb that needs disambiguation)
  return words.slice(0, 3).join(" ");
}

// Canonicalize the displayed instruction: pick the SHORTEST original among all in the group
// (avoids long, idiosyncratic variants becoming the group label).
function canonicalInstr(items) {
  const seen = new Map();
  for (const p of items) {
    const i = (p.instruction || "(без инструкции)").trim();
    seen.set(i, (seen.get(i) || 0) + 1);
  }
  // shortest, ties broken by frequency
  return [...seen.entries()].sort((a, b) => {
    const la = a[0].length, lb = b[0].length;
    if (la !== lb) return la - lb;
    return b[1] - a[1];
  })[0][0];
}

// Group problems within a topic by their normalized instruction
function groupByInstruction(problems) {
  const groups = new Map();
  for (const p of problems) {
    const key = normInstr(p.instruction);
    if (!groups.has(key)) {
      groups.set(key, { items: [] });
    }
    groups.get(key).items.push(p);
  }
  // pick canonical instruction per group (shortest, ties → most frequent)
  for (const g of groups.values()) g.instruction = canonicalInstr(g.items);
  // sort by group size desc
  return [...groups.values()].sort((a, b) => b.items.length - a.items.length);
}

// Build a single topic JSON in OGE format
function buildTopic(topic) {
  // Filter problems for this topic by sections + drop pure theory
  const sectionSet = new Set(topic.sections);
  const probsAll = allProblems.filter(p => sectionSet.has(p.section));
  const probs = probsAll.filter(isPracticeProblem);
  const droppedTheory = probsAll.length - probs.length;

  // Group by instruction
  const groups = groupByInstruction(probs);

  // Build zadaniya
  const zadaniya = [];
  let zadNum = 1;
  for (const g of groups) {
    const tasks = [];
    let taskId = 1;
    for (const p of g.items) {
      const subs = combineSubitemsToTasks(p).filter(isPracticeSubitem);
      for (const s of subs) {
        const exprParts = [];
        if (s.expression) exprParts.push(s.expression);
        if (s.text && s.text !== s.expression) exprParts.push(s.text);
        const expression = exprParts.join(" ").trim();
        tasks.push({
          id: taskId++,
          expression: expression || null,
          answer: null,
          status: "draft",
          src: {
            number: p.number,
            label: s.label,
            page: p.page,
            has_figure: p.has_figure || false
          }
        });
      }
    }
    if (tasks.length === 0) continue;  // skip empty zadanie
    zadaniya.push({
      number: zadNum++,
      instruction: g.instruction,
      tasks
    });
  }

  return {
    topic_id: topic.id,
    meta: {
      title: topic.title,
      description: topic.description,
      color: topic.color,
      icon: topic.icon,
      grade: 7,
      subject: "algebra",
      skills: topic.skills,
      stats: {
        problems_in_section: probsAll.length,
        practice_kept: probs.length,
        theory_dropped: droppedTheory
      }
    },
    exported_at: new Date().toISOString(),
    source: "Макарычев Ю.Н. — Алгебра 7 класс, 2023",
    blocks: [
      {
        number: 1,
        title: "Учебник Макарычев",
        zadaniya
      }
    ]
  };
}

// --- Main ---

const stats = [];
for (const topic of TOPICS) {
  const data = buildTopic(topic);
  const taskCount = data.blocks[0].zadaniya.reduce((s, z) => s + z.tasks.length, 0);
  const probCount = data.blocks[0].zadaniya.reduce((s, z) => s + z.tasks.length, 0);
  const filename = `topic_${topic.id}.json`;
  const filePath = path.join(OUT_DIR, filename);
  fs.writeFileSync(filePath, JSON.stringify(data, null, 2));
  stats.push({
    id: topic.id,
    title: topic.title,
    zadaniya: data.blocks[0].zadaniya.length,
    tasks: taskCount,
    file: filename
  });
}

console.log(`=== Generated ${TOPICS.length} topic files in ${OUT_DIR} ===\n`);
console.log("ID  Topic                                        Заданий  Задач  File");
console.log("--- -------------------------------------------- -------- -----  --------------");
let totalTasks = 0, totalZad = 0;
for (const s of stats) {
  console.log(
    s.id.padEnd(3) + " " +
    s.title.padEnd(45) + "  " +
    String(s.zadaniya).padStart(5) + "    " +
    String(s.tasks).padStart(5) + "  " +
    s.file
  );
  totalTasks += s.tasks;
  totalZad += s.zadaniya;
}
console.log("--- -------------------------------------------- -------- -----  --------------");
console.log(`TOTAL                                                   ${String(totalZad).padStart(5)}    ${String(totalTasks).padStart(5)}`);
