<?php
use Illuminate\Http\Request;
use App\Model\MongoUser;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    //return view('welcome');
    echo 'Hello, world!';
});

Route::get('/test', function ()
{
    echo 'get web test';
});

/*
|--------------------------------------------------------------------------
| 测试mongodb
|--------------------------------------------------------------------------
*/
Route::get('/mongo_insert', function (Request $request)
{
	$data = $request->all();
	$validator = Validator::make($data, [
		'name' => 'required|string',
		'age' => 'required|integer',
	]);
	if ($validator->fails())
	{
		echo "参数错误";
		return;
	}
	DB::connection('mongodb')->collection('tests')->insert([
    	'name' => $data['name'],
    	'age' => $data['age']
    ]);
    echo 'success';
});

Route::get('/mongo_query', function (Request $request)
{
	$data = $request->all();
	$validator = Validator::make($data, [
		'name' => 'required|string',
	]);
	if ($validator->fails())
	{
	    $json_text = DB::connection('mongodb')->collection('tests')->get();
	    //echo $json_text;
	    $objs = json_decode($json_text);
	    foreach ($objs as $obj)
	    {
	    	echo $obj->{'name'} . " : ";
	    	var_dump($obj);
	    	echo "<br/>";
	    }
	}
	else
	{
		$objs = DB::connection('mongodb')->collection('tests')->where("name", "=", $data['name'])->get();
		foreach ($objs as $obj)
		{
		    echo $obj["name"] . " : ";
	    	var_dump($obj);
	    	echo "<br/>";
		}
	}
});

Route::get('/mongouser_add', function (Request $request)
{
	$data = $request->all();
	$validator = Validator::make($data, [
		'name' => 'required|string',
		'age' => 'required|integer',
		'password' => 'required|string',
	]);
	if ($validator->fails())
	{
		echo "参数错误";
		return;
	}
	$result = MongoUser::create([
		'name' => $data['name'],
		'age' => $data['age'],
		'password' => bcrypt($data['password']),
	]);
	echo 'success';
});

Route::get('/mongouser_query', function (Request $request)
{
	$data = $request->all();
	$validator = Validator::make($data, [
		'name' => 'required|string',
	]);
	if ($validator->fails())
	{
		$users = MongoUser::get();
		foreach ($users as $user)
		{
			echo $user->name . " : ";
			echo $user . "<br/>";
		}		
	}
	else
	{
		$users = MongoUser::where('name', $data['name'])->get();
		foreach ($users as $user)
		{
			echo $user->name . " : ";
			echo $user . "<br/>";
			//$user->age = 18;
			//$user->save();
		}		
	}
});

/*
|--------------------------------------------------------------------------
| 测试redis
|--------------------------------------------------------------------------
*/
Route::get('/setredis', function (Request $request)
{
	$data = $request->all();
	$validator = Validator::make($data, [
		'key' => 'required|string',
		'value' => 'required|string',
	]);
	if ($validator->fails())
	{
		echo "参数错误";
		return;
	}
    Redis::set($data['key'], $data['value']);
});

Route::get('/getredis', function (Request $request)
{
	$data = $request->all();
	$validator = Validator::make($data, [
		'key' => 'required|string',
	]);
	if ($validator->fails())
	{
		echo "参数错误";
		return;
	}
    echo Redis::get($data['key']);
});

Route::get('/redis_set', function ()
{
	$redis = app('redis.connection');
	$redis->sadd('set1', 'ab');
	$redis->sadd('set1', 'cd');
	$redis->sadd('set1', 'ef');
	echo "set1: " . json_encode($redis->smembers('set1')) . "<br/>";

	$redis->sadd('set2', 'ab');
	$redis->sadd('set2', 'uv');
	$redis->sadd('set2', 'xy');
	echo "set2: " . json_encode($redis->smembers('set2')) . "<br/>";

	echo "set_inter: " . json_encode($redis->sinter('set1', 'set2')) . "<br/>";
	echo "set_union: " . json_encode($redis->sunion('set1', 'set2')) . "<br/>";
	echo "set_diff: " . json_encode($redis->sdiff('set1', 'set2')) . "<br/>";

	$redis->del('set1');
	echo "after del, set1: " . json_encode($redis->smembers('set1')) . "<br/>";

	echo "set_donot_exist: " . json_encode($redis->smembers('set_donot_exist')) . "<br/>";
});

/*
|--------------------------------------------------------------------------
| 安全性
|--------------------------------------------------------------------------
*/
Route::get('/xss_test', function ()
{
	$input = "<p><script>alert('Laravel);</script></p>";
	$content = htmlentities($input, ENT_QUOTES, 'UTF-8');
	echo $content;
});

Route::get('/sql_test', function ()
{
	//$sql = 'UPDATE users SET password = "hahaha111" WHERE id_crc64 = -7858747494977956003';
	//$sql = sprintf('UPDATE users SET password = "%s" WHERE id_crc64 = -7858747494977956003', $password);
	//$sql = sprintf('UPDATE users SET password = "%s" WHERE id_crc64 = %d', $password, $crc);

	$crc = \CRC::crc64('zzw@163.com');
	$inject_crc = "-7858747494977956003 or 1 = 1";
	$password = 'haha666';

	$sql_injection = false;
	$pdo = true;
	if ($sql_injection)
	{
		//sql注入
		$sql = sprintf('UPDATE users SET password = "%s" WHERE id_crc64 = %s', $password, $inject_crc);
		DB::statement($sql);
		echo $sql;
	}
	else if ($pdo)
	{
		//使用PDO查询
		$sql = sprintf('UPDATE users SET password = ? WHERE id_crc64 = ?');
		DB::statement($sql, array($password, $inject_crc));
	}
	else
	{
		//使用Eloquent
		User::find($inject_crc)->update(['password'=>bcrypt("123456")]);
	}
});