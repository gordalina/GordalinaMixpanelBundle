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

namespace Gordalina\MixpanelBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\Reader;
use Gordalina\MixpanelBundle\Mixpanel\EventListener\ControllerListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Makes sure the annotation reader is shared with the rest of the application.
 *
 * A cached reader returns the very same annotation instances on every call, so
 * listeners that enrich an annotation before this bundle reads it only work as
 * long as everyone uses one reader. Registering a private reader would silently
 * discard those mutations.
 *
 * @author Samuel Gordalina <samuel.gordalina@gmail.com>
 */
final class AnnotationReaderPass implements CompilerPassInterface
{
    public const READER_ID = 'gordalina_mixpanel.annotation_reader';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ControllerListener::class)) {
            return;
        }

        // Symfony < 7 still provides a shared reader: reuse it rather than
        // competing with it, so annotations enriched through it are the very
        // instances this bundle dispatches.
        if ($container->has('annotation_reader')) {
            $container->removeDefinition(self::READER_ID);
            $container->getDefinition(ControllerListener::class)
                ->setArgument('$annotationReader', new Reference('annotation_reader'));

            return;
        }

        // Symfony >= 7 removed it, so expose ours under the same autowiring
        // alias to keep that pattern available.
        if (!$container->has(Reader::class)) {
            $container->setAlias(Reader::class, self::READER_ID);
        }
    }
}
