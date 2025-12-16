<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Enum\StatusReclamation;
use App\Enum\TypeReclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('objet')
            ->add('description', TextareaType::class)
            ->add('typeReclamation', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($c) => ucfirst($c->value), TypeReclamation::cases()),
                    TypeReclamation::cases()
                ),
                'placeholder' => 'Sélectionnez un type',
            ])
            ->add('statusReclamation', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($c) => ucfirst(str_replace('_', ' ', $c->value)), StatusReclamation::cases()),
                    StatusReclamation::cases()
                ),
                'disabled' => true,
                'mapped' => false, // ← IMPORTANT: Ne pas mapper ce champ à l'entité
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}