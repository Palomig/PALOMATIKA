<?php

return [
    /*
     * Иллюстрации вводных текстов лежат рядом с выгрузкой банка ФИПИ,
     * а не в базе: это десятки мегабайт растра, которым в MySQL делать нечего.
     */
    'assets_url' => env('PRINT_ASSETS_URL', 'https://palomig.ru/fipi-bank-export'),
    'assets_cache' => env('PRINT_ASSETS_CACHE', storage_path('app/print-assets')),
];
