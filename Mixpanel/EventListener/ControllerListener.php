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

use Gordalina\MixpanelBundle\Annotation;
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

        foreach ($this->getMixpanelAttributes($event) as $annotation) {
            if (!$this->isConditionMet($annotation, $request)) {
                continue;
            }

            $this->prepareAnnotation($annotation, $request);
            $this->eventDispatcher->dispatch(new MixpanelEvent($annotation, $request));
        }
    }

    /**
     * @return list<Annotation\Annotation>
     */
    private function getMixpanelAttributes(ControllerEvent $event): array
    {
        $annotations = [];

        foreach ($event->getAttributes() as $attributes) {
            foreach ($attributes as $attribute) {
                if ($attribute instanceof Annotation\Annotation) {
                    $annotations[] = $attribute;
                }
            }
        }

        return $annotations;
    }

    private function prepareAnnotation(Annotation\Annotation $annotation, Request $request): void
    {
        foreach ($annotation as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    $this->updateDataAnnotation($annotation, $request, $key2, $value2, $key);
                }
                continue;
            }

            $this->updateDataAnnotation($annotation, $request, $key, $value);
        }
    }

    private function isConditionMet(Annotation\Annotation $annotation, Request $request): bool
    {
        $condition = $annotation->condition;

        if (null === $condition) {
            return true;
        }

        if (is_bool($condition)) {
            return $condition;
        }

        return true === $this->expressionLanguage->evaluate($condition, array_merge(['request' => $request], $request->attributes->all()));
    }

    private function updateDataAnnotation(Annotation\Annotation $annotation, Request $request, string $key, mixed $value, ?string $parentKey = null): void
    {
        $element = null;

        if ($value instanceof Annotation\Id) {
            $element = $this->userData->getId();
        }

        if ($value instanceof Annotation\Expression) {
            $element = $this->expressionLanguage->evaluate($value->expression, array_merge($request->attributes->all(), ['request' => $request]));
        }

        if (null === $element) {
            return;
        }

        if (null !== $parentKey) {
            $annotation->$parentKey[$key] = $element;
        } else {
            $annotation->$key = $element;
        }
    }
}
