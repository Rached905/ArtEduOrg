<?php

namespace App\Form;

use App\Entity\Sponsor;
use App\Entity\Sponsorship;
use App\Enum\SponsorshipTypeEnum;
use App\Enum\TypeSponsorEnum;
use App\Repository\SponsorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SponsorshipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'rows' => 4,
                    'minlength' => 10,
                    'maxlength' => 2000,
                    'class' => 'form-control',
                    'placeholder' => 'Description du parrainage...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('sponsor', EntityType::class, [
                'class' => Sponsor::class,
                'choice_label' => 'name',
                'label' => 'Sponsor',
                'required' => true,
                'query_builder' => function (SponsorRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('type', EnumType::class, [
                'class' => TypeSponsorEnum::class,
                'label' => 'Type',
                'required' => true,
                'choice_label' => fn($choice) => match($choice) {
                    TypeSponsorEnum::ENTREPRISE => 'Entreprise',
                    TypeSponsorEnum::ASSOCIATION => 'Association',
                    TypeSponsorEnum::PARTICULIER => 'Particulier',
                    TypeSponsorEnum::ONG => 'ONG',
                    TypeSponsorEnum::INSTITUTION => 'Institution',
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('sponsorshipType', EnumType::class, [
                'class' => SponsorshipTypeEnum::class,
                'label' => 'Type de parrainage',
                'required' => true,
                'choice_label' => fn($choice) => match($choice) {
                    SponsorshipTypeEnum::FINANCIER => 'Financier',
                    SponsorshipTypeEnum::MATERIEL => 'Matériel',
                    SponsorshipTypeEnum::SERVICE => 'Service',
                    SponsorshipTypeEnum::LOGISTIQUE => 'Logistique',
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'min' => 0.01,
                    'max' => 9999999.99,
                    'step' => 0.01,
                    'class' => 'form-control',
                    'placeholder' => '0.00'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant est obligatoire']),
                    new Assert\Type(['type' => 'float', 'message' => 'Le montant doit être un nombre']),
                    new Assert\Positive(['message' => 'Le montant doit être positif']),
                    new Assert\LessThanOrEqual([
                        'value' => 9999999.99,
                        'message' => 'Le montant ne peut pas dépasser {{ compared_value }}',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsorship::class,
        ]);
    }
}


