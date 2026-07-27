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
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD"})
 *
 * @author Samuel Gordalina <samuel.gordalina@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Set extends Annotation
{
    /**
     * @param array<string, mixed> $props
     */
    public function __construct(
        public array $props,
        public mixed $id = null,
        public bool $ignoreTime = false,
        ?string $project = null,
        string|bool|null $condition = null,
    ) {
        parent::__construct($project, $condition);
    }
}
