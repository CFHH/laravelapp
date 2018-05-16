<?php
namespace App\Model;
use Jenssegers\Mongodb\Eloquent\Model as MongoEloquent;

class MongoUser extends MongoEloquent
{
	protected $connection = 'mongodb';
	protected $collection = 'MongoUsers';
	protected $primaryKey = 'name';
	public $incrementing = false;

	protected $fillable = [
        'name', 'age', 'password',
    ];

    protected $hidden = [
        'password',
    ];

}