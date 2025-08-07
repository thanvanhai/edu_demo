<?php

namespace App\Models\NhanSu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    //protected $connection = 'admin';
    protected $table = 'department';

    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

}
