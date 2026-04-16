<?php

namespace App\Form;

use App\Entity\ModeTournoi;
use App\Entity\Tournoi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TournoiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 3, max: 120),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 5000),
                ],
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('coverImageUrl', TextType::class, [
                'label' => 'Image de couverture',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 2048),
                ],
                'attr' => [
                    'placeholder' => 'https://...'
                ],
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Format',
                'choices' => [
                    'Tournoi classique' => ModeTournoi::LIBRE->value,
                    'Tournoi thème vs thème' => ModeTournoi::THEME_VS_THEME->value,
                ],
                'mapped' => false,
                'placeholder' => 'Choisir un format',
                'required' => true,
                'data' => $options['mode_initial'],
                'disabled' => (bool) ($options['mode_disabled'] ?? false),
            ])
            ->add('nomThemeA', TextType::class, [
                'label' => 'Thème A',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length(max: 120),
                ],
            ])
            ->add('nomThemeB', TextType::class, [
                'label' => 'Thème B',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length(max: 120),
                ],
            ])
            ->add('elementsPayload', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'empty_data' => '[]',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournoi::class,
            'mode_disabled' => false,
            'mode_initial' => null,
        ]);

        $resolver->setAllowedTypes('mode_disabled', 'bool');
        $resolver->setAllowedTypes('mode_initial', ['null', 'string']);
    }
}
