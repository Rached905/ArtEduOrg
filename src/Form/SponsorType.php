<?php

namespace App\Form;

use App\Entity\Sponsor;
use App\Entity\Event;
use App\Enum\TypeSponsorEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'pattern' => '[a-zA-ZÀ-ÿ\\s\\-\\\']+',
                    'title' => 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'pattern' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.(com|tn|fr|net|org)',
                    'title' => 'L\'email doit être valide et se terminer par .com, .tn, .fr, .net ou .org',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|tn|fr|net|org)$/i',
                        'message' => 'L\'email doit être valide et se terminer par .com, .tn, .fr, .net ou .org',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'attr' => [
                    'pattern' => '[0-9+\\-\\s()]+',
                    'title' => 'Le téléphone ne peut contenir que des chiffres, espaces, tirets, parenthèses et le signe +',
                    'minlength' => 8,
                    'maxlength' => 20,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est obligatoire']),
                    new Assert\Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => 'Le téléphone doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[0-9+\-\s()]+$/',
                        'message' => 'Le téléphone ne peut contenir que des chiffres, espaces, tirets, parenthèses et le signe +',
                    ]),
                ],
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
            ])
            ->add('sponsorType', ChoiceType::class, [
                'label' => 'Type de sponsorisation',
                'required' => true,
                'mapped' => false,
                'choices' => [
                    'Sponsor de plateforme' => 'platform',
                    'Sponsor pour événement' => 'event',
                ],
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'sponsor_type_choice',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type de sponsorisation est obligatoire']),
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez téléverser une image valide (JPEG, PNG, GIF ou WebP)',
                        'maxSizeMessage' => 'L\'image ne peut pas dépasser 2 Mo',
                    ]),
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://example.com',
                ],
                'constraints' => [
                    new Assert\Url(['message' => 'L\'URL n\'est pas valide']),
                ],
            ])
        ;

        // Ajouter le champ événement de manière dynamique
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $sponsor = $event->getData();
            
            // Déterminer si c'est un sponsor d'événement existant
            $isEventSponsor = $sponsor && $sponsor->getId() && !$sponsor->getEvents()->isEmpty();
            $selectedEvent = null;
            if ($isEventSponsor) {
                $selectedEvent = $sponsor->getEvents()->first();
            }
            
            $form->add('event', EntityType::class, [
                'class' => Event::class,
                'label' => 'Événement',
                'required' => false,
                'mapped' => false,
                'choice_label' => 'title',
                'placeholder' => 'Sélectionner un événement',
                'data' => $selectedEvent,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.title', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'sponsor_event_select',
                    'style' => $isEventSponsor ? '' : 'display: none;',
                ],
            ]);
            
            // Pré-remplir le type de sponsorisation
            if ($isEventSponsor) {
                $form->get('sponsorType')->setData('event');
            } else {
                $form->get('sponsorType')->setData('platform');
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            
            // Si le type de sponsorisation est "event", rendre l'événement obligatoire
            if (isset($data['sponsorType']) && $data['sponsorType'] === 'event') {
                $form->add('event', EntityType::class, [
                    'class' => Event::class,
                    'label' => 'Événement',
                    'required' => true,
                    'mapped' => false,
                    'choice_label' => 'title',
                    'placeholder' => 'Sélectionner un événement',
                    'query_builder' => function ($er) {
                        return $er->createQueryBuilder('e')
                            ->orderBy('e.title', 'ASC');
                    },
                    'attr' => [
                        'class' => 'form-select',
                        'id' => 'sponsor_event_select',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Veuillez sélectionner un événement']),
                    ],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}


