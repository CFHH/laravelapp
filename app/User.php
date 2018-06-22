<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Extensions\Eloquent\CachableModel;  //use CachableModel

/**
 * 缓存模型使用修改：
 * 1、尽量使用find()来查找，不用where来build然后查找
 * 2、绝对不使用where进行数据表的更新和删除，使用create()、update()、delete()增删改
 * 3、根据合理假设评估缓存时效
 * 4、在Model的$fillable里列出数据库表所有字段
 * 5、所有时间在PHP代码中指定，不由数据库自己设置，包括created_at和updated_at
 *
 * 查询优化和兼容mongodb需要修改
 * 1、只能用where进行查询的，mysql需要索引
 * 2、全部显式指定主键类型$keyType
 * 3、不依赖自增ID，比如不能假设自增的主键是数字
 * 4、不能使用数据库事务
 * 5、不能使用关系型数据库特有的功能，比如联合查询
 */
class User extends Authenticatable
{
    use Notifiable, HasApiTokens, CachableModel;

    protected $primaryKey = 'id_crc64';
    protected $keyType = 'int';  //如果主键是整数，mongo必须设置
    public $incrementing = false;
    protected $cache_expire_sceonds = 3600;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_crc64', 'email', 'name', 'password', 'created_at', 'updated_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function getKeyName()  //这个应该不需要，Model里默认返回$primaryKey了
    {
        return 'id_crc64';
    }

    public function findForPassport($username)
    {
        //return $this->where('id_crc64', $username)->first();
        return $this->find($username);
    }

    /*
    public function validateForPassportPasswordGrant($password)
    {
        //自定义密码验证
    }
    */

    public const AccessTokenCacheKey_ExpireSceonds = 604800;
    
    static public function getAccessTokenCacheKey($userid)
    {
        $name = 'UserToAccessToken';
        return "{$name}:{$userid}";
    }
}
