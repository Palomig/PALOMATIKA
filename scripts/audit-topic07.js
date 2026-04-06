#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

import { auditTopic07, loadTopic07, renderAuditMarkdown } from './audit-topic07-lib.mjs';

const topicPath = path.resolve('storage/app/tasks/topic_07.json');
const outputPath = path.resolve('docs/audit-topic07-results.md');

const topic = loadTopic07(topicPath);
const report = auditTopic07(topic);
const markdown = renderAuditMarkdown(report);

fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, markdown, 'utf8');

console.log(`Saved audit report to ${outputPath}`);
console.log(`Issues: ${report.issues.length}`);
