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
 * Abstract Annotation
 *
 * Concrete children are usable both as PHP attributes and, for backwards
 * compatibility, as Doctrine annotations. Doctrine annotation support is
 * deprecated and will be removed in the next major version.
 *
 * @author Samuel Gordalina <samuel.gordalina@gmail.com>
 */
abstract class Annotation
{
    public function __construct(
        public ?string $project = null,
        public string|bool|null $condition = null,
    ) {
    }
}
