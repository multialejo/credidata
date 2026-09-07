<?php

namespace App\OpenApi;

use OpenApi\Analysers\AnalyserInterface;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Analysis;
use OpenApi\Context;
use OpenApi\Generator;

class DocBlockAnalyser implements AnalyserInterface
{
    private ?Generator $generator = null;

    public function setGenerator(Generator $generator): static
    {
        $this->generator = $generator;

        return $this;
    }

    public function fromFile(string $filename, Context $context): Analysis
    {
        return (new ReflectionAnalyser([
            new AttributeAnnotationFactory,
            new DocBlockAnnotationFactory,
        ]))->setGenerator($this->generator)->fromFile($filename, $context);
    }

    public static function __set_state(array $properties): self
    {
        return new self;
    }
}
