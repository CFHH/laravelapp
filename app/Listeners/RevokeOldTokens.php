<?php

namespace App\Listeners;

use Laravel\Passport\Events\AccessTokenCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Passport\Token as AccessToken;
use App\User;
use DB;
use Redis;

class RevokeOldTokens
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
     * @param  AccessTokenCreated  $event
     * @return void
     */
    public function handle(AccessTokenCreated $event)
    {
        $user_token_key = App\User::getAccessTokenCacheKey($event->userId);
        $old_accesstoken_key = Redis::get($user_token_key);
        if ($old_accesstoken_key != null)
            Redis::del($old_accesstoken_key);
        $new_accesstoken_key = AccessToken::getCacheKey($event->tokenId);
        Redis::setex($user_token_key, User::AccessTokenCacheKey_ExpireSceonds, $new_accesstoken_key);

        AccessToken::where('user_id', $event->userId)
            ->where('id', '!=', $event->tokenId)
            ->delete();

        DB::table('oauth_refresh_tokens')
            ->where('user_id', $event->userId)
            ->delete();
    }
}
