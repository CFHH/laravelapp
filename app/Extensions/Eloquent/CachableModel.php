<?php

namespace App\Extensions\Eloquent;

use Redis;

trait CachableModel
{
    private static $DEFAULT_CACHE_EXPIRE_SECONDS = 3600;

    public static $CACHE_FLAG_NOCACHE = 0;
    public static $CACHE_FLAG_FROM_CACHE = 1;
    public static $CACHE_FLAG_NOW_CACHED = 2;
    protected $cache_flag = 0;

    public function getCacheFlag()
    {
        return $this->cache_flag;
    }

    private static function getCacheKey($id)
    {
        $name = str_replace('\\', ':', __CLASS__);
        return "{$name}:{$id}";
    }

    public static function __callStatic($method, $parameters)
    {
        if ($method == 'find')
        {
            $key = static::getCacheKey($parameters[0]);
            $cache = Redis::get($key);
            if (!is_null($cache))
            {
                $obj = new static(json_decode($cache, true));
                $obj->syncOriginal();
                $obj->exists = true;
                $obj->cache_flag = self::$CACHE_FLAG_FROM_CACHE;
                return $obj;
            }
            else
            {
                $obj = (new static)->$method(...$parameters);
                if (null == $obj)
                {
                    return null;
                }
                else
                {
                    if(property_exists($obj , 'cache_expire_sceonds'))
                        $cache_expire_sceonds = $obj->cache_expire_sceonds;
                    else
                        $cache_expire_sceonds = self::$DEFAULT_CACHE_EXPIRE_SECONDS;
                    if ($cache_expire_sceonds > 0)
                        Redis::setex($key, $cache_expire_sceonds, $obj->toJsonEx());
                    else
                        Redis::set($key, $obj->toJsonEx());
                    $obj->cache_flag = self::$CACHE_FLAG_NOW_CACHED;
                    return $obj;
                }
            }
        }
        else if($method == 'findNoCache')
        {
            $method = 'find';
            return (new static)->$method(...$parameters);
        }
        return (new static)->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if ($method == 'find')
        {
            $key = static::getCacheKey($parameters[0]);
            $cache = Redis::get($key);
            if (!is_null($cache))
            {
                $attributes =json_decode($cache, true);
                $this->fill($attributes);
                $this->syncOriginal();
                $this->exists = true;
                $this->cache_flag = self::$CACHE_FLAG_FROM_CACHE;
                return $this;
            }
            else
            {
                $obj = parent::__call($method, $parameters);
                if (null == $obj)
                {
                    return null;
                }
                else
                {
                    if(property_exists($obj , 'cache_expire_sceonds'))
                        $cache_expire_sceonds = $obj->cache_expire_sceonds;
                    else
                        $cache_expire_sceonds = self::$DEFAULT_CACHE_EXPIRE_SECONDS;
                    if ($cache_expire_sceonds > 0)
                        Redis::setex($key, $cache_expire_sceonds, $obj->toJsonEx());
                    else
                        Redis::set($key, $obj->toJsonEx());
                    $obj->cache_flag = self::$CACHE_FLAG_NOW_CACHED;
                    return $obj;
                }
            }
        }
        else if($method == 'findNoCache')
        {
            $method = 'find';
        }
        return parent::__call($method, $parameters);
    }

    public function save(array $options = [])
    {
        $id = $this->attributes[$this->primaryKey];
        $key = static::getCacheKey($id);
        if(property_exists($this , 'cache_expire_sceonds'))
            $cache_expire_sceonds = $this->cache_expire_sceonds;
        else
            $cache_expire_sceonds = self::$DEFAULT_CACHE_EXPIRE_SECONDS;
        if ($cache_expire_sceonds > 0)
            Redis::setex($key, $cache_expire_sceonds, $this->toJsonEx());
        else
            Redis::set($key, $this->toJsonEx());
        return parent::save($options);
    }

    public function delete()
    {
        $id = $this->attributes[$this->primaryKey];
        $key = static::getCacheKey($id);
        Redis::del($key);
        return parent::delete();
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