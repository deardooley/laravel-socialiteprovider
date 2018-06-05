<?php

namespace SocialiteProviders\Agave;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AgaveExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        \Log::debug("Handling agave socialite provier registritation.");
        $socialiteWasCalled->extendSocialite('agave', __NAMESPACE__.'\Provider');
    }
}