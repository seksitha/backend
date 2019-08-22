<?php

/**
 * Copyright © Bold Brand Commerce Sp. z o.o. All rights reserved.
 * See license.txt for license details.
 */

declare(strict_types = 1);

namespace Ergonode\Workflow\Infrastructure\Validator;

use Ergonode\Workflow\Domain\Entity\StatusId;
use Ergonode\Workflow\Domain\Repository\StatusRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 */
class StatusNotExistsValidator extends ConstraintValidator
{
    /**
     * @var StatusRepositoryInterface
     */
    private $repository;

    /**
     * @param StatusRepositoryInterface $repository
     */
    public function __construct(StatusRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param mixed                     $value
     * @param WorkflowExists|Constraint $constraint
     *
     * @throws \ReflectionException
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof StatusNotExists) {
            throw new UnexpectedTypeException($constraint, StatusNotExists::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        $workflow = null;
        if (StatusId::isValid($value)) {
            $workflow = $this->repository->load(new StatusId($value));
        }

        if (!$workflow) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
