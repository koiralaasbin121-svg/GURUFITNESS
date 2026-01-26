<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'user_id', 'id');
    }
}
