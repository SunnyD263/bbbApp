<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class InboundUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('html_file', FileType::class, [
            'label' => 'Vyber HTML soubor',
            'mapped' => false,
            'required' => true,
            'allow_file_upload' => true,
            'constraints' => [
                new File(
                    maxSize: '5M',
                    mimeTypes: ['text/html', 'text/plain', 'application/xhtml+xml'],
                    mimeTypesMessage: 'Nahraj HTML soubor.'
                ),
            ],
        ]);
    }
}
