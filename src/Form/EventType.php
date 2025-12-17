<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Entity\Sponsor;
use App\Repository\SponsorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $eventEntity = $event->getData();
                $form = $event->getForm();

                if ($eventEntity->getStartDate() && $eventEntity->getEndDate()) {
                    if ($eventEntity->getStartDate() >= $eventEntity->getEndDate()) {
                        $form->get('endDate')->addError(new FormError('La date de fin doit être postérieure à la date de début'));
                    }
                }
            });

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'required' => true,
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire']),
                ],
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('type', TextType::class, [
                'label' => 'Type',
                'required' => false,
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacité',
                'required' => false,
                'constraints' => [
                    new Assert\Positive(['message' => 'La capacité doit être positive']),
                ],
            ])
            ->add('price', TextType::class, [
                'label' => 'Prix',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^\d+(\.\d{1,2})?$/',
                        'message' => 'Le prix doit être un nombre décimal valide (ex: 10.50)',
                    ]),
                ],
            ])
            ->add('latitude', HiddenType::class, [
                'required' => false,
            ])
            ->add('longitude', HiddenType::class, [
                'required' => false,
            ])
            ->add('address', HiddenType::class, [
                'required' => false,
            ])
            ->add('sponsors', EntityType::class, [
                'class' => Sponsor::class,
                'choice_label' => 'name',
                'label' => 'Sponsors',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
                'query_builder' => function (SponsorRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            // Si l'événement a un ID, c'est une modification, on permet de changer le statut
            if ($data && $data->getId() !== null) {
                $form->add('status', EnumType::class, [
                    'class' => EventStatus::class,
                    'label' => 'Statut',
                    'choice_label' => function (EventStatus $status) {
                        return match ($status) {
                            EventStatus::PROGRAMME => 'Programmé',
                            EventStatus::EN_COURS => 'En cours',
                            EventStatus::TERMINE => 'Terminé',
                            EventStatus::ANNULE => 'Annulé',
                        };
                    },
                    'required' => false,
                    'placeholder' => 'Sélectionnez un statut',
                    'attr' => ['class' => 'form-select'],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}


