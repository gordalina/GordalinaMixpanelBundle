<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\Reader;
use Gordalina\MixpanelBundle\DependencyInjection\Compiler\AnnotationReaderPass;
use Gordalina\MixpanelBundle\Mixpanel\EventListener\ControllerListener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * A cached annotation reader returns the same annotation instances on every
 * call, so the reader has to be shared with the rest of the application for
 * listeners enriching an annotation to have any effect. These examples lock
 * that decision down: a private reader would silently discard those mutations.
 */
class AnnotationReaderPassSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(AnnotationReaderPass::class);
    }

    public function it_reuses_the_framework_reader_while_symfony_still_provides_one(
        ContainerBuilder $container,
        Definition $listener,
    ) {
        $container->hasDefinition(ControllerListener::class)->willReturn(true);
        $container->has('annotation_reader')->willReturn(true);
        $container->getDefinition(ControllerListener::class)->willReturn($listener);

        $container->removeDefinition(AnnotationReaderPass::READER_ID)->shouldBeCalled();
        $listener
            ->setArgument('$annotationReader', Argument::that(
                function ($reference) {
                    return $reference instanceof Reference && 'annotation_reader' === (string) $reference;
                }
            ))
            ->shouldBeCalled()
            ->willReturn($listener);

        $container->setAlias(Argument::cetera())->shouldNotBeCalled();

        $this->process($container);
    }

    public function it_exposes_its_own_reader_once_symfony_removed_the_service(ContainerBuilder $container)
    {
        $container->hasDefinition(ControllerListener::class)->willReturn(true);
        $container->has('annotation_reader')->willReturn(false);
        $container->has(Reader::class)->willReturn(false);

        $container->setAlias(Reader::class, AnnotationReaderPass::READER_ID)->shouldBeCalled();
        $container->removeDefinition(Argument::any())->shouldNotBeCalled();

        $this->process($container);
    }

    public function it_leaves_an_already_registered_reader_alias_alone(ContainerBuilder $container)
    {
        $container->hasDefinition(ControllerListener::class)->willReturn(true);
        $container->has('annotation_reader')->willReturn(false);
        $container->has(Reader::class)->willReturn(true);

        $container->setAlias(Argument::cetera())->shouldNotBeCalled();

        $this->process($container);
    }

    public function it_does_nothing_when_the_controller_listener_is_not_registered(ContainerBuilder $container)
    {
        $container->hasDefinition(ControllerListener::class)->willReturn(false);

        $container->setAlias(Argument::cetera())->shouldNotBeCalled();
        $container->removeDefinition(Argument::any())->shouldNotBeCalled();
        $container->getDefinition(Argument::any())->shouldNotBeCalled();

        $this->process($container);
    }
}
