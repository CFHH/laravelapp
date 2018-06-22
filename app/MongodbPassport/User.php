<?php

namespace App\MongodbPassport;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * 兼容mongodb注意事项：
 * 1、mysql默认主键是"id"，mongodb默认主键是"_id"；如果把MySQL数据迁移到mongo，那可能需要显示指定主键是"id"，因为原来的代码对"id"可能有依赖
 * 2、显式指定主键类型$keyType，mysql默认是"int"，mongodb默认是"string"
 * 3、不能使用数据库事务，mongodb不支持
 * 4、不能使用关系型数据库特有的功能，比如join、union等，mongodb不支持
 * 5、不能依赖自增ID啊，如果已经依赖了，那基本需要在mongodb中增加一列（原mysql自增主键列）并且以后显示指定键值
 * 6、索引，mongo和mysql都需要
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;
}
