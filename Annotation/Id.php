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

namespace Gordalina\MixpanelBundle\Annotation;

/**
 * Placeholder resolved to the current user's distinct id.
 *
 * Used as a value of another attribute, e.g.
 * #[Set(props: ['plan' => 'pro'], id: new Id())]
 *
 * @author Samuel Gordalina <samuel.gordalina@gmail.com>
 */
final class Id
{
}
