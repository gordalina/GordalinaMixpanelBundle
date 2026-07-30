<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\Mixpanel\EventListener;

use Gordalina\MixpanelBundle\Attribute as Mixpanel;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Gordalina\MixpanelBundle\Mixpanel\Security\UserData;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * `ControllerEvent` is final, so these examples go through a real one built from a
 * real controller: the attributes are read by reflection, the way they are at
 * runtime.
 */
class ControllerListenerSpec extends ObjectBehavior
{
    public function let(UserData $userData, EventDispatcherInterface $eventDispatcher)
    {
        $this->beConstructedWith($userData, $eventDispatcher);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\EventListener\ControllerListener');
    }

    /**
     * Also covers the value placeholders: `Attribute\Id` resolves through
     * `UserData` and `Attribute\Expression` against the request attributes, both
     * nested inside a `props` array.
     */
    public function it_dispatches_one_event_per_mixpanel_attribute(
        UserData $userData,
        EventDispatcherInterface $eventDispatcher,
        HttpKernelInterface $kernel,
    ) {
        $userData->getId()->willReturn(42);

        $eventDispatcher
            ->dispatch(Argument::that(static function (MixpanelEvent $event): bool {
                $attribute = $event->getAttribute();

                return $attribute instanceof Mixpanel\Track
                    && 'Order Completed' === $attribute->event
                    && ['order_id' => 'ORD-1'] === $attribute->props;
            }))
            ->shouldBeCalledOnce()
            ->willReturnArgument(0);

        $eventDispatcher
            ->dispatch(Argument::that(static function (MixpanelEvent $event): bool {
                $attribute = $event->getAttribute();

                return $attribute instanceof Mixpanel\Set
                    && 42 === $attribute->id
                    && ['plan' => 'pro'] === $attribute->props;
            }))
            ->shouldBeCalledOnce()
            ->willReturnArgument(0);

        $this->onKernelController($this->controllerEvent($kernel, 'tracked'));
    }

    /**
     * The controller carries an attribute of its own, which must not be mistaken
     * for a Mixpanel action.
     */
    public function it_ignores_attributes_that_are_not_mixpanel_actions(
        EventDispatcherInterface $eventDispatcher,
        HttpKernelInterface $kernel,
    ) {
        $eventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->onKernelController($this->controllerEvent($kernel, 'untracked'));
    }

    public function it_skips_attributes_whose_condition_is_not_met(
        EventDispatcherInterface $eventDispatcher,
        HttpKernelInterface $kernel,
    ) {
        $eventDispatcher
            ->dispatch(Argument::that(static fn (MixpanelEvent $event): bool => 'Always Sent' === $event->getAttribute()->event))
            ->shouldBeCalledOnce()
            ->willReturnArgument(0);

        $eventDispatcher
            ->dispatch(Argument::that(static fn (MixpanelEvent $event): bool => 'Always Sent' !== $event->getAttribute()->event))
            ->shouldNotBeCalled();

        $this->onKernelController($this->controllerEvent($kernel, 'conditional'));
    }

    public function it_passes_the_request_along_with_the_attribute(
        EventDispatcherInterface $eventDispatcher,
        HttpKernelInterface $kernel,
    ) {
        $event = $this->controllerEvent($kernel, 'conditional');

        $eventDispatcher
            ->dispatch(Argument::that(static fn (MixpanelEvent $dispatched): bool => $dispatched->getRequest() === $event->getRequest()))
            ->shouldBeCalledOnce()
            ->willReturnArgument(0);

        $this->onKernelController($event);
    }

    private function controllerEvent(HttpKernelInterface $kernel, string $method): ControllerEvent
    {
        $request = Request::create('/checkout');
        $request->attributes->set('orderId', 'ORD-1');

        return new ControllerEvent(
            $kernel->getWrappedObject(),
            [new ControllerListenerFixture(), $method],
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}

#[\Attribute(\Attribute::TARGET_METHOD)]
final class NotAMixpanelAction
{
}

class ControllerListenerFixture
{
    #[Mixpanel\Track('Order Completed', props: ['order_id' => new Mixpanel\Expression('orderId')])]
    #[Mixpanel\Set(props: ['plan' => 'pro'], id: new Mixpanel\Id())]
    public function tracked(): void
    {
    }

    #[Mixpanel\Track('Never Sent', condition: false)]
    #[Mixpanel\Track('Always Sent', condition: 'orderId == "ORD-1"')]
    public function conditional(): void
    {
    }

    #[NotAMixpanelAction]
    public function untracked(): void
    {
    }
}
