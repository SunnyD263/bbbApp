<?php

namespace App\Form\Activa;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class InboundUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pdf_file', FileType::class, [
            'label' => 'Vyber PDF soubor',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => ['application/pdf'],
                    'mimeTypesMessage' => 'Nahraj prosím PDF soubor.',
                ]),
            ],
        ]);
    }
}
