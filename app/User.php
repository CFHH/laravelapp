<?php

namespace App;

use Illuminate\Notifications\Notifiable;
//use Illuminate\Foundation\Auth\User as Authenticatable; //mysql
use DesignMyNight\Mongodb\Auth\User as Authenticatable;  //mongo

class User extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'id_crc64';
    protected $keyType = 'int';  //mongo需要设置
    public $incrementing = false; //mysql需要设置

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_crc64', 'email', 'name', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function findForPassport($username)
    {
        return $this->where('id_crc64', $username)->first();
    }

    public function getKeyName()
    {
        return 'id_crc64';
    }
}
