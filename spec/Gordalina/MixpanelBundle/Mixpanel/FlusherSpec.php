<?php

namespace spec\Gordalina\MixpanelBundle\Mixpanel;

use Gordalina\MixpanelBundle\ManagerRegistry;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Stopwatch\Stopwatch;

class FlusherSpec extends ObjectBehavior
{
    public function it_is_initializable(ManagerRegistry $registry, Stopwatch $stopwatch)
    {
        $this->beConstructedWith($registry, $stopwatch, true);
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\Flusher');
    }

    public function it_should_construct_without_stopwatch(ManagerRegistry $registry)
    {
        $this->beConstructedWith($registry);
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\Flusher');
    }
}
