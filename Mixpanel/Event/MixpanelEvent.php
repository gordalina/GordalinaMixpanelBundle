<?php

declare(strict_types=1);

namespace Gordalina\MixpanelBundle\Mixpanel\Event;

use Gordalina\MixpanelBundle\Attribute\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class MixpanelEvent extends Event
{
    public function __construct(
        private Action $attribute,
        private Request $request,
    ) {
    }

    public function getAttribute(): Action
    {
        return $this->attribute;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
