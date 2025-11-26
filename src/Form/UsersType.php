<?php

namespace App\Form;

use App\Entity\Users;
use App\Enum\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => [
                    'placeholder' => 'exemple@domaine.com',
                    'class' => 'form-control'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de Passe',
                'attr' => [
                    'placeholder' => '••••••••',
                    'class' => 'form-control'
                ],
                'required' => false, // Important pour l'édition
                'mapped' => $options['is_edit'] ?? false ? false : true, // Ne pas mapper en édition si vide
            ])
            ->add('fullname', TextType::class, [
                'label' => 'Nom Complet',
                'attr' => [
                    'placeholder' => 'Jean Dupont',
                    'class' => 'form-control'
                ]
            ])
            
            // 🎯 IMPORTANT : convertir ENUM <-> Form avec icônes
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    '🔴 Administrateur' => Role::ADMIN,
                    '🟢 Vendeur'        => Role::USER,
                    '🔵 Client'         => Role::AGENT,
                ],
                'choice_value' => fn (?Role $role) => $role?->value,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Sélectionner un rôle',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            
            // ✅ Nouveau champ isActive
            ->add('isActive', CheckboxType::class, [
                'label' => '✓ Compte actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
            'is_edit' => false, // Pour gérer le cas édition
        ]);
    }
}