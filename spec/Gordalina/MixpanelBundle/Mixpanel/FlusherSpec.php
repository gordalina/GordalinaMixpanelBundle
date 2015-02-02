<?php

namespace spec\Gordalina\MixpanelBundle\Mixpanel;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FlusherSpec extends ObjectBehavior
{
    /**
     * @param Gordalina\MixpanelBundle\ManagerRegistry $registry
     * @param Symfony\Component\Stopwatch\Stopwatch $stopwatch
     */
    public function it_is_initializable($registry, $stopwatch)
    {
        $this->beConstructedWith($registry, $stopwatch, true);
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\Flusher');
    }

    /**
     * @param Gordalina\MixpanelBundle\ManagerRegistry $registry
     */
    public function it_should_construct_without_stopwatch($registry)
    {
        $this->beConstructedWith($registry);
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\Flusher');
    }
}
