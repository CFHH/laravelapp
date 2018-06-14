<?php

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Log;

class LogDBQuery
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  QueryExecuted  $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        if (config('app.debug'))
        {
            $sql = str_replace("?", "'%s'", $event->sql);
            $args = [];
            $args_cnt = count($event->bindings);
            for ($i = 0; $i < $args_cnt; ++$i)
            {
                $arg = $event->bindings[$i];
                if (is_object($arg))  // DateTime，都用TimeStamp就好了
                    $args[$i] = new Carbon($arg->format('Y-m-d H:i:s.u'), $arg->getTimezone());
                else
                    $args[$i] = $arg;
            }
            $log = 'DB query from ' . $event->connectionName . ' :: ' . vsprintf($sql, $args);
            Log::info($log);
        }
        else
        {
            $log = 'DB query from ' . $event->connectionName . ' :: ' . $event->sql;
            Log::info($log);
        }
    }
}
