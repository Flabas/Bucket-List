<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr' => [
                    'class' => 'form-control custom-input',
                    'placeholder' => 'Votre pseudo'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le pseudo ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 30,
                        'minMessage' => 'Le pseudo doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le pseudo ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control custom-input',
                    'placeholder' => 'votre@email.com'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'email ne peut pas être vide',
                    ]),
                    new Email([
                        'message' => 'Veuillez saisir une adresse email valide',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Nouveau mot de passe (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control custom-input',
                    'placeholder' => 'Laissez vide si vous ne souhaitez pas le changer'
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
