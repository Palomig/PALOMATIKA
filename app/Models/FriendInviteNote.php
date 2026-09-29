<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Отметка ученика «кого позвал»: заметка с датой, на выплату не влияет. */
class FriendInviteNote extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'name'];
}
