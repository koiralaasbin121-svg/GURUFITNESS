<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Main extends Model
{
    use HasFactory;
    // protected $fillable = ['title', 'tags', 'companyName', 'location', 'email', 'website', 'description'];

    public function scopeFilter($query, array $filters)
    {
        if ($filters['tag'] ?? false) {
            $query->where('tags', 'like', '%' . request('tag') . '%');
        }
        if ($filters['search'] ?? false) {
            $query->where('tags', 'like', '%' . request('search') . '%')
                ->orWhere('title', 'like', '%' . request('search') . '%')
                ->orWhere('location', 'like', '%' . request('search') . '%');
        }
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
