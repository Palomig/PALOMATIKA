#!/usr/bin/env python3
"""Проверяет, что ни одно задание не разорвано переносом страницы.

Разорванное задание — половина условия на одном листе, чертёж и строка ответа
на другом — на глаз в пачке из полусотни работ не заметить, а ученику достаётся
лист, по которому нельзя решать. Признак разрыва: на полосе строк «Ответ:»
больше, чем номеров заданий.

    python3 scripts/print/check-splits.py 'storage/app/print/*.pdf'
"""
import re, subprocess, sys, glob

def page_items(pdf, page):
    subprocess.run(['pdftotext','-bbox','-f',str(page),'-l',str(page),pdf,'/tmp/sc.xml'],
                   check=True, capture_output=True)
    d = open('/tmp/sc.xml', encoding='utf-8').read()
    ws = re.findall(r'<word xMin="([\d.]+)"[^>]*>([^<]*)</word>', d)
    nums = [t for x, t in ws if 60 < float(x) < 80 and t.isdigit()]
    answers = [t for x, t in ws if t == 'Ответ:']
    return nums, answers

def pages(pdf):
    out = subprocess.run(['pdfinfo', pdf], capture_output=True, text=True).stdout
    return int(re.search(r'^Pages:\s+(\d+)', out, re.M).group(1))

bad = 0; checked = 0
for pdf in sorted(glob.glob(sys.argv[1])):
    checked += 1
    for p in range(1, pages(pdf) + 1):
        nums, answers = page_items(pdf, p)
        if len(answers) > len(nums):
            print(f'  {pdf.split("/")[-1]} стр.{p}: «Ответ» {len(answers)}, номеров заданий {len(nums)}')
            bad += 1
print('РАЗРЫВОВ НЕ НАЙДЕНО' if bad == 0 else f'найдено подозрительных полос: {bad}',
      f'(проверено {checked} работ)')
