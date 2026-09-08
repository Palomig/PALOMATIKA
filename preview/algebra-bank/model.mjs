export function filterTasks(tasks,{group='',skills=[],level='',grade='',search='',mixed=false},catalog){
 const byId=new Map(catalog.map(s=>[s.id,s]));
 const q=search.trim().toLocaleLowerCase('ru');
 return tasks.filter(t=>(!group||byId.get(t.primary)?.group===group)&&(!grade||t.grades.includes(Number(grade)))&&(!level||t.level===Number(level))&&skills.every(s=>t.skills.includes(s))&&(!mixed||t.skills.length>1)&&(!q||(t.prompt+' '+t.id+' '+t.skills.map(s=>byId.get(s)?.title||'').join(' ')).toLocaleLowerCase('ru').includes(q)));
}
export function representative(tasks){const seen=new Set();return tasks.filter(t=>{if(seen.has(t.template))return false;seen.add(t.template);return true;});}
export function restoreSelection(value,ids){try{const d=JSON.parse(value);return Array.isArray(d)?[...new Set(d)].filter(id=>typeof id==='string'&&ids.has(id)):[];}catch{return [];}}
