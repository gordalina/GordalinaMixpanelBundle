<?php

declare(strict_types=1);

namespace Gordalina\MixpanelBundle\Mixpanel\Event;

use Gordalina\MixpanelBundle\Attribute\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class MixpanelEvent extends Event
{
    private $attribute;
    private $request;

    public function __construct(Action $attribute, Request $request)
    {
        $this->attribute = $attribute;
        $this->request   = $request;
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
