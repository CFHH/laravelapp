<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

//可以使用php artisan make:command MyCommand创建这个文件，只创建了这个文件
class MyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mycommand:test {param} {--option=}';  //php artisan mycommand:test 123 --option=abc

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $command = $this->argument('command');
        $param = $this->argument('param'); // 不指定参数名的情况下用argument
        $option = $this->option('option'); // 用--开头指定参数名
        echo "你的参数是：{$command} {$param} [, {$option}]";
    }
}
