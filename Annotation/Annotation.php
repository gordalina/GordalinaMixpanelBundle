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
 * Base class of every Mixpanel action.
 *
 * Concrete children are PHP attributes. The namespace is named "Annotation"
 * for historical reasons: it predates PHP attributes and keeping it spares
 * every user a rewrite of their `use` statements.
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
