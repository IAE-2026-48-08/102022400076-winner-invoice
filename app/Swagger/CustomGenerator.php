<?php

namespace App\Swagger;

use L5Swagger\CustomGeneratorInterface;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Generator as OpenApiGenerator;

class CustomGenerator implements CustomGeneratorInterface
{
    public function create(): OpenApiGenerator
    {
        return (new OpenApiGenerator())
            ->setAnalyser(new ReflectionAnalyser([
                new AttributeAnnotationFactory(),
                new DocBlockAnnotationFactory(),
            ]));
    }
}
