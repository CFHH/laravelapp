<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User2 extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $primaryKey = 'id_crc64';
    protected $keyType = 'int';  //如果主键是整数，mongo必须设置
    public $incrementing = false;

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
