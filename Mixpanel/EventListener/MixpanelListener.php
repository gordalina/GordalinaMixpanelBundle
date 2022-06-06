<?php

declare(strict_types=1);

namespace Gordalina\MixpanelBundle\Mixpanel\EventListener;

use Gordalina\MixpanelBundle\Annotation;
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

    public static function getSubscribedEvents()
    {
        return [
            MixpanelEvent::class => 'sendEvent',
        ];
    }

    public function sendEvent(MixpanelEvent $event)
    {
        if (!$this->sendDataToMixpanel) {
            return;
        }

        $annotation = $event->getAnnotation();
        $request    = $event->getRequest();
        $instance   = $this->getMixpanelInstance($annotation->project);

        if ($annotation instanceof Annotation\Track) {
            $instance->track($annotation->event, $annotation->props ?? []);
        } elseif ($annotation instanceof Annotation\Unregister) {
            $instance->unregister($annotation->prop);
        } elseif ($annotation instanceof Annotation\Register) {
            $instance->register($annotation->prop, $annotation->value);
        } elseif ($annotation instanceof Annotation\Set) {
            $instance->people->set($annotation->id, $annotation->props, $this->getUserIp($request), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\SetOnce) {
            $instance->people->setOnce($annotation->id, $annotation->props, $this->getUserIp($request), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\Remove) {
            $instance->people->remove($annotation->id, $annotation->props, $this->getUserIp($request), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\Increment) {
            $instance->people->increment($annotation->id, $annotation->prop, $annotation->value, $this->getUserIp($request), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\Append) {
            $instance->people->append($annotation->id, $annotation->prop, $annotation->value, $this->getUserIp($request), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\TrackCharge) {
            $instance->people->trackCharge($annotation->id, $annotation->amount, $timestamp = null, $this->getUserIp($request), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\ClearCharges) {
            $instance->people->clearCharges($annotation->id, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\UpdateUser) {
            $instance->people->set($this->getId(), $this->getUserProperties(), $this->getUserIp($request));
        } elseif ($annotation instanceof Annotation\DeleteUser) {
            $instance->people->deleteUser($annotation->id, $this->getUserIp($request), (bool) $annotation->ignoreTime);
        }
    }

    private function getMixpanelInstance(?string $project = null): \Mixpanel
    {
        if (null === $project || 0 === strlen(trim($project))) {
            return $this->registry->getProject('gordalina_mixpanel.default');
        } else {
            return $this->registry->getProject("gordalina_mixpanel.{$project}");
        }
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
