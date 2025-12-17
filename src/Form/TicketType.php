<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'title',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'événement est requis']),
                ],
            ])
            ->add('price', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est requis']),
                    new Assert\Regex([
                        'pattern' => '/^\d+(\.\d{1,2})?$/',
                        'message' => 'Le prix doit être un nombre décimal valide (ex: 10.50)',
                    ]),
                ],
            ])
            ->add('seatNumber', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('buyerName', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom de l\'acheteur est requis']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('buyerEmail', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email de l\'acheteur est requis']),
                    new Assert\Email(['message' => 'L\'email n\'est pas valide']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La quantité est requise']),
                    new Assert\Positive(['message' => 'La quantité doit être positive']),
                ],
            ])
            ->add('issuedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
                'data' => new \DateTimeImmutable(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'émission est requise']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}


