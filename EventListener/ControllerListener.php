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

namespace Gordalina\MixpanelBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Gordalina\MixpanelBundle\Annotation;
use Gordalina\MixpanelBundle\ManagerRegistry;
use Gordalina\MixpanelBundle\Security\UserData;
use Mixpanel;
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
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var UserData
     */
    private $userData;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function __construct(Reader $annotationReader, ManagerRegistry $registry, UserData $userData, ExpressionLanguage $expressionLanguage)
    {
        $this->annotationReader   = $annotationReader;
        $this->registry           = $registry;
        $this->userData           = $userData;
        $this->expressionLanguage = $expressionLanguage;
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
                    $this->prepareAnnotation($annotation, $event->getRequest());
                    $this->executeAnnotation($annotation, $event->getRequest());
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

    private function updateDataAnnotation(Annotation\Annotation $annotation, Request $request, string $key, $value, ?string $parentKey = null)
    {
        $element = null;

        if ($value instanceof Annotation\Id) {
            $element = $this->userData->getId();
        }

        if ($value instanceof Annotation\Expression) {
            $element = $this->expressionLanguage->evaluate($value->expression, $request->attributes->all());
        }

        if ($element === null) {
            return;
        }

        if ($parentKey !== null) {
            $annotation->$parentKey[$key] = $element;
        } else {
            $annotation->$key = $element;
        }
    }

    private function executeAnnotation(Annotation\Annotation $annotation, Request $request)
    {
        $instance = $this->getMixpanelInstance($annotation->project);

        if ($annotation instanceof Annotation\Track) {
            $instance->track($annotation->event, $annotation->props ?: []);
        } elseif ($annotation instanceof Annotation\Unregister) {
            $instance->unregister($annotation->prop);
        } elseif ($annotation instanceof Annotation\Register) {
            $instance->register($annotation->prop, $annotation->value);
        } elseif ($annotation instanceof Annotation\Set) {
            $instance->people->set($annotation->id, $annotation->props, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\SetOnce) {
            $instance->people->setOnce($annotation->id, $annotation->props, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\Remove) {
            $instance->people->remove($annotation->id, $annotation->prop, $annotation->value, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\Increment) {
            $instance->people->increment($annotation->id, $annotation->prop, $annotation->value, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\Append) {
            $instance->people->append($annotation->id, $annotation->prop, $annotation->value, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\TrackCharge) {
            $instance->people->trackCharge($annotation->id, $annotation->amount, $timestamp = null, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\ClearCharges) {
            $instance->people->clearCharges($annotation->id, $request->getClientIp(), (bool) $annotation->ignoreTime);
        } elseif ($annotation instanceof Annotation\UpdateUser) {
            $instance->people->set($this->getId(), $this->getUserProperties(), $request->getClientIp());
        } elseif ($annotation instanceof Annotation\DeleteUser) {
            $instance->people->deleteUser($annotation->id, $request->getClientIp(), (bool) $annotation->ignoreTime);
        }
    }

    /**
     * @param string $project
     *
     * @return Mixpanel
     */
    private function getMixpanelInstance($project = null)
    {
        if (!$project || strlen($project)) {
            return $this->registry->getProject('gordalina_mixpanel.default');
        } else {
            return $this->registry->getProject("gordalina_mixpanel.{$project}");
        }
    }

    /**
     * @return array|null
     */
    private function getUserProperties()
    {
        $props = $this->userData->getProperties();
        unset($props['id']);

        return $props;
    }

    /**
     * @return int|null
     */
    private function getId()
    {
        return $this->userData->getId();
    }
}
