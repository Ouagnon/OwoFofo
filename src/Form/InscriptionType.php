<?php

namespace App\Form;

use App\Entity\Joueur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $constructeurFormulaire, array $options): void
    {
        $constructeurFormulaire
            ->add('nom', TextType::class, [
                'label' => 'Pseudo',
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 3, max: 80),
                ],
            ])
            ->add('motDePasseEnClair', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 8, max: 255),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolveur): void
    {
        $resolveur->setDefaults([
            'data_class' => Joueur::class,
        ]);
    }
}
