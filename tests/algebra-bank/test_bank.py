import unittest, json, importlib.util
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
class BankTest(unittest.TestCase):
 def test_generator_and_verifier_exist(self):
  self.assertTrue((ROOT/'scripts/algebra-bank/build.py').exists(), 'Bank generator is missing')
  self.assertTrue((ROOT/'scripts/algebra-bank/verify.py').exists(), 'Independent verifier is missing')
 def test_published_bank_contract(self):
  path=ROOT/'preview/algebra-bank/bank.json'
  self.assertTrue(path.exists(), 'Published bank has not been generated')
  d=json.loads(path.read_text()); tasks=d['tasks']
  self.assertEqual(len(d['skills']),156)
  self.assertGreaterEqual(len(tasks),6000)
  self.assertEqual(len({t['id'] for t in tasks}),len(tasks))
  self.assertEqual(len({(t['prompt'],t['math']) for t in tasks}),len(tasks))
  self.assertEqual({t['level'] for t in tasks},set(range(1,7)))
  known={s['id'] for s in d['skills']}; covered=set()
  for t in tasks:
   self.assertTrue(t['answer']); self.assertTrue(t['solution'])
   self.assertIn(t['primary'],t['skills']); self.assertTrue(set(t['skills'])<=known)
   self.assertIn(t['level'],range(1,7)); covered.update(t['skills'])
  self.assertEqual(covered,known)
  self.assertTrue(any({'A051','A055','A007'}<=set(t['skills']) for t in tasks), 'Equation/powers/fractions combination missing')
 def test_published_equations_respect_domain(self):
  d=json.loads((ROOT/'scripts/algebra-bank/checks.json').read_text())
  for t in d:
   for c in t['checks']:
    if c['kind']=='equation':
     self.assertFalse(set(c['roots']) & set(c.get('exclude',[])), t['id'])
 def test_insert_radical_keeps_requested_form(self):
  d=json.loads((ROOT/'preview/algebra-bank/bank.json').read_text())
  for t in d['tasks']:
   if t['template']=='sqrt-insert': self.assertTrue(t['answer'].startswith('\\sqrt{'), t['answer'])
 def test_verifier_rejects_wrong_math(self):
  p=ROOT/'scripts/algebra-bank/verify.py'
  self.assertTrue(p.exists(), 'Verifier missing')
  spec=importlib.util.spec_from_file_location('verify',p); m=importlib.util.module_from_spec(spec); spec.loader.exec_module(m)
  self.assertFalse(m.check({'kind':'identity','lhs':'2*(x+3)','rhs':'2*x-6'}))
  self.assertFalse(m.check({'kind':'equation','lhs':'(x**2-1)/(x-1)','rhs':'2','roots':['1'],'exclude':['1']}))
  self.assertTrue(m.check({'kind':'equation','lhs':'3*(x-2)','rhs':'2*x+5','roots':['11'],'exclude':[]}))
if __name__=='__main__': unittest.main()
