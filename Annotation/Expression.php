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
 * Placeholder resolved by evaluating an ExpressionLanguage expression.
 *
 * Used as a value of another attribute, e.g.
 * #[Track('Order Completed', props: ['user_id' => new Expression('user.getId()')])]
 *
 * @author Samuel Gordalina <samuel.gordalina@gmail.com>
 */
final class Expression
{
    public function __construct(
        public string $expression,
    ) {
    }
}
