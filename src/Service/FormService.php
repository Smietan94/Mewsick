<?php

declare(strict_types=1);

namespace App\Service;

use App\Form\CatElementChoiceType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormService
{
    public function __construct(
        private FormFactoryInterface $formFactory
    ) {
    }

    public function createElementChoiceForm(array $choices): FormInterface
    {
        return $this->formFactory->create(CatElementChoiceType::class, null, ['choices' => $choices]);
    }
}