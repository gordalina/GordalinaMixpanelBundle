<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\Mixpanel\EventListener;

use Gordalina\MixpanelBundle\Attribute;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Gordalina\MixpanelBundle\Mixpanel\ManagerRegistry;
use Gordalina\MixpanelBundle\Mixpanel\Security\UserData;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

/**
 * `sendEvent()` dispatches on the attribute class, one branch per action. The
 * branches are indistinguishable to static analysis — a mis-mapped one (say
 * `SetOnce` calling `people->set()`) is valid PHP — so each is covered here.
 */
class MixpanelListenerSpec extends ObjectBehavior
{
    private const IP = '198.51.100.4';

    public function let(
        ManagerRegistry $registry,
        UserData $userData,
        \Mixpanel $mixpanel,
        \Producers_MixpanelPeople $people,
    ) {
        // `people` is a public property on \Mixpanel, not a method, so it has to be
        // set on the revealed double rather than stubbed.
        $mixpanel->getWrappedObject()->people = $people->getWrappedObject();

        $registry->getProject('gordalina_mixpanel.default')->willReturn($mixpanel);

        $this->beConstructedWith($registry, $userData, true, true);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\EventListener\MixpanelListener');
    }

    public function it_subscribes_to_the_mixpanel_event()
    {
        $this::getSubscribedEvents()->shouldBe([MixpanelEvent::class => 'sendEvent']);
    }

    public function it_tracks(\Mixpanel $mixpanel)
    {
        $mixpanel->track('Order Completed', ['plan' => 'pro'])->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Track('Order Completed', ['plan' => 'pro'])));
    }

    public function it_registers(\Mixpanel $mixpanel)
    {
        $mixpanel->register('plan', 'pro')->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Register('plan', 'pro')));
    }

    public function it_unregisters(\Mixpanel $mixpanel)
    {
        $mixpanel->unregister('plan')->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Unregister('plan')));
    }

    public function it_sets_people_properties(\Producers_MixpanelPeople $people)
    {
        $people->set(42, ['plan' => 'pro'], self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Set(['plan' => 'pro'], 42)));
    }

    public function it_sets_people_properties_once(\Producers_MixpanelPeople $people)
    {
        $people->setOnce(42, ['plan' => 'pro'], self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\SetOnce(['plan' => 'pro'], 42)));
    }

    public function it_removes_people_properties(\Producers_MixpanelPeople $people)
    {
        $people->remove(42, ['plan' => 'pro'], self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Remove(['plan' => 'pro'], 42)));
    }

    public function it_increments_a_people_property(\Producers_MixpanelPeople $people)
    {
        $people->increment(42, 'logins', 2, self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Increment('logins', 2, 42)));
    }

    public function it_appends_to_a_people_property(\Producers_MixpanelPeople $people)
    {
        $people->append(42, 'fruits', 'apples', self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Append('fruits', 'apples', 42)));
    }

    public function it_tracks_a_charge(\Producers_MixpanelPeople $people)
    {
        $people->trackCharge(42, 19.99, null, self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\TrackCharge(19.99, 42)));
    }

    public function it_clears_charges(\Producers_MixpanelPeople $people)
    {
        $people->clearCharges(42, self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\ClearCharges(42)));
    }

    public function it_deletes_a_user(\Producers_MixpanelPeople $people)
    {
        $people->deleteUser(42, self::IP, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\DeleteUser(42)));
    }

    /**
     * `UpdateUser` takes its id and properties from `UserData`, not from the
     * attribute, and `id` is stripped from the properties it sends.
     */
    public function it_updates_the_user_from_user_data(UserData $userData, \Producers_MixpanelPeople $people)
    {
        $userData->getId()->willReturn(42);
        $userData->getProperties()->willReturn(['id' => 42, '$email' => 'user@example.com']);

        $people->set(42, ['$email' => 'user@example.com'], self::IP)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\UpdateUser()));
    }

    public function it_reads_the_project_named_by_the_attribute(
        ManagerRegistry $registry,
        \Mixpanel $other,
        \Mixpanel $mixpanel,
    ) {
        $registry->getProject('gordalina_mixpanel.other')->willReturn($other);

        $other->track('Order Completed', [])->shouldBeCalled();
        $mixpanel->track(Argument::cetera())->shouldNotBeCalled();

        $this->sendEvent($this->event(new Attribute\Track('Order Completed', [], 'other')));
    }

    public function it_falls_back_to_the_default_project_on_a_blank_project_name(\Mixpanel $mixpanel)
    {
        $mixpanel->track('Order Completed', [])->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Track('Order Completed', [], '   ')));
    }

    public function it_omits_the_client_ip_when_disabled(
        ManagerRegistry $registry,
        UserData $userData,
        \Producers_MixpanelPeople $people,
    ) {
        $this->beConstructedWith($registry, $userData, true, false);

        $people->set(42, ['plan' => 'pro'], null, false)->shouldBeCalled();

        $this->sendEvent($this->event(new Attribute\Set(['plan' => 'pro'], 42)));
    }

    public function it_sends_nothing_when_disabled(ManagerRegistry $registry, UserData $userData)
    {
        $this->beConstructedWith($registry, $userData, false, true);

        $registry->getProject(Argument::cetera())->shouldNotBeCalled();

        $this->sendEvent($this->event(new Attribute\Track('Order Completed')));
    }

    private function event(Attribute\Action $attribute): MixpanelEvent
    {
        return new MixpanelEvent($attribute, Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => self::IP]));
    }
}
