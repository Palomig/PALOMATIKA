"""Own deterministic mathematics tasks; numerical variants retain their template ID."""
from pathlib import Path
import json,re,random,hashlib,math
import sympy as S
ROOT=Path(__file__).resolve().parents[2]
x,y,a,b,c=S.symbols('x y a b c',real=True)
LOC={'x':x,'y':y,'a':a,'b':b,'c':c,'sqrt':S.sqrt,'Abs':S.Abs}
def val(e): return S.sympify(str(e),locals=LOC)
def tex(e): return S.latex(val(e),mul_symbol='dot')
def raw(e): return S.latex(S.sympify(str(e),locals=LOC,evaluate=False),mul_symbol='dot')
def frac(a,b): return rf'\frac{{{a}}}{{{b}}}'
def sk(i): return f'A{i:03}'
source=(ROOT/'docs/plans/2026-09-08-algebra-6-8-bank-design.md').read_text()
cat=source.split('## Карта навыков')[1].split('## Образцы')[0]
groups=[];skills=[]
for title,body in re.findall(r'### (.*?)\n(.*?)(?=\n### |\Z)',cat,re.S):
 gid=f'g{len(groups)+1}'; groups.append({'id':gid,'title':title})
 for id,title in re.findall(r'- (A\d+): (.*?)\.\n',body):
  num=int(id[1:]); grades=[6,7,8] if num<=54 or num>=144 else ([7,8] if num<=88 or 129<=num<=143 else [8])
  if num in (64,67,88,136,137,155): grades=[8]
  if num in (152,154): grades=[7,8]
  skills.append({'id':id,'title':title,'group':gid,'grades':grades})
BY={s['id']:s for s in skills};tasks=[];seen=set();occ={}

def emit(key,primary,level,prompt,maths,answer,solution,checks,tags=(),manual=False,graph=None):
 sig=(prompt,maths)
 if sig in seen:return
 seen.add(sig); ids=list(dict.fromkeys([sk(primary)]+[sk(i) for i in tags])); occ[key]=occ.get(key,0)+1
 task={'id':key+'-'+hashlib.sha256((prompt+'|'+maths).encode()).hexdigest()[:10], 'template':key,'primary':sk(primary),'skills':ids,'level':level,'prompt':prompt,'math':maths,'answer':answer,'solution':solution,'manual':manual,'checks':checks}
 if graph:task['graph']=graph
 tasks.append(task)
def identity(lhs,rhs):return {'kind':'identity','lhs':str(lhs),'rhs':str(rhs)}
def ex(key,i,l,e,answer,tags=(),prompt='Упростите выражение.',hint='Выполните преобразования и приведите подобные слагаемые.',maths=None):
 emit(key,i,l,prompt,maths or raw(e),tex(answer),hint,[identity(e,answer)],tags)
def eq(key,i,l,left,right,roots,tags=(),maths=None,exclude=(),hint='Приведите уравнение к стандартному виду и проверьте корни подстановкой.'):
 emit(key,i,l,'Решите уравнение.',maths or raw(left)+'='+raw(right),r'x\in\{'+('; '.join(tex(r) for r in sorted(set(roots),key=lambda q:float(q))))+r'\}' if roots else r'\varnothing',hint,[{'kind':'equation','lhs':str(left),'rhs':str(right),'roots':list(map(str,roots)),'exclude':list(map(str,exclude))}],tags)
def q(key,i,l,prompt,maths,answer,checks,tags=(),hint='Выполните вычисления по условию.',manual=False,graph=None):
 emit(key,i,l,prompt,maths,answer,hint,checks,tags,manual,graph)
def number(key,i,l,e,answer,tags=(),maths=None,hint='Соблюдайте порядок действий.'):
 ex(key,i,l,e,answer,tags,'Вычислите.',hint,maths)
def system(key,i,l,e1,e2,r,s,tags=(),hint='Выразите одну переменную и подставьте во второе уравнение.',graph=None):
 q(key,i,l,'Решите систему уравнений.',r'\begin{cases}'+raw(e1)+r'=0\\'+raw(e2)+r'=0\end{cases}',rf'x={tex(r)},\quad y={tex(s)}',[{'kind':'system','equations':[str(e1),str(e2)],'x':str(r),'y':str(s)}],tags,hint,graph=graph)

for seed in range(75):
 R=random.Random(20260908+seed); A=R.randint(2,12); B=R.randint(2,11); C=R.randint(2,9); D=R.randint(2,8); N=R.randint(2,6); M=R.randint(2,5); r=R.choice([i for i in range(-12,13) if i]); s=r+R.randint(1,8); k=R.randint(2,9)
 # Numbers and computation
 n=A*B*10+C
 q('divisibility',1,1,f'Делится ли число {n} на 2? Объясните по последней цифре.','',r'\text{'+('Да' if n%2==0 else 'Нет')+'}',[identity(n%2,0 if n%2==0 else 1)],hint='На 2 делятся числа, оканчивающиеся чётной цифрой.',manual=True)
 f=S.factorint(A*B*C)
 q('prime-factorization',2,2,'Разложите число на простые множители.',str(A*B*C),r'\cdot'.join(str(t)+'^{'+str(p)+'}' for t,p in f.items()),[identity(math.prod(int(t)**p for t,p in f.items()),A*B*C)],hint='Последовательно делите на простые числа, начиная с 2.')
 q('gcd-lcm',3,2,'Найдите НОД и НОК чисел.',f'{A*C};\ {B*C}',rf'\text{{НОД}}={math.gcd(A*C,B*C)},\quad\text{{НОК}}={math.lcm(A*C,B*C)}',[identity(f'gcd({A*C},{B*C})',math.gcd(A*C,B*C)),identity(f'lcm({A*C},{B*C})',math.lcm(A*C,B*C))],tags=(2,),hint='Разложите числа на простые множители. Для НОД возьмите общую часть, для НОК — все множители с наибольшими показателями.')
 number('reduce-fraction',4,1,f'{A*C}/{B*C}',S.Rational(A,B),maths=frac(A*C,B*C),hint=f'Разделите числитель и знаменатель на общий делитель {math.gcd(A*C,B*C)}.')
 den=math.lcm(A,B)
 q('common-denominator',5,2,'Приведите дроби к наименьшему общему знаменателю.',frac(1,A)+r';\ '+frac(1,B),frac(den//A,den)+r';\ '+frac(den//B,den),[identity(S.Rational(1,A),S.Rational(den//A,den)),identity(S.Rational(1,B),S.Rational(den//B,den))],(3,),hint='Общий знаменатель — НОК исходных знаменателей.')
 sign='<' if S.Rational(A,B)<S.Rational(C,D) else ('>' if S.Rational(A,B)>S.Rational(C,D) else '=')
 q('compare-fractions',6,2,'Сравните дроби.',frac(A,B)+r'\quad\text{и}\quad'+frac(C,D),frac(A,B)+sign+frac(C,D),[{'kind':'truth','value':f'{A*D}{"==" if sign=="=" else sign}{C*B}'}],(5,),hint='При положительных знаменателях сравните перекрёстные произведения.')
 for op,idx,key,ans in [('+',7,'add-fractions',S.Rational(A,B)+S.Rational(C,D)),('-',7,'subtract-fractions',S.Rational(A,B)-S.Rational(C,D)),('*',8,'multiply-fractions',S.Rational(A,B)*S.Rational(C,D)),('/',9,'divide-fractions',S.Rational(A,B)/S.Rational(C,D))]:
  number(key,idx,2,f'({A}/{B}){op}({C}/{D})',ans,(4,5) if idx==7 else (4,),frac(A,B)+{'*':r'\cdot','/':':','+':'+','-':'-'}[op]+frac(C,D))
 number('mixed-numbers',10,3,f'({A}+1/{B})-({C}+1/{D})',A+S.Rational(1,B)-C-S.Rational(1,D),(7,4),rf'{A}\frac{{1}}{{{B}}}-{C}\frac{{1}}{{{D}}}',hint='Переведите смешанные числа в неправильные дроби, затем вычтите.')
 number('decimal-operation',11,2,f'({A}/10+{B}/100)*{C}',(S.Rational(A,10)+S.Rational(B,100))*C,maths=f'({A/10:g}+{B/100:g})\cdot {C}')
 q('decimal-to-fraction',12,1,'Запишите десятичную дробь обыкновенной и сократите.',f'{(A*10+B)/100:g}',tex(S.Rational(A*10+B,100)),[identity(S.Rational(A*10+B,100),S.Rational(A*10+B,100))],(4,),hint='Запишите число сотых в числителе, 100 в знаменателе, затем сократите.')
 q('negative-order',13,1,'Расположите числа по возрастанию.',f'{-A};\ {-B-20};\ {C}',f'{-B-20}<{ -A}<{C}',[{'kind':'truth','value':f'{-B-20} < {-A} < {C}'}],hint='На числовой прямой меньшее число находится левее.')
 number('signed-add',14,1,f'-{A*C}+{B}',-A*C+B)
 number('signed-subtract',15,2,f'-{A}-(-{B*C})',-A+B*C)
 number('signed-product',16,2,f'(-{A})*(-{B})/(-{C})',-S.Rational(A*B,C))
 number('absolute-value',17,2,f'Abs({r}-{A})+Abs(-{B})',abs(r-A)+B,(14,15))
 number('order-numeric',18,3,f'({A}-{B})*({C}+{D})/{N}',S.Rational((A-B)*(C+D),N),(16,9))
 # Ratios and percents
 q('ratio',19,1,f'В группе {A*C} учеников изучают английский, {B*C} — немецкий. Запишите отношение этих количеств в наименьших целых числах.','',f'{A//math.gcd(A,B)}:{B//math.gcd(A,B)}',[identity(S.Rational(A*C,B*C),S.Rational(A//math.gcd(A,B),B//math.gcd(A,B)))],(4,),hint='Разделите оба числа отношения на их НОД.')
 eq('proportion',20,2,f'x/{A}',f'{B}/{C}',[S.Rational(A*B,C)],(52,))
 q('direct-proportion',21,2,f'{A} одинаковых тетрадей стоят {A*B} рублей. Сколько стоят {C} таких тетрадей?','',str(B*C),[identity(f'{A*B}/{A}*{C}',B*C)],(146,),hint=f'Одна тетрадь стоит {B} рублей. Умножьте цену на {C}.')
 q('inverse-proportion',22,3,f'{A} одинаковых станков выполняют заказ за {B*C} часов. За сколько часов выполнят тот же заказ {B} таких станков при одинаковой производительности?','',str(A*C),[identity(f'{A}*{B*C}/{B}',A*C)],(151,),hint='Произведение числа станков на время постоянно.')
 number('part-of-number',23,1,f'{A*B}/{B}*{C}',A*C,maths=rf'\frac{{{C}}}{{{B}}}\text{{ от }}{A*B}')
 q('whole-from-part',24,2,f'{C}/{B} числа составляют {A*C}. Найдите число.','',str(A*B),[identity(f'{A*C}/({C}/{B})',A*B)],(9,),hint='Разделите известную часть на соответствующую дробь.')
 p=N*5;whole=A*100
 q('percent-of',25,2,f'Найдите {p}% от {whole}.','',str(A*p),[identity(f'{whole}*{p}/100',A*p)],(23,),hint='Один процент равен одной сотой числа.')
 q('whole-from-percent',26,2,f'{p}% числа равны {A*p}. Найдите число.','',str(whole),[identity(f'{A*p}*100/{p}',whole)],(24,),hint='Разделите часть на процентную долю.')
 q('percent-ratio',27,2,f'Сколько процентов число {A*p} составляет от {whole}?','',str(p)+r'\%',[identity(f'{A*p}/{whole}*100',p)],(19,),hint='Разделите часть на целое и умножьте на 100%.')
 q('price-decrease',28,3,f'Товар стоил {whole} рублей. Его цену снизили на {p}%. Найдите новую цену.','',str(A*(100-p)),[identity(f'{whole}*(1-{p}/100)',A*(100-p))],(147,25,),hint=f'Новая цена составляет {100-p}% прежней.')
 q('successive-percents',29,4,f'Цена {whole} рублей сначала выросла на {p}%, затем снизилась на {M*5}%. Найдите итоговую цену.','',tex(S.Rational(whole*(100+p)*(100-M*5),10000)),[identity(f'{whole}*(1+{p}/100)*(1-{M*5}/100)',S.Rational(whole*(100+p)*(100-M*5),10000))],(28,147,8),hint='Каждый процент изменения применяется к цене после предыдущего шага.')
 q('ratio-parts',30,3,f'Разделите {C*(A+B)} в отношении {A}:{B}.','',f'{A*C};\ {B*C}',[identity(A*C+B*C,C*(A+B)),identity(S.Rational(A*C,B*C),S.Rational(A,B))],(145,19),hint=f'Всего {A+B} частей, одна часть равна {C}.')
 # Expressions and brackets
 number('substitute',31,1,f'{A}*({r})+{B}',A*r+B,maths=rf'{A}x+{B},\quad x={r}')
 q('word-expression',32,2,f'Запишите выражение: произведение числа {A} и суммы x и {B}.','',rf'{A}(x+{B})',[identity(f'{A}*(x+{B})',A*x+A*B)],(144,37),hint='Сначала запишите сумму в скобках, затем умножение на число.')
 q('like-terms',33,1,'Выпишите слагаемые, подобные первому.',rf'{A}x;\ {B}y;\ {-C}x;\ {D};\ {N}x^2',rf'{A}x;\ {-C}x',[identity(A*x-C*x,(A-C)*x)],hint='У подобных слагаемых одинаковая буквенная часть с теми же показателями степеней.')
 ex('collect-terms',34,2,f'{A}*x+{B}-{C}*x+{D}',(A-C)*x+B+D,(33,))
 ex('bracket-plus',35,1,f'{A}*x+({B}*x-{C})',(A+B)*x-C,(34,),maths=rf'{A}x+({B}x-{C})',hint='После плюса знаки внутри скобок сохраняются.')
 ex('bracket-minus',36,2,f'{A}*x-({B}*x-{C})',(A-B)*x+C,(34,),maths=rf'{A}x-({B}x-{C})',hint='После минуса поменяйте знак каждого слагаемого внутри скобок.')
 ex('distribute-positive',37,1,f'{A}*(x+{B})',A*x+A*B,maths=rf'{A}(x+{B})',hint='Умножьте число на каждое слагаемое в скобках.')
 ex('distribute-negative',38,2,f'-{A}*({B}*x-{C})',-A*B*x+A*C,(16,37),maths=rf'-{A}({B}x-{C})')
 ex('distribute-fraction',39,4,f'({A}/{B})*({B*C}*x-{B*D})',A*C*x-A*D,(8,37),maths=frac(A,B)+rf'({B*C}x-{B*D})')
 ex('nested-brackets',40,5,f'{A}*({B}*x-{C}*(x-{D}))-{N}*(x+{M})',(A*(B-C)-N)*x+A*C*D-N*M,(34,36,37),maths=rf'{A}[{B}x-{C}(x-{D})]-{N}(x+{M})')
 q('simplify-substitute',41,3,rf'Упростите выражение, затем найдите его значение при x = {r}.',rf'{A}(x+{B})-{A}x',str(A*B),[identity(f'{A}*(x+{B})-{A}*x',A*B)],(31,34,37),hint='Слагаемые с x уничтожаются; значение не зависит от x.')
 q('rearrange-formula',42,3,'Выразите x через y.',rf'y={A}x-{B}',rf'x=\frac{{y+{B}}}{{{A}}}',[identity(f'{A}*((y+{B})/{A})-{B}','y')],(139,),hint='Перенесите свободное слагаемое, затем разделите на коэффициент при x.')
 # Linear equations
 eq('eq-add',43,1,f'x+{A}',B,[B-A])
 eq('eq-multiply',44,1,f'{A}*x',B,[S.Rational(B,A)])
 eq('eq-linear',45,2,f'{A}*x+{B}',C,[S.Rational(C-B,A)],(43,44))
 eq('eq-both',46,3,f'{A+C}*x+{B}',f'{A}*x+{D}',[S.Rational(D-B,C)],(34,45))
 eq('eq-one-bracket',47,3,f'{A}*(x-{B})',C,[B+S.Rational(C,A)],(37,45),maths=rf'{A}(x-{B})={C}')
 coeff=A*B-C
 if coeff:
  eq('eq-two-brackets',48,4,f'{A}*({B}*x-{C})-{C}*(x+{D})',N,[S.Rational(N+A*C+C*D,coeff)],(37,38,34,46),maths=rf'{A}({B}x-{C})-{C}(x+{D})={N}')
 eq('eq-decimals',49,3,f'{A}/10*x+{B}/10',f'{C}/10',[S.Rational(C-B,A)],(11,45),maths=rf'{A/10:g}x+{B/10:g}={C/10:g}')
 eq('eq-fraction-coeff',50,4,f'{A}/{B}*x+{C}/{D}',N,[(N-S.Rational(C,D))*B/A],(7,8,45))
 eq('eq-denominators',51,4,f'(x-{A})/{B}+(x+{C})/{D}',N,[S.Rational(N*B*D+A*D-B*C,B+D)],(7,5,47),maths=frac(f'x-{A}',B)+'+'+frac(f'x+{C}',D)+f'={N}')
 eq('eq-proportion',52,3,f'(x+{A})/{B}',f'{C}/{D}',[S.Rational(B*C,D)-A],(20,47),maths=frac(f'x+{A}',B)+'='+frac(C,D))
 q('eq-special',53,5,'Сколько решений имеет уравнение? Объясните.',rf'{A}(x+{B})={A}x+{A*B+C}',r'\text{Нет решений}',[identity(f'{A}*(x+{B})-({A}*x+{A*B+C})',-C)],(37,34),hint=f'После сокращения слагаемых с x получилось 0 = {C}, что неверно.',manual=True)
 q('eq-check',54,2,f'Является ли число {r} корнем уравнения?',rf'{A}x+{B}={A*r+B}',r'\text{Да}',[identity(A*r+B,A*r+B)],(31,),hint='Подставьте предложенное число вместо x и сравните части равенства.')
 # Powers
 number('power-eval',55,1,f'{A}**{M}',A**M,maths=rf'{A}^{{{M}}}')
 number('power-negative',56,2,f'(-{A})**{N}',(-A)**N,maths=rf'(-{A})^{{{N}}}',hint='При чётном показателе результат положительный, при нечётном — отрицательный.')
 q('power-minus-location',57,2,'Вычислите оба выражения и сравните.',rf'(-{A})^{{{2*M}}};\ -{A}^{{{2*M}}}',f'{A**(2*M)};\ {-A**(2*M)}',[identity(f'(-{A})**{2*M}',A**(2*M)),identity(f'-{A}**{2*M}',-A**(2*M))],(56,),hint='Минус вне скобок не входит в основание степени.')
 ex('power-product',58,2,f'x**{N}*x**{M}',x**(N+M),maths=rf'x^{{{N}}}\cdot x^{{{M}}}',hint='При умножении степеней одного основания показатели складываются.')
 ex('power-quotient',59,3,f'x**{N+M}/x**{M}',x**N,maths=frac(f'x^{{{N+M}}}',f'x^{{{M}}}')+r',\ x\ne0',hint='Вычтите показатели; исходное ограничение x ≠ 0 сохраняется.')
 ex('power-of-power',60,3,f'(x**{N})**{M}',x**(N*M),maths=rf'(x^{{{N}}})^{{{M}}}',hint='При возведении степени в степень показатели перемножаются.')
 ex('power-of-product',61,3,f'({A}*x**{N})**{M}',A**M*x**(N*M),(60,),maths=rf'({A}x^{{{N}}})^{{{M}}}')
 ex('power-of-quotient',62,4,f'({A}*x/{B})**{M}',S.Rational(A,B)**M*x**M,(61,8),maths=rf'\left({frac(f"{A}x",B)}\right)^{{{M}}}')
 number('power-zero',63,2,f'{A}**0+{B}',B+1,maths=rf'{A}^0+{B}',hint='Нулевая степень ненулевого числа равна 1.')
 number('power-negative-exponent',64,3,f'{A}**(-{M})',S.Rational(1,A**M),maths=rf'{A}^{{-{M}}}',hint='Отрицательный показатель даёт обратную величину.')
 number('power-common-base',65,4,f'{A*A}**{M}/{A}**{N}',S.Rational(A**(2*M),A**N),(59,60),maths=frac(f'{A*A}^{{{M}}}',f'{A}^{{{N}}}'),hint=f'Представьте {A*A} как {A}², затем вычтите показатели.')
 q('power-compare',66,3,'Сравните, не вычисляя большие степени.',rf'{A}^{{{N+M}}};\ {A}^{{{N}}}',rf'{A}^{{{N+M}}}>{A}^{{{N}}}',[{'kind':'truth','value':f'{A**(N+M)}>{A**N}'}],(58,),hint=f'Первое число в {A}^{{{M}}} раз больше второго.')
 q('standard-number',67,3,'Запишите число в стандартном виде.',str((A*10+B)*10**N),rf'{(A*10+B)/100:g}\cdot10^{{{N+2}}}' if A*10+B>=100 else rf'{(A*10+B)/10:g}\cdot10^{{{N+1}}}',[identity((A*10+B)*10**N,S.Rational(A*10+B,100 if A*10+B>=100 else 10)*10**(N+(2 if A*10+B>=100 else 1)))],(11,55),hint='Коэффициент перед степенью 10 должен быть не меньше 1 и меньше 10.')
 # Monomials, polynomials, identities
 q('monomial-degree',68,2,'Найдите коэффициент и степень одночлена.',rf'{-A}x^{{{N}}}y^{{{M}}}',rf'\text{{Коэффициент }}{-A};\ \text{{степень }}{N+M}',[identity(N+M,N+M)],hint='Коэффициент — числовой множитель, степень одночлена — сумма показателей переменных.')
 ex('monomial-standard',69,3,f'{A}*x**{N}*{B}*x**{M}',A*B*x**(N+M),(58,68),maths=rf'{A}x^{{{N}}}\cdot {B}x^{{{M}}}')
 ex('monomial-multiply',70,3,f'({A}*x**{N}*y)*(-{B}*x*y**{M})',-A*B*x**(N+1)*y**(M+1),(58,16))
 ex('monomial-power',71,4,f'(-{A}*x**{N}*y)**{M}',(-A)**M*x**(N*M)*y**M,(56,60,61),maths=rf'(-{A}x^{{{N}}}y)^{{{M}}}')
 ex('monomial-divide',72,4,f'({A*B}*x**{N+M}*y**{M})/({B}*x**{N}*y)',A*x**M*y**(M-1),(59,89),maths=frac(f'{A*B}x^{{{N+M}}}y^{{{M}}}',f'{B}x^{{{N}}}y')+r',\ x\ne0,\ y\ne0',hint='Сократите коэффициенты, вычтите показатели. x и y не равны нулю.')
 ex('polynomial-subtract',73,3,f'({A}*x**2+{B}*x-{C})-({D}*x**2-{N}*x+{M})',(A-D)*x*x+(B+N)*x-C-M,(36,34),maths=rf'({A}x^2+{B}x-{C})-({D}x^2-{N}x+{M})')
 ex('monomial-polynomial',74,3,f'{A}*x*({B}*x-{C})',A*B*x*x-A*C*x,(37,70),maths=rf'{A}x({B}x-{C})')
 ex('polynomial-product',75,4,f'({A}*x+{B})*(x-{C})',A*x*x+(B-A*C)*x-B*C,(74,34),maths=rf'({A}x+{B})(x-{C})')
 ex('common-factor',76,3,f'{A*B}*x**2+{A*C}*x',f'{A}*x*({B}*x+{C})',(69,),prompt='Вынесите общий множитель за скобки.',hint=f'У обоих слагаемых есть множитель {A}x.')
 ex('group-factor',77,4,f'x**3+{A}*x**2+{B}*x+{A*B}',f'(x+{A})*(x**2+{B})',(76,78),prompt='Разложите на множители.',hint='Сгруппируйте первые два и последние два слагаемых, затем вынесите общий двучлен.')
 ex('common-binomial',78,4,f'{A}*x*(x+{B})-{C}*(x+{B})',f'(x+{B})*({A}*x-{C})',(76,),prompt='Разложите на множители.',maths=rf'{A}x(x+{B})-{C}(x+{B})')
 ex('square-sum',79,3,f'({A}*x+{B})**2',A*A*x*x+2*A*B*x+B*B,(75,),maths=rf'({A}x+{B})^2',hint='Квадрат первого плюс удвоенное произведение плюс квадрат второго.')
 ex('square-difference',80,3,f'({A}*x-{B})**2',A*A*x*x-2*A*B*x+B*B,(75,),maths=rf'({A}x-{B})^2')
 ex('conjugate-product',81,3,f'({A}*x-{B})*({A}*x+{B})',A*A*x*x-B*B,(75,),maths=rf'({A}x-{B})({A}x+{B})',hint='Произведение разности и суммы равно разности квадратов.')
 ex('recognize-square',82,4,f'x**2+{2*A}*x+{A*A}',f'(x+{A})**2',(79,),prompt='Представьте в виде квадрата двучлена.')
 ex('factor-difference-square',83,3,f'{A*A}*x**2-{B*B}',f'({A}*x-{B})*({A}*x+{B})',(81,),prompt='Разложите на множители.')
 ex('factor-fsu-common',84,4,f'{C}*x**3-{C*A*A}*x',f'{C}*x*(x-{A})*(x+{A})',(76,83),prompt='Разложите на множители.')
 ex('factor-repeated',85,5,f'x**4-{A**4}',f'(x-{A})*(x+{A})*(x**2+{A*A})',(83,),prompt='Разложите на множители.',hint='Примените разность квадратов дважды.')
 number('fsu-numeric',86,3,f'({A*10+B})**2-({A*10-B})**2',4*A*10*B,(81,),maths=rf'{A*10+B}^2-{A*10-B}^2',hint='Представьте разность квадратов как произведение суммы и разности.')
 q('prove-identity',87,6,'Докажите тождество.',rf'(x+{A})^2-(x-{A})^2={4*A}x',rf'{4*A}x={4*A}x',[identity(f'(x+{A})**2-(x-{A})**2',4*A*x)],(79,80,34),hint='Раскройте оба квадрата; квадратные и свободные слагаемые сократятся.',manual=True)
 ex('complete-square',88,5,f'x**2-{2*A}*x+{B}',f'(x-{A})**2+{B-A*A}',(80,),prompt='Выделите полный квадрат.',hint=f'Добавьте и вычтите {A*A}.')
 # Rational expressions
 q('domain',89,2,'Найдите допустимые значения x.',frac(f'x+{B}',f'x-{A}'),rf'x\ne {A}',[{'kind':'equation','lhs':f'x-{A}','rhs':'0','roots':[str(A)],'exclude':[]}],hint='Знаменатель не может равняться нулю.')
 ex('rational-monomial',90,3,f'{A*B}*x**{N+1}/({B}*x**{N})',A*x,(72,89),maths=frac(f'{A*B}x^{{{N+1}}}',f'{B}x^{{{N}}}'),hint='Сократите общие множители; исходная ОДЗ: x ≠ 0.')
 ex('rational-factor',91,4,f'(x**2-{A*A})/(x-{A})',x+A,(83,99,89),maths=frac(f'x^2-{A*A}',f'x-{A}'),hint=f'Разложите числитель на множители и сократите x − {A}; исходная ОДЗ: x ≠ {A}.')
 q('rational-common-den',92,3,'Укажите наименьший общий знаменатель дробей.',frac(1,f'x-{A}')+r';\ '+frac(1,f'x+{B}'),rf'(x-{A})(x+{B})',[identity(f'(x-{A})*(x+{B})',x*x+(B-A)*x-A*B)],(89,),hint=f'Возьмите произведение разных линейных множителей. x ≠ {A}, x ≠ {-B}.')
 ex('rational-add-same',93,3,f'{A}/(x+{C})+{B}/(x+{C})',f'{A+B}/(x+{C})',(7,89),maths=frac(A,f'x+{C}')+'+'+frac(B,f'x+{C}'),hint=f'Сложите числители, знаменатель сохраните. x ≠ {-C}.')
 ex('rational-add-different',94,4,f'{A}/x+{B}/(x+{C})',f'({A+B}*x+{A*C})/(x*(x+{C}))',(92,7,89),maths=frac(A,'x')+'+'+frac(B,f'x+{C}'),hint=f'Общий знаменатель x(x + {C}). x ≠ 0 и x ≠ {-C}.')
 ex('rational-multiply',95,4,f'((x**2-{A*A})/{B})*({B}/(x-{A}))',x+A,(83,91,89),maths=frac(f'x^2-{A*A}',B)+r'\cdot'+frac(B,f'x-{A}'),hint=f'Разложите числитель, сократите общие множители. x ≠ {A}.')
 ex('rational-divide',96,4,f'({A}/(x+{B}))/({C}/(x+{B}))',S.Rational(A,C),(9,89),maths=frac(A,f'x+{B}')+':'+frac(C,f'x+{B}'),hint=f'Умножьте на обратную дробь. x ≠ {-B}.')
 ex('rational-power',97,4,f'({A}*x/(x+{B}))**2',f'{A*A}*x**2/(x+{B})**2',(62,89),maths=rf'\left({frac(f"{A}x",f"x+{B}")}\right)^2',hint=f'Возведите числитель и знаменатель в квадрат. x ≠ {-B}.')
 ex('complex-fraction',98,5,f'(1+{A}/x)/(1-{A}/x)',f'(x+{A})/(x-{A})',(94,96,99,89),maths=frac('1+'+frac(A,'x'),'1-'+frac(A,'x')),hint=f'Умножьте числитель и знаменатель большой дроби на x. Исходная ОДЗ: x ≠ 0 и x ≠ {A}.')
 # Radicals
 number('sqrt-perfect',100,1,f'sqrt({A*A*B*B})',A*B,maths=rf'\sqrt{{{A*A*B*B}}}')
 h=A*A+C
 lo=math.isqrt(h)
 if lo*lo==h:h+=1
 lo=math.isqrt(h)
 q('sqrt-estimate',101,2,'Между какими соседними целыми числами находится корень?',rf'\sqrt{{{h}}}',rf'{lo}<\sqrt{{{h}}}<{lo+1}',[{'kind':'truth','value':f'{lo*lo}<{h}<{(lo+1)**2}'}],hint='Сравните подкоренное число с квадратами соседних целых чисел.')
 number('sqrt-product',102,2,f'sqrt({A*A}*{B*B})',A*B,maths=rf'\sqrt{{{A*A}\cdot {B*B}}}')
 number('sqrt-quotient',103,2,f'sqrt({A*A}/{B*B})',S.Rational(A,B),maths=rf'\sqrt{{{frac(A*A,B*B)}}}')
 d=R.choice([2,3,5,7,11])
 ex('sqrt-extract',104,3,f'sqrt({A*A*d})',A*S.sqrt(d),prompt='Вынесите множитель из-под корня.',maths=rf'\sqrt{{{A*A*d}}}',hint='Выделите полный квадрат под знаком корня.')
 q('sqrt-insert',105,3,'Внесите множитель под знак корня.',rf'{A}\sqrt{{{d}}}',rf'\sqrt{{{A*A*d}}}',[identity(f'{A}*sqrt({d})',f'sqrt({A*A*d})')],hint=f'Положительный множитель {A} вносится под корень в квадрате.')
 ex('sqrt-like',106,3,f'{A}*sqrt({d})-{B}*sqrt({d})+{C}*sqrt({d})',(A-B+C)*S.sqrt(d),(34,),maths=rf'{A}\sqrt{{{d}}}-{B}\sqrt{{{d}}}+{C}\sqrt{{{d}}}')
 ex('sqrt-multiply',107,4,f'(sqrt({d})+{A})*(sqrt({d})-{A})',d-A*A,(81,),maths=rf'(\sqrt{{{d}}}+{A})(\sqrt{{{d}}}-{A})')
 number('sqrt-square-abs',108,3,f'sqrt(({r}-{A})**2)',abs(r-A),(17,),maths=rf'\sqrt{{({r}-{A})^2}}',hint='Корень из квадрата числа равен его модулю.')
 ex('rationalize',109,4,f'{A}/sqrt({d})',S.Rational(A,d)*S.sqrt(d),(8,),maths=frac(A,rf'\sqrt{{{d}}}'),prompt='Освободитесь от иррациональности в знаменателе.',hint='Умножьте числитель и знаменатель на корень из знаменателя.')
 q('compare-radicals',110,3,'Сравните.',rf'{A}\sqrt{{{d}}};\ \sqrt{{{A*A*d+1}}}',rf'{A}\sqrt{{{d}}}<\sqrt{{{A*A*d+1}}}',[{'kind':'truth','value':f'{A*A*d}<{A*A*d+1}'}],(105,),hint='Оба выражения неотрицательны: сравните их квадраты.')
 # Quadratics
 eq('quad-pure',111,2,'x**2',A*A,[-A,A],(100,))
 eq('quad-factor-x',112,3,f'{A}*x**2-{A*B}*x',0,[0,B],(76,117))
 eq('quad-discriminant',113,3,f'x**2-{r+s}*x+{r*s}',0,[r,s],(114,))
 discr=4*A*A-4*(A*A+B)
 q('discriminant-count',114,3,'Сколько действительных корней имеет уравнение?',rf'x^2+{2*A}x+{A*A+B}=0',r'0',[identity(f'{2*A}**2-4*{A*A+B}',discr)],hint=f'Дискриминант равен {discr} < 0, действительных корней нет.')
 eq('quad-vieta',115,3,f'x**2-{A+B}*x+{A*B}',0,[A,B],hint=f'По Виету сумма корней {A+B}, произведение {A*B}.')
 ex('quad-factor',116,4,f'x**2-{A+B}*x+{A*B}',f'(x-{A})*(x-{B})',(115,),prompt='Разложите квадратный трёхчлен на множители.')
 eq('zero-product',117,2,f'(x-{r})*(x-{s})',0,[r,s],maths=rf'(x-({r}))(x-({s}))=0',hint='Произведение равно нулю, если хотя бы один множитель равен нулю.')
 eq('quad-transform',118,4,f'(x+{A})**2',f'{B*B}',[-A-B,-A+B],(79,111),maths=rf'(x+{A})^2={B*B}')
 if A!=B:
  eq('rational-equation',119,5,f'(x**2-{A*A})/(x-{A})',B,([] if B==2*A else [B-A]),(91,89,45),maths=frac(f'x^2-{A*A}',f'x-{A}')+f'={B}',exclude=[A],hint=f'ОДЗ: x ≠ {A}. После сокращения x + {A} = {B}. '+('Полученное значение запрещено: решений нет.' if B==2*A else 'Полученный корень удовлетворяет ОДЗ.'))
 q('quad-positive-root',120,4,f'Найдите положительный корень уравнения.',rf'x^2-{A*A}=0',str(A),[{'kind':'equation','lhs':'x**2','rhs':str(A*A),'roots':[str(-A),str(A)],'exclude':[]},{'kind':'truth','value':f'{A}>0'}],(111,),hint=f'Корни −{A} и {A}; условию соответствует положительный.')
 # Inequalities
 q('inequality-add',121,1,f'Известно, что a < b. Сравните a + {A} и b + {A}.','',rf'a+{A}<b+{A}',[identity(f'(b+{A})-(a+{A})','b-a')],hint='При прибавлении одного числа к обеим частям знак неравенства сохраняется.',manual=True)
 q('inequality-negative',122,2,'Решите неравенство.',rf'-{A}x>{-A*r}',rf'x<{r}',[identity(f'-{A}*x-({-A*r})',f'-{A}*(x-({r}))')],(123,),hint='При делении на отрицательное число знак неравенства меняется.')
 q('inequality-linear',123,2,'Решите неравенство.',rf'{A}x+{B}\le {A*r+B}',rf'x\le {r}',[identity(f'{A}*x+{B}-({A*r+B})',f'{A}*(x-({r}))')],hint=f'Вычтите {B}, разделите на положительное число {A}.')
 q('inequality-fraction',124,4,'Решите неравенство.',frac(f'{A}(x-{B})',C)+rf'>{D}',rf'x>{tex(B+S.Rational(C*D,A))}',[identity(f'{A}*(x-{B})/{C}-{D}',f'{A}/{C}*(x-({B+S.Rational(C*D,A)}))')],(39,51,123),hint='Умножьте на положительный знаменатель и решите линейное неравенство.')
 q('interval-record',125,1,'Запишите множество решений промежутком.',rf'{r}<x\le {s}',rf'({r};{s}]',[{'kind':'truth','value':f'{r}<{s}'}],hint='Строгой границе соответствует круглая скобка, нестрогой — квадратная.')
 q('interval-intersection',126,3,'Решите систему неравенств.',rf'\begin{{cases}}x>{r}\\x\le {s}\end{{cases}}',rf'({r};{s}]',[{'kind':'truth','value':f'{r}<{s}'}],(125,),hint='Нужно одновременное выполнение обоих условий.')
 q('integer-solutions',127,3,'Найдите все целые решения.',rf'{r}<x\le {s}',r';\ '.join(map(str,range(r+1,s+1))),[{'kind':'truth','value':f'{r}<{r+1} and {s}<={s}'}],(125,),hint='Левая граница не включается, правая включается.')
 q('interval-membership',128,2,f'Принадлежит ли число {r} данному промежутку?',rf'({r};{s}]',r'\text{Нет}',[{'kind':'truth','value':f'not ({r}<{r}<={s})'}],(125,),hint='Круглая скобка исключает граничное число.')
 # Functions and systems
 q('function-value',129,1,f'Найдите f({r}).',rf'f(x)={A}x+{B}',str(A*r+B),[identity(A*r+B,A*r+B)],(31,),hint='Подставьте аргумент в формулу.')
 eq('function-argument',130,2,f'{A}*x+{B}',A*r+B,[r],(129,45))
 q('point-on-line',131,2,f'Принадлежит ли точка ({r}; {A*r+B}) графику функции?',rf'y={A}x+{B}',r'\text{Да}',[identity(A*r+B,A*r+B)],(129,),hint='Подставьте абсциссу точки и сравните результат с ординатой.')
 graph={'curves':[{'kind':'line','a':A,'b':-B}], 'range':[-5,5]}
 q('draw-line',132,3,'Постройте график функции. Укажите две точки графика.',rf'y={A}x-{B}',rf'(0;{-B}),\ (1;{A-B})',[identity(A*0-B,-B),identity(A-B,A-B)],(129,131),hint='Отметьте две точки и проведите через них прямую.',manual=True)
 q('line-coefficients',133,2,'Найдите k и b. Возрастает или убывает функция?',rf'y={-A}x+{B}',rf'k={-A},\ b={B};\ \text{{убывает}}',[{'kind':'truth','value':f'{-A}<0'}],hint='В записи y = kx + b коэффициент k отрицательный, значит функция убывает.')
 rr=S.Rational(B+D,A+C)
 q('line-intersection',134,4,'Найдите координаты точки пересечения графиков.',rf'y={A}x-{B},\quad y={-C}x+{D}',rf'({tex(rr)};{tex(A*rr-B)})',[identity(A*rr-B,-C*rr+D)],(46,140),hint='Приравняйте выражения для y, найдите x и подставьте в любую формулу.')
 q('read-line-graph',135,2,f'По графику найдите значение y при x = 1.','',str(A-B),[identity(A-B,A-B)],(129,),hint='Найдите x = 1 на горизонтальной оси, затем ординату соответствующей точки прямой.',graph=graph)
 q('inverse-function',136,3,f'Найдите значение функции при x = {A}.',rf'y=\frac{{{A*B}}}{{x}}',str(B),[identity(f'{A*B}/{A}',B)],(9,),hint='Подставьте ненулевое значение x в знаменатель.')
 q('parabola-graph',137,3,f'На графике y = x² найдите точки с ординатой {A*A}.',r'y=x^2',rf'({-A};{A*A}),\ ({A};{A*A})',[identity((-A)**2,A*A),identity(A**2,A*A)],(111,),hint='Положительной ординате соответствуют два противоположных значения x.',graph={'curves':[{'kind':'parabola'}],'range':[-A-1,A+1]})
 e1=x+y-r-s;e2=A*x-y-A*r+s
 q('system-check',138,2,f'Проверьте, является ли пара ({r}; {s}) решением системы.',rf'\begin{{cases}}x+y={r+s}\\{A}x-y={A*r-s}\end{{cases}}',r'\text{Да}',[identity(r+s,r+s),identity(A*r-s,A*r-s)],(54,31),hint='Подставьте пару чисел в каждое уравнение системы.')
 q('system-express',139,2,'Выразите y через x.',rf'{A}x+y={B}',rf'y={B}-{A}x',[identity(f'{A}*x+({B}-{A}*x)',B)],(42,),hint='Перенесите слагаемое с x в правую часть.')
 system('system-substitute',140,3,e1,e2,r,s,(139,45))
 system('system-eliminate',141,4,A*x+B*y-A*r-B*s,C*x-B*y-C*r+B*s,r,s,(46,),hint='Сложите уравнения: слагаемые с y уничтожатся. Найдите x, затем y.')
 system('system-graphical',142,4,y-x-r,y+x-s,S.Rational(s-r,2),S.Rational(s+r,2),(132,134),hint='Постройте две прямые; их точка пересечения — решение системы.',graph={'curves':[{'kind':'line','a':1,'b':r},{'kind':'line','a':-1,'b':s}],'range':[-max(abs(r),abs(s))-2,max(abs(r),abs(s))+2]})
 q('system-special',143,5,'Сколько решений имеет система? Объясните.',rf'\begin{{cases}}x+y={A}\\{B}x+{B}y={A*B+C}\end{{cases}}',r'\text{Нет решений}',[identity(f'{A*B+C}-{B}*{A}',C)],(53,),hint=f'Левые части пропорциональны, правые отличаются от нужной пропорции на {C}.',manual=True)
 # Word problems
 q('word-purchases',146,2,f'Купили {A} ручек по {B} рублей и {C} карандашей по {D} рублей. Сколько рублей сдачи получат с {A*B+C*D+100}?','',str(100),[identity(f'{A*B+C*D+100}-{A}*{B}-{C}*{D}',100)],(144,18),hint='Из уплаченной суммы вычтите стоимость обеих покупок.')
 q('word-distance',148,2,f'Велосипедист ехал {A} часов со скоростью {B+10} км/ч. Найдите пройденный путь в километрах.','',str(A*(B+10)),[identity(f'{A}*{B+10}',A*(B+10))],(156,),hint='Путь равен произведению скорости на время; единицы согласованы.')
 q('word-meeting',149,3,f'Два пешехода вышли одновременно навстречу друг другу из пунктов на расстоянии {(M+N)*C} км. Их скорости {M} и {N} км/ч. Через сколько часов они встретятся?','',str(C),[identity(f'{(M+N)*C}/({M}+{N})',C)],(148,45),hint='Скорость сближения равна сумме скоростей.')
 q('word-average-speed',150,4,f'Первую половину пути автомобиль ехал со скоростью {A*10} км/ч, вторую — {B*10} км/ч. Найдите среднюю скорость на всём пути в км/ч.','',tex(S.Rational(20*A*B,A+B)),[identity(f'2/(1/{A*10}+1/{B*10})',S.Rational(20*A*B,A+B))],(9,7,148),hint='Возьмите одинаковую длину двух участков. Разделите весь путь на суммарное время; среднее арифметическое скоростей здесь не подходит.')
 q('word-work',151,4,f'Первый мастер выполняет заказ за {A} часов, второй — за {B} часов. За сколько часов они выполнят заказ вместе?','',tex(S.Rational(A*B,A+B)),[identity(f'1/(1/{A}+1/{B})',S.Rational(A*B,A+B))],(7,9),hint='Сложите доли заказа за час, затем найдите обратную величину.')
 q('word-mixture',152,5,f'Смешали {A} кг {N*5}%-го и {B} кг {M*10}%-го раствора соли. Найдите концентрацию смеси в процентах.','',tex(S.Rational(A*N*5+B*M*10,A+B))+r'\%',[identity(f'({A}*{N*5}/100+{B}*{M*10}/100)/({A}+{B})*100',S.Rational(A*N*5+B*M*10,A+B))],(25,7,8,156),hint='Найдите массу соли в каждом растворе, сложите и разделите на общую массу смеси.')
 tens=R.randint(1,8);ones=R.randint(1,9)
 q('word-digits',153,3,f'Сумма цифр двузначного числа равна {tens+ones}. Цифра десятков равна {tens}. Найдите число.','',str(tens*10+ones),[identity(10*tens+(tens+ones-tens),10*tens+ones)],(144,45),hint='Найдите цифру единиц, затем используйте запись числа 10a + b.')
 q('word-system',154,4,f'За {A} одинаковых ручек и одну тетрадь заплатили {A*C+D} рублей. За {A+1} таких ручек и тетрадь — {(A+1)*C+D} рублей. Найдите цену ручки и тетради.','',rf'\text{{Ручка }}{C};\ \text{{тетрадь }}{D}',[{'kind':'system','equations':[f'{A}*x+y-{A*C+D}',f'{A+1}*x+y-{(A+1)*C+D}'],'x':str(C),'y':str(D)}],(146,141,144),hint='Вычтите первое уравнение из второго: разность платежей равна цене одной ручки.')
 q('word-quadratic',155,5,f'Произведение двух последовательных положительных целых чисел равно {A*(A+1)}. Найдите эти числа.','',rf'{A};\ {A+1}',[identity(A*(A+1),A*(A+1)),{'kind':'equation','lhs':'x*(x+1)','rhs':str(A*(A+1)),'roots':[str(A),str(-A-1)],'exclude':[]}],(118,120,156),hint=f'Пусть меньшее число x. Получаем x(x + 1) = {A*(A+1)}. Отрицательный корень не подходит по смыслу.')
 # Mixed and nonstandard tasks: separately authored structures
 coeff=S.Rational(2**M,B)-S.Rational(1,D)
 if coeff:
  target=S.Rational(2**M*(r-A),B)-S.Rational(r+C,D)
  eq('mixed-equation-power-fractions',51,5,f'2**{M}*(x-{A})/{B}-(x+{C})/{D}',target,[r],(55,7,47,38),maths=frac(rf'2^{{{M}}}(x-{A})',B)+'-'+frac(f'x+{C}',D)+'='+tex(target),hint='Вычислите степень, умножьте обе части на общий знаменатель, раскройте скобки и приведите подобные.')
 q('reverse-brackets',37,6,'Найдите пропущенное число.',rf'\square(x+{A})={B}x+{A*B}',str(B),[identity(f'{B}*(x+{A})',B*x+A*B)],(34,),hint='Коэффициент при x равен искомому множителю; проверьте свободное слагаемое.')
 q('brackets-error',36,6,'Ученик допустил ошибку. Исправьте равенство и объясните.',rf'-{A}(x-{B})=-{A}x-{A*B}',rf'-{A}(x-{B})=-{A}x+{A*B}',[identity(f'-{A}*(x-{B})',-A*x+A*B)],(38,16),hint='Произведение двух отрицательных чисел положительно.',manual=True)
 q('reverse-equation',45,6,f'Найдите число b, чтобы уравнение имело корень {r}.',rf'{A}x+b={C}',rf'b={C-A*r}',[identity(A*r+(C-A*r),C)],(31,54),hint='Подставьте известный корень вместо x и найдите b.')
 q('power-exponent-find',58,6,'Найдите натуральное n.',rf'{A}^n+{A}^{{n+1}}={(A+1)*A**M}',rf'n={M}',[identity(f'{A}**{M}+{A}**({M}+1)',(A+1)*A**M)],(65,76,55),hint=f'Вынесите {A}^n. Получится ({A}+1)·{A}^n; затем представьте правую часть степенью {A}.')
 q('fraction-error',7,6,'Верно ли равенство? Исправьте правую часть.',frac(A,B)+'+'+frac(C,D)+'='+frac(A+C,B+D),tex(S.Rational(A,B)+S.Rational(C,D)),[identity(f'{A}/{B}+{C}/{D}',S.Rational(A,B)+S.Rational(C,D))],(5,4),hint='Числители складывают только после приведения к общему знаменателю. Проверьте, не совпал ли результат случайно.',manual=True)
 q('percent-reverse',29,6,f'Цену увеличили на {p}%. На сколько процентов нужно снизить новую цену, чтобы вернуться к исходной?','',tex(S.Rational(100*p,100+p))+r'\%',[identity(f'(1+{p}/100)*(1-({S.Rational(100*p,100+p)})/100)',1)],(26,147),hint='За исходную цену примите 100. Нужно убрать прибавку из новой, большей базы.')
 q('polynomial-constant',87,6,'Докажите, что значение выражения не зависит от x.',rf'(x+{A})(x-{A})-x^2+{B}',str(B-A*A),[identity(f'(x+{A})*(x-{A})-x**2+{B}',B-A*A)],(81,34),hint='Примените разность квадратов, затем сократите x².',manual=True)
 q('domain-trap',99,6,'После сокращения дроби ученик подставил запрещённое значение. Объясните ошибку.',frac(f'x^2-{A*A}',f'x-{A}')+rf',\quad x={A}',rf'\text{{При }}x={A}\text{{ исходное выражение не определено}}',[identity(f'(x**2-{A*A})/(x-{A})',x+A),identity(A-A,0)],(89,91,83),hint='Упрощение формулы не расширяет исходную область определения.',manual=True)
 q('sqrt-error',108,6,f'Верно ли, что при x = {-A} выражение √(x²) равно x? Объясните.','',rf'\text{{Нет: }}\sqrt{{({-A})^2}}={A}',[identity(f'sqrt({A*A})',A)],(17,100),hint='Арифметический корень неотрицателен; √(x²) = |x|.',manual=True)
 q('quad-missing-coeff',115,6,f'Один корень уравнения равен {A}. Найдите b и второй корень.',rf'x^2-bx+{A*B}=0',rf'b={A+B},\quad x_2={B}',[identity(A*B,A*B),identity(f'{A}**2-{A+B}*{A}+{A*B}',0)],(54,116),hint='По произведению корней найдите второй корень; коэффициент b равен сумме корней.')
 q('inequality-error',122,6,'Ученик разделил на отрицательное число, не изменив знак. Запишите верное решение.',rf'-{A}x<{B}',rf'x>{tex(-S.Rational(B,A))}',[identity(f'-{A}*x-{B}',f'-{A}*(x+{B}/{A})')],(123,),hint='При делении на отрицательное число знак меняется на противоположный.',manual=True)
 q('function-reconstruct',133,6,f'Линейная функция проходит через точки (0; {B}) и ({C}; {A*C+B}). Найдите её формулу.','',rf'y={A}x+{B}',[identity(A*C+B,A*C+B)],(132,134,129),hint=f'Из первой точки b = {B}. Подставьте вторую точку и найдите k.')
 q('average-explain',150,6,f'На двух равных участках скорости были {A*10} и {B*10} км/ч. Объясните, когда средняя скорость совпадает со средним арифметическим этих скоростей.','',r'\text{Только при равных скоростях}',[identity('(a+b)**2-4*a*b','(a-b)**2')],(88,148),hint='Средняя скорость на равных участках — 2uv/(u+v). Равенство со средним арифметическим приводит к (u−v)² = 0.',manual=True)
 # Extra explicit pilot ladders and combined polynomial powers
 ex('brackets-many',37,3,f'{A}*(x-{B})-{C}*(x+{D})',(A-C)*x-A*B-C*D,(38,34),maths=rf'{A}(x-{B})-{C}(x+{D})')
 ex('brackets-fraction-sum',39,4,f'{A}/{B}*({B}*x-{C})-{D}/{B}*(x+{N})',(A-S.Rational(D,B))*x-S.Rational(A*C+D*N,B),(7,34,38),maths=frac(A,B)+rf'({B}x-{C})-'+frac(D,B)+rf'(x+{N})')
 ex('powers-nested-fraction',60,5,f'(({A}*x**{N})**2*{B}*x**{M})/({A*B}*x**{N+1})',A*x**(N+M-1),(58,59,61,72,90),maths=frac(rf'({A}x^{{{N}}})^2\cdot {B}x^{{{M}}}',rf'{A*B}x^{{{N+1}}}'),hint='Возведите произведение в степень, сложите показатели в числителе, затем сократите. x ≠ 0.')

public=[]
for t in tasks:
 u={k:v for k,v in t.items() if k!='checks'};u['grades']=sorted(set.intersection(*(set(BY[i]['grades']) for i in u['skills'])));public.append(u)
covered={i for t in tasks for i in t['skills']}
missing=set(BY)-covered
assert not missing,missing
out=ROOT/'preview/algebra-bank';out.mkdir(parents=True,exist_ok=True)
levels=['Первые шаги','Закрепление','Несколько шагов','Сочетание навыков','Сложные комбинации','Нестандартные']
(out/'bank.json').write_text(json.dumps({'version':'2026-09-08.1','groups':groups,'skills':skills,'levels':levels,'tasks':public},ensure_ascii=False,separators=(',',':')))
(ROOT/'scripts/algebra-bank/checks.json').write_text(json.dumps(tasks,ensure_ascii=False,separators=(',',':')))
print(json.dumps({'tasks':len(tasks),'skills':len(skills),'templates':len(occ),'levels':{i:sum(t['level']==i for t in tasks) for i in range(1,7)}},ensure_ascii=False))
