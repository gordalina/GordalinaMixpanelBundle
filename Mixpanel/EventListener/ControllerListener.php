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

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Gordalina\MixpanelBundle\Annotation;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Gordalina\MixpanelBundle\Mixpanel\Security\UserData;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var UserData
     */
    private $userData;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(Reader $annotationReader, UserData $userData, EventDispatcherInterface $eventDispatcher)
    {
        $this->annotationReader   = $annotationReader;
        $this->userData           = $userData;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->eventDispatcher    = $eventDispatcher;
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $className = class_exists('Doctrine\Common\Util\ClassUtils') ? ClassUtils::getClass($controller[0]) : get_class($controller[0]);
        $object    = new \ReflectionClass($className);
        $method    = $object->getMethod($controller[1]);

        $classAnnotations  = $this->annotationReader->getClassAnnotations($object);
        $methodAnnotations = $this->annotationReader->getMethodAnnotations($method);

        foreach ([$classAnnotations, $methodAnnotations] as $collection) {
            foreach ($collection as $annotation) {
                if ($annotation instanceof Annotation\Annotation) {
                    $this->handleCondition($annotation, $event->getRequest());
                    if (true === $annotation->condition) {
                        $this->prepareAnnotation($annotation, $event->getRequest());
                        $this->eventDispatcher->dispatch(new MixpanelEvent($annotation, $event->getRequest()));
                    }
                }
            }
        }
    }

    private function prepareAnnotation(Annotation\Annotation $annotation, Request $request)
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

    private function handleCondition(Annotation\Annotation $annotation, Request $request)
    {
        $value = $annotation->condition;

        if (null !== $value) {
            $annotation->condition = $this->expressionLanguage->evaluate($value, array_merge(['request' => $request], $request->attributes->all()));
        } else {
            $annotation->condition = true;
        }
    }

    private function updateDataAnnotation(Annotation\Annotation $annotation, Request $request, string $key, $value, ?string $parentKey = null)
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
