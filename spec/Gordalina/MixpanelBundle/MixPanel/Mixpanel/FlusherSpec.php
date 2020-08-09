<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\MixPanel\Mixpanel;

use Gordalina\MixpanelBundle\MixPanel\ManagerRegistry;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Stopwatch\Stopwatch;

class FlusherSpec extends ObjectBehavior
{
    public function it_is_initializable(ManagerRegistry $registry, Stopwatch $stopwatch)
    {
        $this->beConstructedWith($registry, $stopwatch, true);
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\MixPanel\Flusher');
    }

    public function it_should_construct_without_stopwatch(ManagerRegistry $registry)
    {
        $this->beConstructedWith($registry);
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\MixPanel\Flusher');
    }
}
