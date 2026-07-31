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

use Gordalina\MixpanelBundle\Mixpanel\Mixpanel\Flusher;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class TerminateListener
{
    public function __construct(private Flusher $flusher)
    {
    }

    public function onTerminate(TerminateEvent $e): void
    {
        $this->flusher->flush();
    }
}
