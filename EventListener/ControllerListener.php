<?php

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
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

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

    /**
     * @param Reader          $annotationReader
     * @param ManagerRegistry $registry
     * @param UserData        $userData
     */
    public function __construct(Reader $annotationReader, ManagerRegistry $registry, UserData $userData, ExpressionLanguage $expressionLanguage)
    {
        $this->annotationReader = $annotationReader;
        $this->registry = $registry;
        $this->userData = $userData;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param  FilterControllerEvent $event
     * @return null
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $className = class_exists('Doctrine\Common\Util\ClassUtils') ? ClassUtils::getClass($controller[0]) : get_class($controller[0]);
        $object = new \ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $classAnnotations = $this->annotationReader->getClassAnnotations($object);
        $methodAnnotations = $this->annotationReader->getMethodAnnotations($method);

        foreach (array($classAnnotations, $methodAnnotations) as $collection) {
            foreach ($collection as $annotation) {
                if ($annotation instanceof Annotation\Annotation) {
                    $this->prepareAnnotation($annotation, $event->getRequest());
                    $this->executeAnnotation($annotation, $event->getRequest());
                }
            }
        }
    }

    /**
     * @param  AnnotationAnnotation $annotation
     * @return null
     */
    private function prepareAnnotation(Annotation\Annotation $annotation, Request $request)
    {
        foreach ($annotation as $key => $value) {
            if ($value instanceof Annotation\Id) {
                $annotation->$key = $this->userData->getId();
            }

            if ($value instanceof Annotation\Expression) {
                $annotation->$key = $this->expressionLanguage->evaluate($value->expression, $request->attributes->all());
            }
        }
    }

    /**
     * @param  AnnotationAnnotation $annotation
     * @return null
     */
    private function executeAnnotation(Annotation\Annotation $annotation, Request $request)
    {
        $instance = $this->getMixpanelInstance($annotation->project);

        if ($annotation instanceof Annotation\Track) {
            $instance->track($annotation->event, $annotation->props ?: array());
        } else if ($annotation instanceof Annotation\Unregister) {
            $instance->unregister($annotation->prop, $annotation->value);
        } else if ($annotation instanceof Annotation\Register) {
            $instance->register($annotation->prop, $annotation->value);
        } else if ($annotation instanceof Annotation\Set) {
            $instance->people->set($annotation->id, $annotation->props, $request->getClientIp(), !!$annotation->ignoreTime);
        } else if ($annotation instanceof Annotation\SetOnce) {
            $instance->people->setOnce($annotation->id, $annotation->props, $request->getClientIp(), !!$annotation->ignoreTime);
        } else if ($annotation instanceof Annotation\Remove) {
            $instance->people->remove($annotation->id, $annotation->prop, $annotation->value, $request->getClientIp(), !!$annotation->ignoreTime);
        } else if ($annotation instanceof Annotation\Increment) {
            $instance->people->increment($annotation->id, $annotation->prop, $annotation->value, $request->getClientIp(), !!$annotation->ignoreTime);
        } else if ($annotation instanceof Annotation\Append) {
            $instance->people->append($annotation->id, $annotation->prop, $annotation->value, $request->getClientIp(), !!$annotation->ignoreTime);
        } else if ($annotation instanceof Annotation\TrackCharge) {
            $instance->people->trackCharge($annotation->id, $annotation->amount, $timestamp = null, $request->getClientIp(), !!$annotation->ignoreTime);
        } else if ($annotation instanceof Annotation\ClearCharges) {
            $instance->people->clearCharges($annotation->id, $request->getClientIp(), !!$annotation->ignoreTime);
        } else if ($annotation instanceof Annotation\UpdateUser) {
            $instance->people->set($this->getId(), $this->getUserProperties(), $request->getClientIp());
        } else if ($annotation instanceof Annotation\DeleteUser) {
            $instance->people->deleteUser($annotation->id, $request->getClientIp(), !!$annotation->ignoreTime);
        }
    }
    /**
     * @param  string $project
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
}
