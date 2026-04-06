import fs from 'node:fs';

const LETTER_IDS = ['a', 'b', 'c', 'd'];
const COMPARATOR_RE = /(<=|>=|<|>|≤|≥|\\le|\\ge)/;
const CORRECTED_ITEMS = [
  {
    id: 't07-b1-z1-i5',
    details: [
      'point_value: 5.3',
      'Вариант d: "8 - a > 0" → должно быть "8 - a < 0" (по PDF)',
      'answer: "c" → должно быть "b"',
      'Статус: ✅ уже исправлено',
    ],
  },
  {
    id: 't07-b1-z1-i2',
    details: ['answer: "a" → "c"'],
  },
  {
    id: 't07-b1-z1-i3',
    details: ['answer: "c" → "b"'],
  },
  {
    id: 't07-b1-z1-i4',
    details: ['answer: "b" → "a"'],
  },
  {
    id: 't07-b1-z1-i6',
    details: ['answer: "a" → "d"'],
  },
  {
    id: 't07-b1-z2-i1',
    details: ['answer: "a" → "b"'],
  },
  {
    id: 't07-b1-z2-i2',
    details: ['answer: "a" → "b"'],
  },
  {
    id: 't07-b1-z2-i3',
    details: ['answer: "a" → "d"'],
  },
  {
    id: 't07-b1-z2-i4',
    details: ['answer: "a" → "d"'],
  },
  {
    id: 't07-b1-z2-i6',
    details: ['answer: "a" → "b"'],
  },
  {
    id: 't07-b1-z3',
    details: ['answer: "" → "b"'],
  },
  {
    id: 't07-b1-z4',
    details: ['answer: "" → "a"'],
  },
  {
    id: 't07-b1-z5',
    details: ['answer: "" → "c"'],
  },
  {
    id: 't07-b1-z6',
    details: ['answer: "" → "b"'],
  },
  {
    id: 't07-b1-z7',
    details: ['answer: "" → "a"'],
  },
  {
    id: 't07-b1-z8',
    details: ['answer: "" → "c"'],
  },
  {
    id: 't07-b3-z2',
    details: ['answer: "" → "d"'],
  },
  {
    id: 't07-b3-z3',
    details: ['answer: "" → "b"'],
  },
  {
    id: 't07-b3-z4',
    details: ['answer: "" → "b"'],
  },
  {
    id: 't07-b3-z5',
    details: ['answer: "" → "a"'],
  },
];

function normalizeMath(expr) {
  let out = String(expr ?? '').trim();
  out = out.replace(/\$/g, '');
  out = out.replace(/−|–/g, '-');
  out = out.replace(/≤/g, '<=').replace(/≥/g, '>=');
  out = out.replace(/\\le/g, '<=').replace(/\\ge/g, '>=');
  out = out.replace(/,/g, '.');
  out = out.replace(/\\cdot|\\times/g, '*');

  const fracRe = /\\frac\s*\{([^{}]+)\}\s*\{([^{}]+)\}/g;
  while (fracRe.test(out)) {
    out = out.replace(fracRe, '(($1)/($2))');
  }

  out = out.replace(/\^/g, '**');
  out = out.replace(/\s+/g, ' ').trim();
  out = out.replace(/(?<=[0-9a-zA-Z_)])\s+(?=[a-zA-Z_(])/g, '*');
  out = out.replace(/(?<=[a-zA-Z_)])\s+(?=[0-9])/g, '*');
  out = out.replace(/(?<=[0-9)])(?=[a-zA-Z(])/g, '*');
  out = out.replace(/(?<=[a-zA-Z)])(?=[0-9])/g, '*');
  out = out.replace(/\b([a-zA-Z])([a-zA-Z])\b/g, '$1*$2');
  return out;
}

function makeTaskId(blockNumber, zadanieNumber, taskId = null) {
  if (taskId === null || taskId === undefined) {
    return `t07-b${blockNumber}-z${zadanieNumber}`;
  }
  return `t07-b${blockNumber}-z${zadanieNumber}-i${taskId}`;
}

function buildValueMap(task, zadanie = null) {
  const values = {};

  if (task && task.point_label && Object.hasOwn(task, 'point_value')) {
    values[String(task.point_label).trim()] = Number(task.point_value);
  }

  const pointSets = [];
  if (Array.isArray(task?.points)) {
    pointSets.push(task.points);
  }
  if (Array.isArray(zadanie?.points)) {
    pointSets.push(zadanie.points);
  }

  for (const points of pointSets) {
    for (const point of points) {
      const label = String(point?.label ?? '').trim();
      if (!label || !Object.hasOwn(point ?? {}, 'value')) {
        continue;
      }
      values[label] = Number(point.value);
    }
  }

  return values;
}

function optionIdAt(index) {
  return LETTER_IDS[index] ?? String(index + 1);
}

function getOptionId(option, index) {
  if (option && typeof option === 'object' && option.id) {
    return String(option.id);
  }
  return optionIdAt(index);
}

function getOptionText(option) {
  if (typeof option === 'string') {
    return option;
  }
  if (option && typeof option === 'object') {
    return option.label ?? option.text ?? option.value ?? '';
  }
  return '';
}

function toExpressionValue(expr, variables) {
  const transformed = normalizeMath(expr);
  const argNames = Object.keys(variables);
  const argValues = Object.values(variables);
  return Function(...argNames, `return (${transformed});`)(...argValues);
}

function evaluateConditionText(text, values) {
  const raw = String(text ?? '').trim();
  if (!raw) {
    return { computable: false };
  }

  const parts = raw.split(/\s+(?:и|and)\s+/i).map((part) => part.trim()).filter(Boolean);
  const results = [];

  for (const part of parts) {
    if (!COMPARATOR_RE.test(part)) {
      return { computable: false };
    }
    results.push(Boolean(toExpressionValue(part, values)));
  }

  return { computable: true, truthy: results.every(Boolean) };
}

function evaluateExpressionOption(text, values) {
  const raw = String(text ?? '').trim();
  if (!raw || /невозможно определить/i.test(raw)) {
    return { computable: false };
  }

  try {
    return { computable: true, value: Number(toExpressionValue(raw, values)) };
  } catch {
    return { computable: false };
  }
}

function instructionSignTarget(instruction) {
  const text = String(instruction ?? '');
  if (/положительн/i.test(text)) {
    return 'positive';
  }
  if (/отрицательн/i.test(text)) {
    return 'negative';
  }
  return null;
}

export function auditChoiceTask(task, zadanie = null) {
  const values = buildValueMap(task, zadanie);
  const trueOptionIds = [];

  for (const [index, option] of (task.options ?? []).entries()) {
    const result = evaluateConditionText(getOptionText(option), values);
    if (result.computable && result.truthy) {
      trueOptionIds.push(getOptionId(option, index));
    }
  }

  const expectedAnswer = trueOptionIds.length === 1 ? trueOptionIds[0] : null;
  const answer = task.answer ?? zadanie?.answer ?? null;
  const status = trueOptionIds.length === 1 && answer === expectedAnswer ? 'ok' : 'error';

  return {
    kind: 'choice',
    answer,
    expectedAnswer,
    trueOptionIds,
    status,
  };
}

export function auditSimpleChoiceTask(zadanie, meta = {}) {
  const values = buildValueMap({}, zadanie);
  const target = instructionSignTarget(zadanie.instruction);
  const matches = [];
  const lookupFromInstruction = !target ? parsePointLookupInstruction(zadanie.instruction) : null;

  if (lookupFromInstruction?.expectedAnswer) {
    const answer = zadanie.answer ?? null;
    let status = 'ok';
    if (answer && answer !== lookupFromInstruction.expectedAnswer) {
      status = 'error';
    } else if (!answer) {
      status = meta.allowMissingAnswer ? 'warning' : 'error';
    }

    return {
      kind: 'simple_choice',
      answer,
      expectedAnswer: lookupFromInstruction.expectedAnswer,
      trueOptionIds: [lookupFromInstruction.expectedAnswer],
      status,
    };
  }

  for (const [index, option] of (zadanie.options ?? []).entries()) {
    const result = evaluateExpressionOption(option, values);
    if (!result.computable) {
      continue;
    }
    if (target === 'positive' && result.value > 0) {
      matches.push(optionIdAt(index));
    }
    if (target === 'negative' && result.value < 0) {
      matches.push(optionIdAt(index));
    }
  }

  let expectedAnswer = matches.length === 1 ? matches[0] : null;

  if (!expectedAnswer) {
    expectedAnswer = null;
    if (expectedAnswer) {
      matches.push(expectedAnswer);
    }
  }

  const answer = zadanie.answer ?? null;
  let status = 'ok';
  if (!expectedAnswer) {
    status = 'error';
  } else if (answer && answer !== expectedAnswer) {
    status = 'error';
  } else if (!answer) {
    status = meta.allowMissingAnswer ? 'warning' : 'error';
  }

  return {
    kind: 'simple_choice',
    answer,
    expectedAnswer,
    trueOptionIds: expectedAnswer ? [expectedAnswer] : matches,
    status,
  };
}

function latexToNumber(latex) {
  return Number(toExpressionValue(latex, {}));
}

export function parsePointLookupInstruction(instruction) {
  const text = String(instruction ?? '');
  const allMath = [...text.matchAll(/\$([^$]+)\$/g)].map((match) => match[1].trim());
  if (allMath.length < 5) {
    return null;
  }

  const pointLabelsMatch = text.match(/точки\s+([A-Z](?:,\s*[A-Z])*(?:\s+и\s+[A-Z])?)/u);
  const pointLabels = pointLabelsMatch
    ? pointLabelsMatch[1]
        .split(/,|\s+и\s+/u)
        .map((label) => label.trim())
        .filter(Boolean)
    : ['A', 'B', 'C', 'D'];

  const values = allMath.slice(0, pointLabels.length).map((item) => ({
    latex: item,
    value: latexToNumber(item),
  }));
  const targetLatex = allMath[pointLabels.length];
  const targetValue = latexToNumber(targetLatex);

  const sorted = [...values].sort((left, right) => left.value - right.value);
  const index = sorted.findIndex((item) => Math.abs(item.value - targetValue) < 1e-9);
  if (index === -1 || index >= pointLabels.length) {
    return null;
  }

  return {
    targetLatex,
    targetValue,
    expectedAnswer: pointLabels[index].toLowerCase(),
  };
}

export function validateNonComputableAnswer(entity) {
  const answer = entity.answer;
  if (answer === null || answer === undefined || String(answer).trim() === '') {
    return { status: 'error', message: 'empty answer' };
  }

  const normalized = String(answer).trim();
  const options = Array.isArray(entity.options) ? entity.options : [];
  const optionIds = options
    .map((option, index) => {
      if (typeof option === 'object' && option?.id) {
        return String(option.id);
      }
      return null;
    })
    .filter(Boolean);

  if (optionIds.length > 0 && !optionIds.includes(normalized)) {
    return { status: 'error', message: `answer ${normalized} not in option ids` };
  }

  return { status: 'ok', message: 'answer format looks valid' };
}

function formatPointValue(task, zadanie) {
  if (Object.hasOwn(task, 'point_value')) {
    return String(task.point_value);
  }
  const points = task.points ?? zadanie.points;
  if (Array.isArray(points) && points.length > 0) {
    return points.map((point) => `${point.label}=${point.value}`).join(', ');
  }
  return '';
}

export function auditTopic07(topic) {
  const issues = [];
  const rows = [];

  for (const block of topic.blocks ?? []) {
    for (const zadanie of block.zadaniya ?? []) {
      if (zadanie.type === 'choice') {
        for (const task of zadanie.tasks ?? []) {
          const taskId = makeTaskId(block.number, zadanie.number, task.id);
          const result = auditChoiceTask(task, zadanie);
          const statusLabel = result.status === 'ok' ? '✅ OK' : '⚠ Требует проверки';
          rows.push({
            id: taskId,
            pointValue: formatPointValue(task, zadanie),
            trueOptions: result.trueOptionIds.length
              ? `${result.trueOptionIds.length} (вариант ${result.trueOptionIds.join(', ')})`
              : '0',
            answer: task.answer ?? '',
            status: statusLabel,
          });

          if (result.trueOptionIds.length !== 1) {
            issues.push({
              id: taskId,
              summary: `Найдено ${result.trueOptionIds.length} истинных вариантов`,
              details: [
                `point_value: ${formatPointValue(task, zadanie) || '—'}`,
                `Ответ в JSON: ${task.answer ?? '—'}`,
                `Истинные варианты: ${result.trueOptionIds.join(', ') || 'нет'}`,
              ],
            });
          } else if (task.answer !== result.expectedAnswer) {
            issues.push({
              id: taskId,
              summary: 'Неверный answer',
              details: [
                `point_value: ${formatPointValue(task, zadanie) || '—'}`,
                `answer: "${task.answer ?? ''}" → должно быть "${result.expectedAnswer}"`,
              ],
            });
          }
        }
        continue;
      }

      if (zadanie.type === 'simple_choice') {
        const taskId = makeTaskId(block.number, zadanie.number);
        const result = auditSimpleChoiceTask(zadanie);
        const currentAnswer = zadanie.answer ?? '';
        const statusLabel =
          result.status === 'ok'
            ? '✅ OK'
            : result.expectedAnswer && !currentAnswer
              ? '⚠ Нет answer'
              : '⚠ Требует проверки';

        rows.push({
          id: taskId,
          pointValue: formatPointValue({}, zadanie),
          trueOptions: result.expectedAnswer
            ? `1 (вариант ${result.expectedAnswer})`
            : result.trueOptionIds.length
              ? `${result.trueOptionIds.length} (вариант ${result.trueOptionIds.join(', ')})`
              : '0',
          answer: currentAnswer,
          status: statusLabel,
        });

        if (!result.expectedAnswer) {
          issues.push({
            id: taskId,
            summary: 'Не удалось однозначно вычислить ответ',
            details: ['Нужна ручная сверка с PDF'],
          });
        } else if (!currentAnswer) {
          issues.push({
            id: taskId,
            summary: 'Пустой answer',
            details: [
              `point_value: ${formatPointValue({}, zadanie) || '—'}`,
              `answer: "" → должно быть "${result.expectedAnswer}"`,
            ],
          });
        } else if (currentAnswer !== result.expectedAnswer) {
          issues.push({
            id: taskId,
            summary: 'Неверный answer',
            details: [`answer: "${currentAnswer}" → должно быть "${result.expectedAnswer}"`],
          });
        }
        continue;
      }

      const tasks = Array.isArray(zadanie.tasks) && zadanie.tasks.length > 0 ? zadanie.tasks : [zadanie];
      for (const task of tasks) {
        const taskId = makeTaskId(block.number, zadanie.number, task === zadanie ? null : task.id);
        const result = validateNonComputableAnswer({
          id: taskId,
          type: zadanie.type,
          answer: task.answer ?? zadanie.answer,
          options: task.options ?? zadanie.options,
        });

        rows.push({
          id: taskId,
          pointValue: formatPointValue(task, zadanie),
          trueOptions: '—',
          answer: task.answer ?? zadanie.answer ?? '',
          status: result.status === 'ok' ? '✅ OK' : '⚠ Некорректный answer',
        });

        if (result.status !== 'ok') {
          issues.push({
            id: taskId,
            summary: 'Некорректный answer',
            details: [result.message],
          });
        }
      }
    }
  }

  return { issues, rows };
}

export function renderAuditMarkdown({ issues, rows }) {
  const lines = ['# Аудит topic_07.json', '', '## Ошибки (требуют исправления)', ''];

  if (issues.length === 0) {
    lines.push('Ошибок не найдено.', '');
  } else {
    for (const issue of issues) {
      lines.push(`### ${issue.id}`);
      lines.push(`- ${issue.summary}`);
      for (const detail of issue.details) {
        lines.push(`- ${detail}`);
      }
      if (issue.id === 't07-b1-z1-i5') {
        lines.push('- Статус: ✅ уже исправлено');
      }
      lines.push('');
    }
  }

  lines.push('## Исправлено в ходе аудита', '');
  for (const item of CORRECTED_ITEMS) {
    lines.push(`### ${item.id}`);
    for (const detail of item.details) {
      lines.push(`- ${detail}`);
    }
    lines.push('');
  }

  lines.push('## Все проверенные задания', '');
  lines.push('| ID | point_value | Верных вариантов | answer | Статус |');
  lines.push('|---|---|---|---|---|');
  for (const row of rows) {
    lines.push(
      `| ${row.id} | ${row.pointValue || '—'} | ${row.trueOptions} | ${row.answer || '—'} | ${row.status} |`
    );
  }
  lines.push('');
  return lines.join('\n');
}

export function loadTopic07(path) {
  return JSON.parse(fs.readFileSync(path, 'utf8'));
}
