<?php

namespace App\Form;

use App\Entity\Sponsor;
use App\Entity\SponsorContract;
use App\Enum\SponsorLevelEnum;
use App\Repository\SponsorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SponsorContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contractNumber', TextType::class, [
                'label' => 'Numéro de contrat',
                'required' => true,
                'attr' => [
                    'pattern' => '[A-Z0-9\\-_]+',
                    'title' => 'Le numéro de contrat ne peut contenir que des lettres, chiffres, tirets et underscores',
                    'minlength' => 3,
                    'maxlength' => 100,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le numéro de contrat est obligatoire']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le numéro de contrat doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le numéro de contrat ne peut pas dépasser {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9\-_]+$/i',
                        'message' => 'Le numéro de contrat ne peut contenir que des lettres, chiffres, tirets et underscores',
                    ]),
                ],
            ])
            ->add('signedAt', DateType::class, [
                'label' => 'Date de signature',
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'min' => date('Y-m-d'),
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de signature est obligatoire']),
                ],
            ])
            ->add('expiresAt', DateType::class, [
                'label' => 'Date d\'expiration',
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'min' => date('Y-m-d', strtotime('+1 day')),
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'expiration est obligatoire']),
                ],
            ])
            ->add('level', EnumType::class, [
                'class' => SponsorLevelEnum::class,
                'label' => 'Niveau',
                'required' => true,
                'choice_label' => fn($choice) => match($choice) {
                    SponsorLevelEnum::BRONZE => 'Bronze',
                    SponsorLevelEnum::SILVER => 'Silver',
                    SponsorLevelEnum::GOLD => 'Gold',
                    SponsorLevelEnum::PLATINUM => 'Platinum',
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Conditions',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                    'minlength' => 10,
                    'maxlength' => 5000,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Les conditions sont obligatoires']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 5000,
                        'minMessage' => 'Les conditions doivent contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Les conditions ne peuvent pas dépasser {{ limit }} caractères',
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
        ;

        // Validation personnalisée pour vérifier que expiresAt > signedAt
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data instanceof SponsorContract) {
                $signedAt = $data->getSignedAt();
                $expiresAt = $data->getExpiresAt();

                if ($signedAt && $expiresAt && $expiresAt <= $signedAt) {
                    $form->get('expiresAt')->addError(
                        new \Symfony\Component\Form\FormError('La date d\'expiration doit être postérieure à la date de signature')
                    );
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SponsorContract::class,
        ]);
    }
}


