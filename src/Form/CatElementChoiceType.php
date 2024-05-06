<?php

namespace App\Form;

use App\Enum\CatColor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CatElementChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('elements', ChoiceType::class, [
                'choices'  => $options['choices'],
                'expanded' => true,
                'multiple' => false
            ])
            ->add('submit', SubmitType::class, ['label' => 'next step'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'choices' => []
        ]);
    }
}
