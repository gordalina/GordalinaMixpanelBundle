<?php

declare(strict_types=1);

/*
 * This file is part of the mixpanel bundle.
 *
 * (c) Samuel Gordalina <https://github.com/gordalina/mixpanel-bundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gordalina\MixpanelBundle\Mixpanel\EventListener;

use Gordalina\MixpanelBundle\Attribute;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Gordalina\MixpanelBundle\Mixpanel\Security\UserData;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(
        private UserData $userData,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        foreach ($this->getMixpanelAttributes($event) as $attribute) {
            if (!$this->isConditionMet($attribute, $request)) {
                continue;
            }

            $this->prepareAttribute($attribute, $request);
            $this->eventDispatcher->dispatch(new MixpanelEvent($attribute, $request));
        }
    }

    /**
     * @return list<Attribute\Action>
     */
    private function getMixpanelAttributes(ControllerEvent $event): array
    {
        $mixpanelAttributes = [];

        foreach ($event->getAttributes() as $attributes) {
            foreach ($attributes as $attribute) {
                if ($attribute instanceof Attribute\Action) {
                    $mixpanelAttributes[] = $attribute;
                }
            }
        }

        return $mixpanelAttributes;
    }

    private function prepareAttribute(Attribute\Action $attribute, Request $request): void
    {
        foreach ($attribute as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    $this->updateAttributeData($attribute, $request, $key2, $value2, $key);
                }
                continue;
            }

            $this->updateAttributeData($attribute, $request, $key, $value);
        }
    }

    private function isConditionMet(Attribute\Action $attribute, Request $request): bool
    {
        $condition = $attribute->condition;

        if (null === $condition) {
            return true;
        }

        if (is_bool($condition)) {
            return $condition;
        }

        return true === $this->expressionLanguage->evaluate($condition, array_merge(['request' => $request], $request->attributes->all()));
    }

    private function updateAttributeData(Attribute\Action $attribute, Request $request, string $key, mixed $value, ?string $parentKey = null): void
    {
        $element = null;

        if ($value instanceof Attribute\Id) {
            $element = $this->userData->getId();
        }

        if ($value instanceof Attribute\Expression) {
            $element = $this->expressionLanguage->evaluate($value->expression, array_merge($request->attributes->all(), ['request' => $request]));
        }

        if (null === $element) {
            return;
        }

        if (null !== $parentKey) {
            $attribute->$parentKey[$key] = $element;
        } else {
            $attribute->$key = $element;
        }
    }
}
