<?php

namespace App\Listeners;

use Laravel\Passport\Events\AccessTokenCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Passport\Token as AccessToken;
use DB;
use Redis;

class RevokeOldTokens
{
    protected $cache_expire_sceonds = 604800;

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
     * @param  AccessTokenCreated  $event
     * @return void
     */
    public function handle(AccessTokenCreated $event)
    {
        $thiskey = $this->getCacheKey($event->userId);
        $old_accesstoken_key = Redis::get($thiskey);
        if ($old_accesstoken_key != null)
            Redis::del($old_accesstoken_key);
        $new_accesstoken_key = AccessToken::getCacheKey($event->tokenId);
        Redis::setex($thiskey, $this->cache_expire_sceonds, $new_accesstoken_key);

        DB::table('oauth_access_tokens')
            ->where('user_id', $event->userId)
            ->where('id', '!=', $event->tokenId)
            ->delete();

        DB::table('oauth_refresh_tokens')
            ->where('user_id', $event->userId)
            ->delete();
    }

    public function getCacheKey($userid)
    {
        $name = 'User2AccessToken';
        return "{$name}:{$userid}";
    }
}
