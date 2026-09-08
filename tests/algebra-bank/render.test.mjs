import test from 'node:test';import assert from 'node:assert/strict';import fs from 'node:fs';import katex from '/home/dev/fipi-sync/node_modules/katex/dist/katex.mjs';
const d=JSON.parse(fs.readFileSync(new URL('../../preview/algebra-bank/bank.json',import.meta.url)));
test('all task conditions and answers render in KaTeX',()=>{const bad=[];for(const t of d.tasks)for(const key of ['math','answer'])if(t[key])try{katex.renderToString(t[key],{throwOnError:true,strict:'ignore'});}catch(e){bad.push(t.id+':'+key);}assert.deepEqual(bad,[]);});
test('quadratic word problems are not presented as grade six',()=>{for(const t of d.tasks.filter(t=>t.primary==='A155'))assert.deepEqual(t.grades,[8]);});
