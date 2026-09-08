import test from 'node:test';import assert from 'node:assert/strict';
import {filterTasks,representative,restoreSelection} from '../../preview/algebra-bank/model.mjs';
const catalog=[{id:'equation',title:'Уравнения',group:'g1'},{id:'power',title:'Степени',group:'g2'},{id:'fraction',title:'Дроби',group:'g3'}];
const tasks=[{id:'a',primary:'equation',skills:['equation','power','fraction'],template:'mixed',level:5,grades:[7,8],prompt:'Решите'},{id:'b',primary:'equation',skills:['equation'],template:'simple',level:1,grades:[6,7,8],prompt:'Решите'},{id:'c',primary:'equation',skills:['equation','power','fraction'],template:'mixed',level:5,grades:[7,8],prompt:'Решите'}];
test('combination requires ALL tags',()=>assert.deepEqual(filterTasks(tasks,{skills:['power','fraction']},catalog).map(t=>t.id),['a','c']));
test('numeric levels and grades apply together',()=>assert.equal(filterTasks(tasks,{level:'5',grade:'6'},catalog).length,0));
test('search accepts Russian skill names',()=>assert.equal(filterTasks(tasks,{search:'степени'},catalog).length,2));
test('different types retain one actual task per template',()=>assert.deepEqual(representative(tasks).map(t=>t.id),['a','b']));
test('selection survives corrupt storage and drops obsolete IDs',()=>{assert.deepEqual(restoreSelection('{',new Set(['a'])),[]);assert.deepEqual(restoreSelection('["a","a","old"]',new Set(['a'])),['a']);});
