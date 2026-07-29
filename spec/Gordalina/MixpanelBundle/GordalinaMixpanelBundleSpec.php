<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle;

use Gordalina\MixpanelBundle\DependencyInjection\Compiler\AnnotationReaderPass;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GordalinaMixpanelBundleSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\GordalinaMixpanelBundle');
    }

    /**
     * Without this pass the bundle would fall back to its own private reader,
     * which silently discards annotations enriched by other listeners.
     */
    public function it_registers_the_annotation_reader_pass(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(Argument::type(AnnotationReaderPass::class), Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($container);

        $this->build($container);
    }
}
