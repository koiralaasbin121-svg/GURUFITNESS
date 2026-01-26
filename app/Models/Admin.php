<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Admin extends Authenticatable
{
    use HasFactory;
    public function scopeFilter($query,array $filters)
    {
        if($filters['search'] ?? false){
            $query
            ->where('fullName','like','%'.$filters['search'].'%')
            ->orWhere('username','like','%'.$filters['search'].'%')
            ->orWhere('role','like','%'.$filters['search'].'%');
        }
    }

}
