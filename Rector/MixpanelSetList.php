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

namespace Gordalina\MixpanelBundle\Rector;

/**
 * Rector sets shipped with this bundle.
 *
 * Usage in your project's rector.php:
 *
 *     use Gordalina\MixpanelBundle\Rector\MixpanelSetList;
 *
 *     return RectorConfig::configure()
 *         ->withPaths([__DIR__.'/src'])
 *         ->withSets([MixpanelSetList::ANNOTATIONS_TO_ATTRIBUTES]);
 *
 * @author Samuel Gordalina <samuel.gordalina@gmail.com>
 */
final class MixpanelSetList
{
    /**
     * Converts Mixpanel Doctrine annotations into PHP attributes.
     */
    public const ANNOTATIONS_TO_ATTRIBUTES = __DIR__.'/../Resources/rector/annotations-to-attributes.php';

    private function __construct()
    {
    }
}
