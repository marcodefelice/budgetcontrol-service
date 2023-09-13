<?php

namespace App\Http\Services;

use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * get ID user from access token
     * @param string $token
     * 
     */
    static public function userIDfromToken(string $token)
    {
        $session = session()->getId();

        if(Cache::has($session)) {
            Log::info("Current session $session");
            return Cache::get($session);
        }

        $data = PersonalAccessToken::where('token',$token)->firstOrFail();
        Cache::put($session,$data->tokenable_id);
    }

    /**
     * get user id from cache
     * 
     * @return int
     * @throws Exception
     */
    static public function getCacheUserID(): int
    {

        if(env("APP_DISABLE_AUTH",false) === true) {
            Log::info("Start session DEBUG MODE");
            return 1;
        }
        
        $session = session()->getId();

        if(!Cache::has($session)) {
            throw new \Exception("Unable find a user ID from cache with IP $session");
        }

        return Cache::get($session);

    }
}
