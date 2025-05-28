<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BookFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("title", TextType::class, [
                "label" => "Название",
                "attr" => ["class" => "form-control"],
            ])
            ->add("author", TextType::class, [
                "label" => "Автор",
                "attr" => ["class" => "form-control"],
            ])
            ->add("coverFile", FileType::class, [
                "label" => "Обложка (PNG, JPG файл)",
                "mapped" => false, 
                "required" => false, 
                "attr" => ["class" => "form-control"],
                "constraints" => [
                    new Image([
                        "maxSize" => "2M",
                        "mimeTypes" => [
                            "image/png",
                            "image/jpeg",
                        ],
                        "mimeTypesMessage" => "Пожалуйста, загрузите валидный PNG или JPG файл.",
                    ])
                ],
            ])
            ->add("bookFile", FileType::class, [
                "label" => "Файл с книгой (до 5МБ)",
                "mapped" => false, 
                "required" => false, 
                "attr" => ["class" => "form-control"],
                "constraints" => [
                    new File([
                        "maxSize" => "5M",
                        // "mimeTypes" => ["application/pdf", "application/x-pdf"], 
                        // "mimeTypesMessage" => "Пожалуйста, загрузите валидный PDF файл.",
                    ])
                ],
            ])
            ->add("readDate", DateType::class, [
                "label" => "Дата прочтения",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])
            ->add("allowDownload", CheckboxType::class, [
                "label" => "Разрешить скачивание",
                "required" => false,
                "attr" => ["class" => "form-check-input"],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $book = $event->getData();
            $form = $event->getForm();

            if ($book && $book->getId()) { // Only add these fields if we are editing an existing book
                if ($book->getCoverPath()) {
                    $form->add("deleteCoverFile", CheckboxType::class, [
                        "label" => "Удалить текущую обложку?",
                        "required" => false,
                        "mapped" => false,
                        "attr" => ["class" => "form-check-input"],
                    ]);
                }
                if ($book->getFilePath()) {
                    $form->add("deleteBookFile", CheckboxType::class, [
                        "label" => "Удалить текущий файл книги?",
                        "required" => false,
                        "mapped" => false,
                        "attr" => ["class" => "form-check-input"],
                    ]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Book::class,
        ]);
    }
}

