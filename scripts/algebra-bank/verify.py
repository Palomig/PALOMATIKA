"""Independent symbolic verification of factory answers. Never shipped to browser."""
import sympy as S
x,y=S.symbols('x y', real=True)
LOC={'x':x,'y':y,'sqrt':S.sqrt,'Abs':S.Abs,'gcd':S.gcd,'lcm':S.lcm}
def expr(v): return S.sympify(str(v),locals=LOC)
def check(c):
 try:
  if c['kind']=='identity': return S.simplify(expr(c['lhs'])-expr(c['rhs']))==0
  if c['kind']=='equation':
   lhs,rhs=expr(c['lhs']),expr(c['rhs'])
   roots={expr(v) for v in c['roots']}; excluded={expr(v) for v in c.get('exclude',[])}
   actual=set(S.solve(lhs-rhs,x))-excluded
   return roots==actual and not roots&excluded and all(S.simplify((lhs-rhs).subs(x,v))==0 for v in roots)
  if c['kind']=='system':
   got=S.solve([expr(e) for e in c['equations']],(x,y))
   return got=={x:expr(c['x']),y:expr(c['y'])}
  if c['kind']=='truth': return bool(expr(c['value']))
  return False
 except Exception: return False
if __name__=='__main__':
 import json,sys,time
 tasks=json.load(open(sys.argv[1])); failures=[]; start=time.time()
 for i,t in enumerate(tasks):
  if not t['checks'] or not all(check(c) for c in t['checks']): failures.append(t['id'])
  if (i+1)%1000==0: print(f'Checked {i+1}/{len(tasks)}',flush=True)
 print(json.dumps({'checked':len(tasks),'failed':failures,'seconds':round(time.time()-start,1)}))
 sys.exit(bool(failures))
