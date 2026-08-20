<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Вводный текст практико-ориентированного блока (задания 1–5).
 *
 * @property string $bank
 * @property string $guid
 * @property string $html
 * @property array<int, string>|null $images
 */
class TaskIntro extends Model
{
    protected $fillable = ['bank', 'guid', 'html', 'images'];

    protected $casts = ['images' => 'array'];
}
