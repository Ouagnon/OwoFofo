<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ElementTournoiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $constructeurFormulaire, array $options): void
    {
        $constructeurFormulaire
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 255),
                ],
            ])
            ->add('url', TextType::class, [
                'label' => 'Lien média (image/vidéo)',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 2048),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolveur): void
    {
        $resolveur->setDefaults([
            'data_class' => null,
        ]);
    }
}
