<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\Country;
use App\Entity\User;
use App\Repository\UserRepository;

class ProfileFormType extends AbstractType
{
    public function __construct(private UserRepository $userRepository)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'Actual email',
                'disabled' => true,
                'required' => true,
                'mapped' => true
            ])
            ->add('name', TextType::class, [
                'label' => 'New name',
                'required' => true,
                'mapped' => true,
                    
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'bg-white text-black p-2 mb-4'],
                'placeholder' => 'Select your country',
                'required' => true,
                'mapped' => true
            ])
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Old password',
                'mapped' => false,
                'required' => false,
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Passwords must match',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => false,
                'first_options'  => ['label' => 'New password'],
                'second_options' => ['label' => 'Repeat new password'],
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
