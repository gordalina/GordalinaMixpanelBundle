<?php

declare(strict_types=1);

namespace Gordalina\MixpanelBundle\Mixpanel\EventListener;

use Gordalina\MixpanelBundle\Attribute;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Gordalina\MixpanelBundle\Mixpanel\ManagerRegistry;
use Gordalina\MixpanelBundle\Mixpanel\Security\UserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class MixpanelListener implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var UserData
     */
    private $userData;

    /**
     * @var bool
     */
    private $sendDataToMixpanel;

    /**
     * @var bool
     */
    private $sendUserIpToMixpanel;

    public function __construct(ManagerRegistry $registry, UserData $userData, bool $sendDataToMixpanel, bool $sendUserIpToMixpanel)
    {
        $this->registry             = $registry;
        $this->userData             = $userData;
        $this->sendDataToMixpanel   = $sendDataToMixpanel;
        $this->sendUserIpToMixpanel = $sendUserIpToMixpanel;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MixpanelEvent::class => 'sendEvent',
        ];
    }

    public function sendEvent(MixpanelEvent $event): void
    {
        if (!$this->sendDataToMixpanel) {
            return;
        }

        $attribute = $event->getAttribute();
        $request   = $event->getRequest();
        $instance  = $this->getMixpanelInstance($attribute->project);

        if ($attribute instanceof Attribute\Track) {
            $instance->track($attribute->event, $attribute->props);
        } elseif ($attribute instanceof Attribute\Unregister) {
            $instance->unregister($attribute->prop);
        } elseif ($attribute instanceof Attribute\Register) {
            $instance->register($attribute->prop, $attribute->value);
        } elseif ($attribute instanceof Attribute\Set) {
            $instance->people->set($attribute->id, $attribute->props, $this->getUserIp($request), $attribute->ignoreTime);
        } elseif ($attribute instanceof Attribute\SetOnce) {
            $instance->people->setOnce($attribute->id, $attribute->props, $this->getUserIp($request), $attribute->ignoreTime);
        } elseif ($attribute instanceof Attribute\Remove) {
            $instance->people->remove($attribute->id, $attribute->props, $this->getUserIp($request), $attribute->ignoreTime);
        } elseif ($attribute instanceof Attribute\Increment) {
            $instance->people->increment($attribute->id, $attribute->prop, $attribute->value, $this->getUserIp($request), $attribute->ignoreTime);
        } elseif ($attribute instanceof Attribute\Append) {
            $instance->people->append($attribute->id, $attribute->prop, $attribute->value, $this->getUserIp($request), $attribute->ignoreTime);
        } elseif ($attribute instanceof Attribute\TrackCharge) {
            $instance->people->trackCharge($attribute->id, $attribute->amount, null, $this->getUserIp($request), $attribute->ignoreTime);
        } elseif ($attribute instanceof Attribute\ClearCharges) {
            $instance->people->clearCharges($attribute->id, $request->getClientIp(), $attribute->ignoreTime);
        } elseif ($attribute instanceof Attribute\UpdateUser) {
            $instance->people->set($this->getId(), $this->getUserProperties(), $this->getUserIp($request));
        } elseif ($attribute instanceof Attribute\DeleteUser) {
            $instance->people->deleteUser($attribute->id, $this->getUserIp($request), $attribute->ignoreTime);
        }
    }

    private function getMixpanelInstance(?string $project = null): \Mixpanel
    {
        if (null === $project || 0 === strlen(trim($project))) {
            return $this->registry->getProject('gordalina_mixpanel.default');
        }

        return $this->registry->getProject("gordalina_mixpanel.{$project}");
    }

    private function getUserProperties(): array
    {
        $props = $this->userData->getProperties();
        unset($props['id']);

        return $props;
    }

    private function getUserIp(Request $request): ?string
    {
        return $this->sendUserIpToMixpanel ? $request->getClientIp() : null;
    }

    /**
     * @return int|null
     */
    private function getId()
    {
        return $this->userData->getId();
    }
}
