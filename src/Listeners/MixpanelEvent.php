<?php namespace GeneaLabs\LaravelMixpanel\Listeners;

use Carbon\Carbon;
use GeneaLabs\LaravelMixpanel\Events\MixpanelEvent as Event;

class MixpanelEvent
{
    public function handle(Event $event)
    {
        $user = $event->user;

        if ($user) {
            $profileData = $this->getProfileData($user);
            $profileData = array_merge($profileData, $event->profileData);

            app('mixpanel')->identify($user->getKey());
            app('mixpanel')->people->set($user->getKey(), $profileData, request()->ip());

            if ($event->charge !== 0) {
                app('mixpanel')->people->trackCharge($user->id, $event->charge);
            }

            app('mixpanel')->track($event->eventName);
        }
    }

    private function getProfileData($user) : array
    {

        if ($user->surname == '') {
            $nameParts = explode(' ', $user->name);
            array_filter($nameParts);

            $lastName  = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);
        }
        else
        {
            $firstName = $user->name;
            $lastName  = $user->surname;
        }

        $data = [
            '$first_name' => $firstName,
            '$last_name'  => $lastName,
            '$name'       => $user->name,
            '$email'      => $user->email,
            'Team'        => $user->currentTeam() ? $user->currentTeam()->slug : '',
            '$created'    => ($user->created_at
                ? (new Carbon)->parse($user->created_at)->format('Y-m-d\Th:i:s')
                : null),
        ];
        array_filter($data);

        return $data;
    }
}
