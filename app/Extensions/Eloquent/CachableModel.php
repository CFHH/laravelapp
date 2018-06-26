<?php

namespace App\Extensions\Eloquent;

use Redis;

/**
 * 缓存模型注意事项：
 * 1、使用create()或save()来创建，都可以
 * 2、尽量使用find()来查找；不用where()来build然后查找，这种方式不经过缓存
 * 3、绝对不使用where来对缓存的表进行更新和删除，使用delete()、update()；如果你必须使用where来改和查，那么这所有的model就不可以缓存
 * 4、在Model的$fillable里列出数据库表所有字段，否则无法从缓存恢复完整数据；或者使用protected $guarded = []，表示全部可以赋值
 * 5、Model的$cache_expire_sceonds，根据实际使用情景合理假设评估缓存时效
 * 6、所有时间在PHP代码中指定，不由数据库自己设置，包括created_at和updated_at
 */
trait CachableModel
{
    private static $ENABLE_CACHE = true;
    private static $DEFAULT_CACHE_EXPIRE_SECONDS = 3600;

    public static $CACHE_FLAG_NOCACHE = 0;
    public static $CACHE_FLAG_FROM_DB = 1;
    public static $CACHE_FLAG_FROM_CACHE = 2;
    public static $CACHE_FLAG_NEW = 4;
    public static $CACHE_FLAG_UPDATE_CACHE = 8;
    public static $CACHE_FLAG_DELETED = 16;
    protected $cache_flag = 0;

    public function getCacheFlag()
    {
        return $this->cache_flag;
    }

    public static function getCacheKey($id)
    {
        $name = str_replace('\\', ':', __CLASS__);
        return "{$name}:{$id}";
    }

    private static function cacheObject($obj, $key)
    {
        if(property_exists($obj , 'cache_expire_sceonds'))
            $cache_expire_sceonds = $obj->cache_expire_sceonds;
        else
            $cache_expire_sceonds = self::$DEFAULT_CACHE_EXPIRE_SECONDS;
        if ($cache_expire_sceonds > 0)
            Redis::setex($key, $cache_expire_sceonds, $obj->toJsonEx());
        else
            Redis::set($key, $obj->toJsonEx());
    }

    /*
     * __callStatic的默认实现是：return (new static)->$method(...$parameters);
     * 也就是创建一个Model，然后调用method，如果不存在，就调用__call了
     * 所以不需要修改__callStatic这个函数
    public static function __callStatic($method, $parameters)
    {
        if ($method == 'find')
        {
            // Model::find()第1步，会经过这里，然后调用__call
        }
        else if($method == 'create')
        {
            // Model::create()第1步，会经过这里，然后调用__call
            //var_dump($parameters);
        }
        else if($method == 'findNoCache')
        {
            $method = 'find';
        }
        return (new static)->$method(...$parameters);
    }
    */

    public function __call($method, $parameters)
    {
        if ($method == 'find')
        {
            if (!self::$ENABLE_CACHE)
                return parent::__call($method, $parameters);
            if (is_array($parameters[0]))
            {
                $query_ids = $parameters[0];
                $query_count = count($query_ids);
                $redis_keys = [];
                for ($i = 0; $i < $query_count; ++$i)
                    $redis_keys[] = static::getCacheKey($query_ids[$i]);
                $cache_objs = Redis::mget($redis_keys);
                $db_ids = [];
                $db_indexes = [];
                $objs = [];
                if ($cache_objs == null)
                {
                    for ($i = 0; $i < $query_count; ++$i)
                    {
                        $db_ids[] = $query_ids[$i];
                        $db_indexes[] = $i;
                        $objs[] = null;
                    }
                }
                else
                {
                    for ($i = 0; $i < $query_count; ++$i)
                    {
                        if ($cache_objs[$i] == null)
                        {
                            $db_ids[] = $query_ids[$i];
                            $db_indexes[] = $i;
                            $objs[] = null;
                        }
                        else
                        {
                            $obj = new static(json_decode($cache_objs[$i], true));
                            $obj->syncOriginal();
                            $obj->exists = true;
                            $obj->cache_flag = self::$CACHE_FLAG_FROM_CACHE;
                            $objs[] = $obj;
                        }
                    }
                }
                if (count($db_ids) == 0)
                    return $objs;
                $parameters[0] = $db_ids;
                $db_objs = parent::__call($method, $parameters);  //这里数据库只返回存在的
                if ($db_objs == null)
                    return $objs;
                $db_query_count = count($db_ids);
                $db_return_count = count($db_objs);
                for ($i = 0; $i < $db_return_count; ++$i)
                {
                    $db_obj = $db_objs[$i];
                    $id = $db_obj->attributes[$db_obj->primaryKey];
                    for ($j = 0; $j < $db_query_count; ++$j )
                    {
                        if ($id != $db_ids[$j])
                            continue;
                        $index = $db_indexes[$j];
                        self::cacheObject($db_obj, $redis_keys[$index]);
                        $db_obj->cache_flag = self::$CACHE_FLAG_FROM_DB;
                        $objs[$index] = $db_obj;
                        break;
                    }
                }
                return $objs;
            }
            else
            {
                $key = static::getCacheKey($parameters[0]);
                $cache = Redis::get($key);
                if ($cache != null)
                {
                    $obj = new static(json_decode($cache, true));
                    $obj->syncOriginal();
                    $obj->exists = true;
                    $obj->cache_flag = self::$CACHE_FLAG_FROM_CACHE;
                    return $obj;
                }
                else
                {
                    $obj = parent::__call($method, $parameters);
                    if ($obj != null)
                    {
                        self::cacheObject($obj, $key);
                        $obj->cache_flag = self::$CACHE_FLAG_FROM_DB;
                    }
                    return $obj;
                }
            }
        }
        /*
        else if($method == 'create')
        {
            // Model::create()第2步，会经过这里，然后调用save
            //var_dump($parameters);
        }
        */
        else if($method == 'findNoCache')
        {
            $method = 'find';
        }
        return parent::__call($method, $parameters);
    }

    public function save(array $options = [])
    {
        $exists = $this->exists;
        $result = parent::save($options);
        if ($result && self::$ENABLE_CACHE)
        {
            //var_dump(__CLASS__ . '::save');
            $id = $this->attributes[$this->primaryKey];  //通过save来创建，如果是自增主键，此处有主键了
            $key = static::getCacheKey($id);
            self::cacheObject($this, $key);
            if ($exists)
            {
                $this->cache_flag = $this->cache_flag | self::$CACHE_FLAG_UPDATE_CACHE;
            }
            else
            {
                // Model::create()第3步，会经过这里
                // 通过save来创建，也是到这里
                //var_dump($this);
                $this->cache_flag = self::$CACHE_FLAG_NEW;
            }
        }
        return $result;
    }

    public function delete()
    {
        $result = parent::delete();
        if ($result && self::$ENABLE_CACHE)
        {
            $id = $this->attributes[$this->primaryKey];
            $key = static::getCacheKey($id);
            Redis::del($key);
            $this->cache_flag = $this->cache_flag | self::$CACHE_FLAG_DELETED;
        }
        return $result;
    }

    /*
    Model::destroy的实现是把模型全部从DB拉出来，然后删除，应该修改
    Model::destroy(1);
    Model::destroy([1, 2, 3]);
    Model::destroy(1, 2, 3);
    */
    public static function destroy($ids)
    {
        if (!self::$ENABLE_CACHE)
            return parent::destroy($ids);
        $ids = is_array($ids) ? $ids : func_get_args();
        $query_count = count($ids);
        $key = ($instance = new static)->getKeyName();
        $del_count = $instance->whereIn($key, $ids)->delete();
        $redis_keys = [];
        for ($i = 0; $i < $query_count; ++$i)
            $redis_keys[] = static::getCacheKey($ids[$i]);
        Redis::del($redis_keys);
        return $del_count;
    }

    public function toJsonEx($options = 0)
    {
        $this->all_arrayable = true;
        $json = json_encode($this->toArray(), $options);
        $this->all_arrayable = false;
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }
        return $json;
    }
}
