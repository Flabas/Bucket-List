<?php

namespace App\Form;

use App\Entity\Wish;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class WishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Ex: Marcher sur le GR20',
                    'maxlength' => 255,
                    'class' => 'glass-input',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Détaillez votre idée... (optionnel)',
                    'class' => 'glass-input',
                ],
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'maxlength' => 50,
                    'class' => 'glass-input',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (PNG/JPG/WEBP)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés: JPG, PNG, WEBP.',
                    ]),
                ],
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publier ?',
                'required' => false,
                'mapped' => true,
                'data' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Wish::class,
        ]);
    }
}
