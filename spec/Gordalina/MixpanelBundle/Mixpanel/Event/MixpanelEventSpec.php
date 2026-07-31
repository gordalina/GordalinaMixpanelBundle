<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\Mixpanel\Event;

use Gordalina\MixpanelBundle\Attribute;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\Request;

class MixpanelEventSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(new Attribute\Track('Order Completed'), Request::create('/'));
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent');
    }

    public function it_exposes_the_attribute_it_was_built_with()
    {
        $attribute = new Attribute\Track('Order Completed', ['plan' => 'pro']);
        $this->beConstructedWith($attribute, Request::create('/'));

        $this->getAttribute()->shouldBe($attribute);
    }

    public function it_exposes_the_request_it_was_built_with()
    {
        $request = Request::create('/checkout');
        $this->beConstructedWith(new Attribute\Track('Order Completed'), $request);

        $this->getRequest()->shouldBe($request);
    }

    /**
     * The constructor is documented as directly usable, so its parameter names are
     * public API: `attribute` replaced `annotation` in 7.0. Renaming one again
     * makes the call below fail with an "Unknown named parameter" Error.
     */
    public function it_is_constructible_with_named_arguments()
    {
        $attribute = new Attribute\Track('Order Completed');
        $event     = new MixpanelEvent(attribute: $attribute, request: Request::create('/'));

        $this->beConstructedWith($attribute, $event->getRequest());

        $this->getAttribute()->shouldBe($event->getAttribute());
    }
}
