<?php

namespace App\Extensions\Eloquent;

use Redis;

trait CachableModel
{
    private static $DEFAULT_CACHE_EXPIRE_SECONDS = 3600;

    public static $CACHE_FLAG_NOCACHE = 0;
    public static $CACHE_FLAG_FROM_CACHE = 1;
    public static $CACHE_FLAG_FROM_DB = 2;
    public static $CACHE_FLAG_NEW = 4;
    public static $CACHE_FLAG_UPDATE_CACHE = 8;
    public static $CACHE_FLAG_DELETED = 16;
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

    public static function __callStatic($method, $parameters)
    {
        if ($method == 'find')
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
                $obj = (new static)->$method(...$parameters);
                if ($obj != null)
                {
                    //var_dump(__CLASS__ . '::__callStatic');
                    self::cacheObject($obj, $key);
                    $obj->cache_flag = self::$CACHE_FLAG_FROM_DB;
                }
                return $obj;
            }
        }
        /*
        else if($method == 'create')
        {
            // Model::create()第1步，会经过这里，然后调用__call
            //var_dump($parameters);
        }
        */
        else if($method == 'findNoCache')
        {
            $method = 'find';
        }
        return (new static)->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if ($method == 'find')
        {
            $key = static::getCacheKey($parameters[0]);
            $cache = Redis::get($key);
            if ($cache != null)
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
                if ($obj != null)
                {
                    //var_dump(__CLASS__ . '::__call');
                    self::cacheObject($obj, $key);
                    $obj->cache_flag = self::$CACHE_FLAG_FROM_DB;
                }
                return $obj;
            }
        }
        else if($method == 'create')
        {
            // Model::create()第2步，会经过这里，然后调用save
            //var_dump($parameters);
            $obj = parent::__call($method, $parameters);
            if ($obj != null)
            {
                $id = $obj->attributes[$obj->primaryKey];
                $key = static::getCacheKey($id);
                self::cacheObject($obj, $key);
                $obj->cache_flag = self::$CACHE_FLAG_NEW;
            }
            return $obj;
        }
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
        if ($exists && $result)
        {
            //var_dump(__CLASS__ . '::save');
            $id = $this->attributes[$this->primaryKey];
            $key = static::getCacheKey($id);
            self::cacheObject($this, $key);
            $this->cache_flag = $this->cache_flag | self::$CACHE_FLAG_UPDATE_CACHE;
        }
        /*
        if (!$exists)
        {
            // Model::create()第3步，会经过这里
            //var_dump($this);
        }
        */
        return $result;
    }

    public function delete()
    {
        $result = parent::delete();
        if ($result)
        {
            $id = $this->attributes[$this->primaryKey];
            $key = static::getCacheKey($id);
            Redis::del($key);
            $this->cache_flag = $this->cache_flag | self::$CACHE_FLAG_DELETED;
        }
        return $result;
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
