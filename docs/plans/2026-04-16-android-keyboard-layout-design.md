# Android Keyboard Layout Design

## Goal

Убрать принудительное переключение Android-клавиатуры на английскую/цифровую раскладку при фокусе на полях ответа в student test-экранах.

## Decision

Используем единый нейтральный режим ввода для student test-страниц:
- не просим у браузера числовую клавиатуру через `inputmode="numeric"`
- сохраняем `type="text"` для всех ответов
- применяем одинаковое поведение в ОГЭ, ЕГЭ и ВПР

## Why

На Android `inputmode="numeric"` часто вызывает клавиатуру без русского ввода или с навязчивым переключением типа клавиатуры. Для учеников это хуже, чем потеря “удобной” цифровой клавиатуры, потому что часть ответов содержит не только цифры, но и запятые, минусы, буквы и составные значения.

## Scope

- `resources/views/pwa/student/vpr-test.blade.php`
- `resources/views/pwa/student/test.blade.php`
- `resources/views/pwa/student/ege-test.blade.php`
- регрессионный feature test для шаблонов
