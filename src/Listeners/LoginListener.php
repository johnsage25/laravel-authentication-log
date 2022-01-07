<?php

namespace Pearldrift\LaravelAuthenticationLog\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Pearldrift\LaravelAuthenticationLog\Notifications\NewDevice;

class LoginListener
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle($event): void
    {
        if (! $event instanceof (config('authentication-log.events.login') ?? Login::class)) {
            return;
        }

        if ($event->user) {
            $user = $event->user;
            $ip = $this->request->ip();
            $userAgent = $this->request->userAgent();
            $known = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->whereLoginSuccessful(true)->first();
            $newUser = Carbon::parse($user->{$user->getCreatedAtColumn()})->diffInMinutes(Carbon::now()) < 1;

            $log = $user->authentications()->create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'login_at' => now(),
                'login_successful' => true,
                'session_id'=>session()->getId(),
                'location' => config('authentication-log.notifications.new-device.location') ? optional(geoip()->getLocation($ip))->toArray() : null,
            ]);

            if (! $known && ! $newUser && config('authentication-log.notifications.new-device.enabled')) {
                $newDevice = config('authentication-log.notifications.new-device.template') ?? NewDevice::class;
                $user->notify(new $newDevice($log));
            }
        }
    }
}
