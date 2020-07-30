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
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 *
 * @author Samuel Gordalina <samuel.gordalina@gmail.com>
 */
final class Append extends Annotation
{
    /**
     * @var mixed
     */
    public $id;

    /**
     * @Required
     *
     * @var string
     */
    public $prop;

    /**
     * @Required
     *
     * @var int
     */
    public $value;

    /**
     * @var bool
     */
    public $ignoreTime;
}
