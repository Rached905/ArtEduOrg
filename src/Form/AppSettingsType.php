<?php

namespace App\Form;

use App\Entity\AppSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siteName', TextType::class, [
                'label' => 'Nom du site',
                'required' => false,
                'attr' => ['placeholder' => 'ex: ArtEduOrg'],
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email de contact',
                'required' => false,
                'attr' => ['placeholder' => 'contact@example.com'],
            ])
            ->add('maintenanceMode', CheckboxType::class, [
                'label' => 'Mode maintenance (désactive l\'accès public)',
                'required' => false,
            ])
            ->add('footerText', TextareaType::class, [
                'label' => 'Texte du pied de page',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => '© 2025 ArtEduOrg. Tous droits réservés.'],
            ])
            ->add('itemsPerPage', IntegerType::class, [
                'label' => 'Nombre d\'éléments par page',
                'attr' => ['min' => 4, 'max' => 100],
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'Description (référencement)',
                'required' => false,
                'attr' => ['rows' => 2, 'maxlength' => 500],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppSettings::class,
        ]);
    }
}
