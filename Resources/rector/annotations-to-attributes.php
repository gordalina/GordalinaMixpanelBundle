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

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

/*
 * Converts Mixpanel Doctrine annotations into PHP attributes.
 *
 * Nested values such as @Mixpanel\Id or @Mixpanel\Expression("...") are turned
 * into "new" expressions, which is why the resulting code requires PHP 8.1.
 *
 * @see Gordalina\MixpanelBundle\Rector\MixpanelSetList::ANNOTATIONS_TO_ATTRIBUTES
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // events
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Register'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Track'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Unregister'),
        // engagement
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Append'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\ClearCharges'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\DeleteUser'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Increment'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Remove'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Set'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\SetOnce'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\TrackCharge'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\UpdateUser'),
    ]);
};
