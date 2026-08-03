<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];

    // protected $casts = [
    //     'password' => 'hashed', // auto-hash saat $user->password = '...' lalu save()
    // ];

    public function timKerja()
    {
        return $this->belongsToMany(TimKerja::class, 'user_tim_kerja');
    }
}
