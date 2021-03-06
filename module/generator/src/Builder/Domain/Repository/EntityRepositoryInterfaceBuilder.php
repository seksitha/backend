<?php

/**
 * Copyright © Bold Brand Commerce Sp. z o.o. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types = 1);

namespace Ergonode\Generator\Builder\Domain\Repository;

use Ergonode\EventSourcing\Domain\AbstractAggregateRoot;
use Ergonode\Generator\Builder\BuilderInterface;
use Ergonode\Generator\Builder\FileBuilder;
use Ergonode\Generator\Builder\MethodBuilder;
use Nette\PhpGenerator\PhpFile;

/**
 */
class EntityRepositoryInterfaceBuilder implements BuilderInterface
{
    /**
     * @var FileBuilder
     */
    private $builder;

    /**
     * @var MethodBuilder
     */
    private $methodBuilder;

    /**
     * EntityRepositoryInterfaceBuilder constructor.
     *
     * @param FileBuilder   $builder
     * @param MethodBuilder $methodBuilder
     */
    public function __construct(FileBuilder $builder, MethodBuilder $methodBuilder)
    {
        $this->builder = $builder;
        $this->methodBuilder = $methodBuilder;
    }

    /**
     * @param string $module
     * @param string $entity
     * @param array  $properties
     *
     * @return PhpFile
     */
    public function build(string $module, string $entity, array $properties = []): PhpFile
    {
        $file = $this->builder->build();
        $interfaceName = sprintf('%sRepositoryInterface', $entity);
        $entityClass = sprintf('Ergonode\%s\Domain\Entity\%s', ucfirst($module), $entity);
        $entityIdClass = sprintf('Ergonode\%s\Domain\Entity\%sId', ucfirst($module), $entity);

        $namespace = sprintf('Ergonode\%s\Domain\Repository', ucfirst($module));

        $phpNamespace = $file->addNamespace($namespace);
        $phpNamespace->addUse(sprintf('Ergonode\%s\Domain\Entity\%s', ucfirst($module), $entity));
        $phpNamespace->addUse(sprintf('Ergonode\%s\Domain\Entity\%sId', ucfirst($module), $entity));

        $phpClass = $phpNamespace->addInterface($interfaceName);
        $phpClass->addComment('Autogenerated interface');
        $phpClass
            ->addMember(
                $this
                    ->methodBuilder
                    ->build('load', ['id' => $entityIdClass], AbstractAggregateRoot::class)
            );
        $phpClass->addMember($this->methodBuilder->build('save', ['object' => $entityClass], 'void'));
        $phpClass->addMember($this->methodBuilder->build('exists', ['id' => $entityIdClass], 'bool'));

        return $file;
    }
}
