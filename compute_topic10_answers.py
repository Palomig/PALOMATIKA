#!/usr/bin/env python3
"""
Compute answers for all probability tasks in topic_10.json.
Parses Russian text, identifies problem type, extracts numbers, computes probability.
"""

import json
import re
import math
from fractions import Fraction

INPUT_FILE = "/home/dev/palomatika/storage/app/tasks/topic_10.json"


def fmt(val):
    """Format a probability value as a clean decimal string."""
    # Round to avoid floating point noise
    val = round(val, 10)
    # If it's a clean fraction with small denominator, express exactly
    frac = Fraction(val).limit_denominator(10000)
    result = float(frac)
    # Format: remove trailing zeros
    s = f"{result:.10f}".rstrip('0').rstrip('.')
    return s


def dice_sum_count(target):
    """Count ways to get target sum with two dice."""
    count = 0
    for a in range(1, 7):
        for b in range(1, 7):
            if a + b == target:
                count += 1
    return count


def dice_sum_counts(targets):
    """Count ways to get any of the target sums with two dice."""
    count = 0
    for a in range(1, 7):
        for b in range(1, 7):
            if a + b in targets:
                count += 1
    return count


def comb(n, k):
    """Binomial coefficient."""
    return math.comb(n, k)


# Russian number words to integers
RUSSIAN_NUMS = {
    'один': 1, 'одна': 1, 'одно': 1, 'одного': 1,
    'два': 2, 'две': 2, 'двух': 2,
    'три': 3, 'трёх': 3, 'трех': 3,
    'четыре': 4, 'четырёх': 4, 'четырех': 4,
    'пять': 5, 'пяти': 5,
    'шесть': 6, 'шести': 6,
    'семь': 7, 'семи': 7,
    'восемь': 8, 'восьми': 8,
    'девять': 9, 'девяти': 9,
    'десять': 10, 'десяти': 10,
    'одиннадцать': 11, 'двенадцать': 12, 'тринадцать': 13,
    'четырнадцать': 14, 'пятнадцать': 15, 'шестнадцать': 16,
    'семнадцать': 17, 'восемнадцать': 18, 'девятнадцать': 19,
    'двадцать': 20, 'двадцати': 20,
    'тридцать': 30, 'тридцати': 30,
    'сорок': 40,
    'пятьдесят': 50, 'пятидесяти': 50,
    'семьдесят': 70, 'семидесяти': 70,
    'сто': 100, 'ста': 100,
}

# Ordinal forms for "каждой N-ой"
RUSSIAN_ORDINAL_NUMS = {
    'четвертой': 4, 'четвёртой': 4,
    'пятой': 5,
    'десятой': 10,
    'двадцатой': 20,
    'двадцать пятой': 25,
    'тридцатой': 30,
    'пятидесятой': 50,
    'сотой': 100,
}


def parse_russian_num(word):
    """Parse a Russian number word or a digit string to int."""
    word = word.strip().lower()
    if word.isdigit():
        return int(word)
    # Try compound like "двадцать пятой" -> handled separately
    return RUSSIAN_NUMS.get(word, None)


def solve_task(block_num, zadanie_num, task, zadanie_instruction):
    """Solve a single task and return the answer as a string."""
    text = task['text']

    # ===== BLOCK 1, ZADANIE 1: Classical probability =====
    # Taxi problems
    m = re.search(r'свободно (\d+) машин.*?(\d+) чёрн\w+[,] (\d+) жёлт\w+ и (\d+) зелён', text)
    if m:
        total = int(m.group(1))
        black, yellow, green = int(m.group(2)), int(m.group(3)), int(m.group(4))
        if 'жёлтое' in text:
            return fmt(yellow / total)

    # Puzzle/gift problems
    m = re.search(r'закупил (\d+) пазлов.*?(\d+) с машинами и (\d+) с видами', text)
    if m:
        total = int(m.group(1))
        cars = int(m.group(2))
        return fmt(cars / total)

    # Ski race
    m = re.search(r'участвуют (\d+) спортсмен\w* из России.*?(\d+) спортсмен\w* из Норвегии.*?(\d+) спортсмен\w* из Швеции', text)
    if m:
        russia, norway, sweden = int(m.group(1)), int(m.group(2)), int(m.group(3))
        total = russia + norway + sweden
        if 'из Швеции' in text and 'Найдите' in text:
            after_find = text[text.index('Найдите'):]
            if 'не из Норвегии' in after_find:
                return fmt((russia + sweden) / total)
            elif 'не из России' in after_find:
                return fmt((norway + sweden) / total)
            elif 'из Норвегии или Швеции' in after_find:
                return fmt((norway + sweden) / total)
            elif 'из России' in after_find and 'или' not in after_find:
                return fmt(russia / total)
            elif 'из Норвегии' in after_find and 'или' not in after_find:
                return fmt(norway / total)
            elif 'из Швеции' in after_find and 'или' not in after_find:
                return fmt(sweden / total)
        # Fallback: check what's being asked
        if 'из Швеции' in text.split('Найдите')[-1] and 'или' not in text.split('Найдите')[-1] and 'не из' not in text.split('Найдите')[-1]:
            return fmt(sweden / total)

    # Cups (babushka)
    m = re.search(r'(\d+) чашек.*?(\d+) с красными цветами.*?остальные с синими', text)
    if m:
        total, red = int(m.group(1)), int(m.group(2))
        return fmt((total - red) / total)

    # Exam tickets
    m = re.search(r'(\d+) билет\w*.*?не выучил (\d+)', text)
    if m:
        total, not_learned = int(m.group(1)), int(m.group(2))
        return fmt((total - not_learned) / total)

    # Pens (канцтовары)
    m = re.search(r'продаётся (\d+) руч\w+.*?(\d+) красн\w+.*?(\d+) зелён\w+.*?(\d+) фиолетов\w+.*?остальные синие и чёрные.*?их поровну', text)
    if m:
        total = int(m.group(1))
        red, green, purple = int(m.group(2)), int(m.group(3)), int(m.group(4))
        blue_black = total - red - green - purple
        blue = blue_black // 2
        black = blue_black // 2

        # What's being asked?
        ask = text.split('Найдите')[-1] if 'Найдите' in text else text
        favorable = 0
        if 'красн' in ask:
            favorable += red
        if 'фиолетов' in ask:
            favorable += purple
        if 'син' in ask:
            favorable += blue
        if 'чёрн' in ask or 'черн' in ask:
            favorable += black
        if 'зелён' in ask or 'зелен' in ask:
            favorable += green
        return fmt(favorable / total)

    # Dice: sum equals specific values (two dice)
    m = re.search(r'(?:кость|кубик) бросают дважды|(?:кость|кубик) бросают два раза|(?:кость|кубик) бросают 2 раза', text)
    if m or ('бросают дважды' in text) or ('бросают два раза' in text) or ('бросают 2 раза' in text):
        # Check for "сумма равна X или Y"
        m2 = re.search(r'сумма.*?равна (\d+) или (\d+)', text)
        if m2:
            s1, s2 = int(m2.group(1)), int(m2.group(2))
            return fmt(dice_sum_counts({s1, s2}) / 36)

        # Check for "сумма чётна"
        if 'чётна' in text and 'нечётна' not in text:
            return fmt(0.5)

        # Check for "сумма нечётна"
        if 'нечётна' in text:
            return fmt(0.5)

        # Check for "наибольшее из двух выпавших чисел равно N"
        m2 = re.search(r'наибольшее.*?равно (\d+)', text)
        if m2:
            n = int(m2.group(1))
            # Count: (a,b) where max(a,b)=n = n^2 - (n-1)^2 = 2n-1
            count = 2 * n - 1
            return fmt(count / 36)

        # Check for "оба раза выпало число, большее N"
        m2 = re.search(r'оба раза.*?больш\w+ (\d+)', text)
        if m2:
            threshold = int(m2.group(1))
            favorable = 6 - threshold  # numbers > threshold
            return fmt((favorable ** 2) / 36)

        # Check for "оба раза выпало число, меньшее N"
        m2 = re.search(r'оба раза.*?меньш\w+ (\d+)', text)
        if m2:
            threshold = int(m2.group(1))
            favorable = threshold - 1  # numbers < threshold
            return fmt((favorable ** 2) / 36)

        # "хотя бы раз выпало число, большее N"
        m2 = re.search(r'хотя бы раз.*?больш\w+ (\d+)', text)
        if m2:
            threshold = int(m2.group(1))
            not_favorable = threshold  # numbers <= threshold
            return fmt(1 - (not_favorable / 6) ** 2)

        # "хотя бы раз выпало число, меньшее N"
        m2 = re.search(r'хотя бы раз.*?меньш\w+ (\d+)', text)
        if m2:
            threshold = int(m2.group(1))
            not_favorable = 6 - threshold + 1  # numbers >= threshold
            return fmt(1 - (not_favorable / 6) ** 2)

    # Demo dice: "сумма выпавших очков равна 9, 10 или 11" or "4, 5, 6 или 7"
    m = re.search(r'сумма выпавших очков равна ([\d,\s]+(?:или \d+)?)', text)
    if m and ('кубик' in text or 'кость' in text):
        nums_str = m.group(1)
        targets = set(map(int, re.findall(r'\d+', nums_str)))
        return fmt(dice_sum_counts(targets) / 36)

    # ===== Flashlights (statistical probability) =====
    m = re.search(r'из (\d+).*?фонарик\w*.*?(\w+) неисправн', text)
    if m:
        total = int(m.group(1))
        bad_word = m.group(2)
        bad = parse_russian_num(bad_word)
        if bad is None:
            bad = int(bad_word) if bad_word.isdigit() else None
        if bad is not None:
            return fmt((total - bad) / total)

    # ===== Pen writes badly (complement probability) =====
    m = re.search(r'равна (0[,.]\d+).*?пишет хорошо', text)
    if m:
        prob_bad = float(m.group(1).replace(',', '.'))
        return fmt(1 - prob_bad)

    # ===== BLOCK 2, ZADANIE 1 =====

    # Pies (пирожки)
    m = re.search(r'(\d+) с (\w+),\s*(\d+) с (\w+)\s+и\s+(\d+) с (\w+)', text)
    if m and 'пирожк' in text:
        counts = {m.group(2): int(m.group(1)), m.group(4): int(m.group(3)), m.group(6): int(m.group(5))}
        total = sum(counts.values())
        # Find which filling is being asked about
        ask = text.split('Найдите')[-1] if 'Найдите' in text else text
        for filling, count in counts.items():
            if filling[:4] in ask:
                return fmt(count / total)
        # Try matching the last word before the period
        for filling, count in counts.items():
            if filling[:3] in ask:
                return fmt(count / total)

    # Names drawing lots
    if 'бросили жребий' in text or 'бросили жребий' in text.lower():
        # Count people from names
        names_section = text.split('бросили жребий')[0] if 'бросили жребий' in text else text.split('бросили жребий')[0]
        # Extract names (capitalized words that are names)
        boy_names = {'Петя', 'Ваня', 'Игорь', 'Антон', 'Саша', 'Семён', 'Коля', 'Миша'}
        girl_names = {'Вика', 'Катя', 'Полина', 'Зоя', 'Лера', 'Даша', 'Наташа', 'Маша', 'Аня'}

        # Find all names in text before "бросили жребий"
        found_boys = 0
        found_girls = 0
        total_names = 0

        # Parse "Петя, Вика, Катя, Игорь, Антон, Полина"
        name_part = text.split('бросили жребий')[0]
        # Get capitalized words
        words = re.findall(r'[А-ЯЁ][а-яё]+', name_part)
        # Filter: skip the first word if it's like "Девятиклассники"
        names = []
        for w in words:
            if w in boy_names or w in girl_names:
                names.append(w)

        for n in names:
            total_names += 1
            if n in boy_names:
                found_boys += 1
            elif n in girl_names:
                found_girls += 1

        ask = text.split('Найдите')[-1] if 'Найдите' in text else text

        if 'мальчик' in ask:
            return fmt(found_boys / total_names)
        elif 'девочк' in ask:
            return fmt(found_girls / total_names)
        elif 'не Семён' in ask or 'не выпадет' in ask:
            # Probability NOT a specific person
            return fmt((total_names - 1) / total_names)

    # Three-digit numbers divisible by X
    m = re.search(r'трёхзначное число.*?делится на (\d+)', text)
    if m:
        divisor = int(m.group(1))
        # Three digit numbers: 100-999
        total = 900
        count = len([x for x in range(100, 1000) if x % divisor == 0])
        return fmt(count / total)

    # Coin flips
    m = re.search(r'монету бросают (дважды|трижды|четыре раза|2 раза|3 раза|4 раза).*?орел выпадет ровно (\d+) раз', text)
    if m:
        throws_word = m.group(1)
        k = int(m.group(2))
        throws_map = {'дважды': 2, 'трижды': 3, 'четыре раза': 4, '2 раза': 2, '3 раза': 3, '4 раза': 4}
        n = throws_map.get(throws_word, 2)
        return fmt(comb(n, k) / (2 ** n))

    # Single die problems
    if re.search(r'при бросании (?:игрального )?кубик\w', text):
        # "не большее 3" -> P(X <= 3) = 3/6
        m2 = re.search(r'не больш\w+ (\d+)', text)
        if m2:
            threshold = int(m2.group(1))
            return fmt(threshold / 6)

        # "не меньшее 1" -> P(X >= 1) = 6/6 = 1
        m2 = re.search(r'не меньш\w+ (\d+)', text)
        if m2:
            threshold = int(m2.group(1))
            return fmt((6 - threshold + 1) / 6)

        # "более 3 очков" -> P(X > 3) = 3/6
        m2 = re.search(r'более (\d+) очков', text)
        if m2:
            threshold = int(m2.group(1))
            return fmt((6 - threshold) / 6)

        # "менее 4 очков" -> P(X < 4) = 3/6
        m2 = re.search(r'менее (\d+) очков', text)
        if m2:
            threshold = int(m2.group(1))
            return fmt((threshold - 1) / 6)

        # "четное число очков"
        if 'четное' in text or 'чётное' in text:
            return fmt(0.5)

        # "нечетное число очков"
        if 'нечетное' in text or 'нечётное' in text:
            return fmt(0.5)

    # ===== BLOCK 2, ZADANIE 2: Statistical =====

    # "Из N пакетов K протекают"
    m = re.search(r'[Ии]з (\d+) пакетов.*?(\d+) протекают', text)
    if m:
        total, bad = int(m.group(1)), int(m.group(2))
        return fmt((total - bad) / total)

    # "Из N клавиатур K неисправны"
    m = re.search(r'[Ии]з (\d+) клавиатур.*?(\d+) не исправн', text)
    if m:
        total, bad = int(m.group(1)), int(m.group(2))
        return fmt((total - bad) / total)

    # "Из N дисков K не пригодны"
    m = re.search(r'[Ии]з (\d+).*?дисков.*?(\d+) не пригодн', text)
    if m:
        total, bad = int(m.group(1)), int(m.group(2))
        return fmt((total - bad) / total)

    # "Из каждых N лампочек K бракованных"
    m = re.search(r'[Ии]з каждых (\d+).*?лампочек.*?(\d+) бракованн', text)
    if m:
        total, bad = int(m.group(1)), int(m.group(2))
        return fmt((total - bad) / total)

    # "Из N аккумуляторов K заряжены ... не заряжен"
    m = re.search(r'[Ии]з каждых (\d+).*?аккумулятор\w+.*?(\d+) аккумулятор\w+ заряжен', text)
    if m:
        total, charged = int(m.group(1)), int(m.group(2))
        if 'не заряжен' in text.split('Найдите')[-1]:
            return fmt((total - charged) / total)
        return fmt(charged / total)

    # "В каждой N-ой банке" prize
    m = re.search(r'[Вв] каждой (\w+(?:\s+\w+)?) банке', text)
    if m:
        ordinal = m.group(1).lower()
        n = RUSSIAN_ORDINAL_NUMS.get(ordinal, None)
        if n is None:
            # Try parsing compound like "двадцать пятой"
            for key, val in RUSSIAN_ORDINAL_NUMS.items():
                if key in ordinal:
                    n = val
                    break
        if n is not None:
            prob_prize = 1 / n
            if 'не найд' in text:
                return fmt(1 - prob_prize)
            return fmt(prob_prize)

    # Baby gender problems: difference between frequency and probability
    m = re.search(r'вероятность.*?мальчиком.*?равна (0[,.]\d+).*?(\d+) девочек.*?частота.*?отличается', text)
    if m:
        prob_boy = float(m.group(1).replace(',', '.'))
        actual_girls = int(m.group(2))
        prob_girl = 1 - prob_boy
        freq_girl = actual_girls / 1000
        diff = abs(freq_girl - prob_girl)
        return fmt(diff)

    # Alternative baby pattern
    m = re.search(r'мальчиком.*?равна (0[,.]\d+).*?пришлось (\d+) девочек', text)
    if m:
        prob_boy = float(m.group(1).replace(',', '.'))
        actual_girls = int(m.group(2))
        prob_girl = round(1 - prob_boy, 10)
        freq_girl = actual_girls / 1000
        diff = abs(round(freq_girl - prob_girl, 10))
        return fmt(diff)

    # Coin experiment: "монету бросили 1000 раз, N раз выпал орел/решка"
    m = re.search(r'монету бросили (\d+) раз.*?(\d+) раз\w* выпал (\w+)', text)
    if m:
        total_throws = int(m.group(1))
        count_outcome = int(m.group(2))
        outcome = m.group(3)

        # What frequency are we asked about?
        ask = text.split('частота')[-1] if 'частота' in text else text

        if 'решк' in ask and 'орел' in outcome.lower() or 'орёл' in outcome.lower():
            # Told eagle count, asked about tails frequency
            tails_count = total_throws - count_outcome
            freq_tails = tails_count / total_throws
            diff = abs(freq_tails - 0.5)
            return fmt(diff)
        elif 'орл' in ask and 'решк' in outcome.lower():
            # Told tails count, asked about heads frequency
            heads_count = total_throws - count_outcome
            freq_heads = heads_count / total_throws
            diff = abs(freq_heads - 0.5)
            return fmt(diff)

    # Alternative coin experiment pattern
    m = re.search(r'монету бросили (\d+) раз.*?(\d+) раз\w* выпала решка.*?частота.*?орла', text)
    if m:
        total_throws = int(m.group(1))
        tails_count = int(m.group(2))
        heads_count = total_throws - tails_count
        freq_heads = heads_count / total_throws
        diff = abs(freq_heads - 0.5)
        return fmt(diff)

    # ===== BLOCK 2, ZADANIE 3: Formulas =====

    # Sum of probabilities: "Вероятность ... равна X. Вероятность ... равна Y. Найдите вероятность одной из двух тем"
    m = re.findall(r'равна (0[,.]\d+)', text)
    if len(m) == 2 and 'одной из этих двух тем' in text:
        p1 = float(m[0].replace(',', '.'))
        p2 = float(m[1].replace(',', '.'))
        return fmt(p1 + p2)

    # Shooter: "N раз стреляет, вероятность = P, первые K попал, последние M промахнулся"
    m = re.search(r'(\d+) раз\w* стреляет.*?равна (0[,.]\d+)', text)
    if m:
        total_shots = int(m.group(1))
        p_hit = float(m.group(2).replace(',', '.'))
        p_miss = round(1 - p_hit, 10)

        # Count hits and misses
        hits = 0
        misses = 0

        # Parse "первые K раза попал" and "последние M раза промахнулся"
        # or "первый раз попал" and "последние N раз промахнулся"
        m2 = re.search(r'перв\w+ (\d+) раз\w* попал', text)
        if m2:
            hits = int(m2.group(1))
        elif 'первый раз попал' in text:
            hits = 1

        m3 = re.search(r'последни\w+ (\d+) раз\w* промахнулс', text)
        if m3:
            misses = int(m3.group(1))

        m3b = re.search(r'последни\w+ два раза промахнулс', text)
        if m3b:
            misses = 2

        m3c = re.search(r'последни\w+ три раза промахнулс', text)
        if m3c:
            misses = 3

        if 'последний раз промахнулся' in text:
            misses = 1

        if hits + misses == total_shots:
            result = (p_hit ** hits) * (p_miss ** misses)
            return fmt(result)

    # ===== BLOCK 3: Exam variants =====

    # Class boys/girls
    m = re.search(r'учатся (\d+) мальчик\w* и (\d+) девоч\w*.*?мальчик', text)
    if m:
        boys, girls = int(m.group(1)), int(m.group(2))
        return fmt(boys / (boys + girls))

    # Tourist group
    m = re.search(r'(\d+) человек.*?выбирают (\w+) человек\w*.*?вероятность.*?пойдёт в магазин', text)
    if m:
        total = int(m.group(1))
        chosen_word = m.group(2)
        chosen = parse_russian_num(chosen_word)
        if chosen is None:
            chosen = int(chosen_word) if chosen_word.isdigit() else 3
        return fmt(chosen / total)

    # Alternative tourist pattern
    m = re.search(r'группе туристов (\d+) человек.*?выбирают (\w+) человек', text)
    if m:
        total = int(m.group(1))
        chosen_word = m.group(2)
        chosen = parse_russian_num(chosen_word)
        if chosen is None:
            chosen = int(chosen_word) if chosen_word.isdigit() else 3
        return fmt(chosen / total)

    # Exam tickets with topic
    m = re.search(r'(\d+) билет\w*.*?в (\d+) из них.*?вопрос по теме', text)
    if m:
        total, favorable = int(m.group(1)), int(m.group(2))
        return fmt(favorable / total)

    # Conference: days/talks
    m = re.search(r'в (\d+) дн\w*.*?(\d+) доклад\w*.*?первы\w+ дн\w+ .{0,5}(\d+) доклад\w*.*?остальные.*?поровну', text)
    if m:
        days = int(m.group(1))
        total = int(m.group(2))
        first_days_count = int(m.group(3))
        remaining = total - first_days_count
        last_day = remaining // (days - 1)
        if 'последний день' in text:
            return fmt(last_day / total)

    # Conference 4 days pattern
    m = re.search(r'в (\d+) дн\w*.*?(\d+) доклад\w*.*?первые два дня .{0,5} по (\d+) доклад\w*.*?остальные.*?поровну', text)
    if m:
        days = int(m.group(1))
        total = int(m.group(2))
        first_two_per_day = int(m.group(3))
        remaining = total - 2 * first_two_per_day
        last_day = remaining // (days - 2)
        if 'последний день' in text:
            return fmt(last_day / total)

    # Olympiad: auditoriums
    m = re.search(r'первых двух по (\d+) человек.*?запасную.*?всего.*?(\d+) участник\w*', text)
    if m:
        per_room = int(m.group(1))
        total = int(m.group(2))
        reserve = total - 2 * per_room
        return fmt(reserve / total)

    # Tea: "чёрного в N раз больше зелёного"
    m = re.search(r'чёрн\w+ чаем? в (\d+) раз\w* больше.*?чёрн\w+ чаем?', text)
    if m:
        ratio = int(m.group(1))
        # black = ratio * green, total = (ratio+1)*green
        return fmt(ratio / (ratio + 1))

    # Tea: alternative "зеленого в N раз меньше черного"
    m = re.search(r'зелен\w+ чаем? в (\d+) раз\w* меньше.*?чёрн\w+', text)
    if m:
        ratio = int(m.group(1))
        # green = black/ratio, black/total = ratio/(ratio+1)
        return fmt(ratio / (ratio + 1))

    # More general tea pattern
    if 'чайные пакетики' in text:
        m = re.search(r'в (\d+) раз\w* больше', text)
        if m:
            ratio = int(m.group(1))
            ask = text.split('вероятность')[-1] if 'вероятность' in text else text
            if 'черн' in ask or 'чёрн' in ask:
                # If black is N times more than green: P(black) = N/(N+1)
                if 'чёрн' in text.split('в')[0] or 'черн' in text.split('в')[0]:
                    return fmt(ratio / (ratio + 1))
                # "пакетиков с чёрным чаем в 4 раза больше"
                if re.search(r'чёрн\w+.*?в \d+ раз', text):
                    return fmt(ratio / (ratio + 1))
            elif 'гус' in ask:
                return fmt(1 / (ratio + 1))
        m = re.search(r'в (\d+) раз\w* меньше', text)
        if m:
            ratio = int(m.group(1))
            ask = text.split('вероятность')[-1] if 'вероятность' in text else text
            if 'черн' in ask or 'чёрн' in ask:
                return fmt(ratio / (ratio + 1))

    # Farm: "кур в N раз больше гусей"
    m = re.search(r'кур в (\d+) раз\w* больше.*?гус\w*', text)
    if m:
        ratio = int(m.group(1))
        ask = text.split('вероятность')[-1] if 'вероятность' in text else text
        if 'гус' in ask:
            return fmt(1 / (ratio + 1))
        return fmt(ratio / (ratio + 1))

    # Chess/Tennis tournament: pairing
    m = re.search(r'(\d+) спортсмен\w*.*?(\d+) спортсмен\w* из Росс\w+.*?в том числе', text)
    if m:
        total = int(m.group(1))
        from_russia = int(m.group(2))
        ask = text.split('вероятность')[-1] if 'вероятность' in text else text
        if 'не из России' in ask:
            # P(paired with non-Russian) = (total - from_russia) / (total - 1)
            return fmt((total - from_russia) / (total - 1))
        else:
            # P(paired with another Russian) = (from_russia - 1) / (total - 1)
            return fmt((from_russia - 1) / (total - 1))

    # Round table: 2 girls neighbors
    m = re.search(r'круглый стол на (\d+) стул\w*.*?(\d+) мальчик\w* и (\d+) девочк', text)
    if m:
        chairs = int(m.group(1))
        boys = int(m.group(2))
        girls = int(m.group(3))
        # Total ways to place on round table: C(chairs, girls) * ...
        # Actually for circular: total ways to choose 2 seats out of N in circle
        # For 2 girls on N chairs (round table):
        # Total pairs of seats: C(N, 2)
        # Adjacent pairs: N (circular)
        n = chairs
        total_pairs = comb(n, 2)
        adjacent_pairs = n

        if 'не окажутся на соседних' in text:
            return fmt(1 - adjacent_pairs / total_pairs)
        else:
            return fmt(adjacent_pairs / total_pairs)

    # Conditional probability: dice sum > 8, P(second = K)
    m = re.search(r'сумма.*?больше (\d+).*?втором броске выпало (\d+)', text)
    if m:
        min_sum = int(m.group(1))
        second_val = int(m.group(2))
        # Count outcomes where sum > min_sum
        total_favorable = 0
        target_favorable = 0
        for a in range(1, 7):
            for b in range(1, 7):
                if a + b > min_sum:
                    total_favorable += 1
                    if b == second_val:
                        target_favorable += 1
        return fmt(target_favorable / total_favorable)

    # Festival: P(France after both UK and Russia)
    # = 1/3! of the 3 countries' orderings where France is last = 2/6 = 1/3
    if 'фестивале' in text.lower() and 'будет выступать после' in text:
        # P(country X after both Y and Z) = P(X is last among 3) = 1/3
        result = round(1/3, 2)
        return str(result)

    if 'фестивале' in text.lower() and 'будет выступать до' in text:
        # P(country X before both Y and Z) = P(X is first among 3) = 1/3
        result = round(1/3, 2)
        return str(result)

    # ===== BLOCK 3, ZADANIE 2: Factory bags =====
    m = re.search(r'[Ии]з (\d+) сумок.*?(\d+) сум\w+ имеют.*?дефект', text)
    if m:
        total, bad = int(m.group(1)), int(m.group(2))
        if 'без дефектов' in text:
            return fmt((total - bad) / total)

    # ===== BLOCK 3, ZADANIE 3: Printer/scanner lifespan =====
    m = re.search(r'прослужит больше года.*?равна (0[,.]\d+).*?два года.*?(0[,.]\d+).*?меньше двух лет.*?не менее года', text)
    if m:
        p_more_1 = float(m.group(1).replace(',', '.'))
        p_more_2 = float(m.group(2).replace(',', '.'))
        # P(1 <= T < 2) = P(T >= 1) - P(T >= 2) = p_more_1 - p_more_2
        return fmt(p_more_1 - p_more_2)

    return None


def process_all():
    with open(INPUT_FILE, 'r', encoding='utf-8') as f:
        data = json.load(f)

    total_tasks = 0
    solved = 0
    unsolved = []

    for block in data['blocks']:
        block_num = block['number']
        for zadanie in block['zadaniya']:
            zadanie_num = zadanie['number']
            instruction = zadanie.get('instruction', '')
            for task in zadanie['tasks']:
                total_tasks += 1
                answer = solve_task(block_num, zadanie_num, task, instruction)
                if answer is not None:
                    task['answer'] = answer
                    solved += 1
                    print(f"  B{block_num}/Z{zadanie_num}/T{task['id']}: {answer}")
                else:
                    unsolved.append((block_num, zadanie_num, task['id'], task['text'][:80]))
                    print(f"  B{block_num}/Z{zadanie_num}/T{task['id']}: *** UNSOLVED ***")

    print(f"\n{'='*60}")
    print(f"Total tasks: {total_tasks}")
    print(f"Solved: {solved}")
    print(f"Unsolved: {total_tasks - solved}")

    if unsolved:
        print(f"\nUnsolved tasks:")
        for b, z, tid, txt in unsolved:
            print(f"  B{b}/Z{z}/T{tid}: {txt}...")

    # Save back
    with open(INPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)

    print(f"\nFile saved to {INPUT_FILE}")


if __name__ == '__main__':
    process_all()
