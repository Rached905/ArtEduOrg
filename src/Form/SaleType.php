<?php

namespace App\Form;

use App\Entity\Sale;
use App\Enum\SaleType as SaleTypeEnum;
use App\Enum\SaleStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SaleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de vente',
                'required' => true,
                'choices' => [
                    'Vente' => SaleTypeEnum::VENTE,
                    'Ticket' => SaleTypeEnum::TICKET,
                    'Échange' => SaleTypeEnum::ECHANGE,
                ],
                'choice_value' => fn (?SaleTypeEnum $type) => $type?->value,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Sélectionner un type',
                'attr' => [
                    'class' => 'form-control',
                    'required' => 'required'
                ]
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Tableau d\'art moderne',
                    'class' => 'form-control',
                    'required' => 'required'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description détaillée du produit...',
                    'class' => 'form-control',
                    'rows' => 5
                ]
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant',
                'required' => false,
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0'
                ]
            ])
            ->add('contactInfo', TextType::class, [
                'label' => 'Contact (Email ou Téléphone)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'exemple@email.com ou +33 6 12 34 56 78',
                    'class' => 'form-control'
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Localisation',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ville, Adresse...',
                    'class' => 'form-control'
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image principale (optionnel)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF ou WebP).'
                    ])
                ]
            ])
            ->add('images', FileType::class, [
                'label' => 'Galerie d\'images (jusqu\'à 5 images)',
                'required' => false,
                'mapped' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                    'multiple' => 'multiple'
                ],
                'constraints' => [
                    new Assert\All([
                        new Assert\File([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp'
                            ],
                            'mimeTypesMessage' => 'Veuillez uploader des images valides (JPEG, PNG, GIF ou WebP).'
                        ])
                    ]),
                    new Assert\Count([
                        'max' => 5,
                        'maxMessage' => 'Vous ne pouvez pas uploader plus de {{ limit }} images.'
                    ])
                ]
            ]);

        // Ajouter le statut uniquement en mode édition
        if ($options['is_edit']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'required' => true,
                'choices' => [
                    'En attente' => SaleStatus::EN_ATTENTE,
                    'Payé' => SaleStatus::PAYE,
                    'Annulé' => SaleStatus::ANNULE,
                ],
                'choice_value' => fn (?SaleStatus $status) => $status?->value,
                'expanded' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-control',
                    'required' => 'required'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sale::class,
            'is_edit' => false,
        ]);
    }
}

