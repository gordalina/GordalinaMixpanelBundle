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

namespace Gordalina\MixpanelBundle\Mixpanel;

use Gordalina\MixpanelBundle\ManagerRegistry;
use Symfony\Component\Stopwatch\Stopwatch;

class Flusher
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var int
     */
    private $time;

    /**
     * @var bool
     */
    private $enableProfiler;

    /**
     * @param Stopwatch $stopwatch      defaults to null
     * @param bool      $enableProfiler defaults to false
     */
    public function __construct(ManagerRegistry $registry, Stopwatch $stopwatch = null, $enableProfiler = false)
    {
        $this->registry       = $registry;
        $this->stopwatch      = $stopwatch ?: new Stopwatch();
        $this->enableProfiler = $enableProfiler;
    }

    public function flush()
    {
        if (!$this->enableProfiler) {
            $this->straightFlush();
        } else {
            $this->dataCollectorFlush();
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @see http://en.wikipedia.org/wiki/Glossary_of_poker_terms#poker_face
     *
     * @return null
     */
    private function straightFlush()
    {
        foreach ($this->registry->getProjects() as $id => $project) {
            $project->flush();
            $project->people->flush();
        }
    }

    /**
     * @return null
     */
    private function dataCollectorFlush()
    {
        // get data from the queue
        foreach ($this->registry->getProjects() as $id => $project) {
            if (!isset($this->data[$id])) {
                $this->data[$id] = [
                    'events' => [],
                    'people' => [],
                ];
            }

            $this->data[$id]['events'] = array_merge($this->data[$id]['events'], $this->getQueue($project, '_events', false));
            $this->data[$id]['people'] = array_merge($this->data[$id]['people'], $this->getQueue($project, 'people', true));
        }

        // log the time spent flushing
        $key = sprintf('%s::flush', get_class($this->registry));
        $this->stopwatch->start($key);

        $this->straightFlush();

        $event = $this->stopwatch->stop($key);
        $this->time += $event->getDuration();
    }

    /**
     * This is quite a hack, but gets the job done.
     *
     * @param MixPanel $project
     * @param string   $propertyName
     * @param bool     $isAccessible
     *
     * @return array
     */
    private function getQueue(\MixPanel $project, $propertyName, $isAccessible)
    {
        $queue = [];

        $rfl      = new \ReflectionClass($project);
        $property = $rfl->getProperty($propertyName);

        if (!$isAccessible) {
            $property->setAccessible(true);
        }

        $producer = $property->getValue($project);

        $propertyQueue = new \ReflectionProperty('Producers_MixpanelBaseProducer', '_queue');
        $propertyQueue->setAccessible(true);

        $queue = $propertyQueue->getValue($producer);

        $propertyQueue->setAccessible(false);

        if (!$isAccessible) {
            $property->setAccessible(false);
        }

        return $queue;
    }
}
