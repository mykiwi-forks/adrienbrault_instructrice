<?php

declare(strict_types=1);

namespace AdrienBrault\Instructrice\Tests;

use AdrienBrault\Instructrice\InstructriceFactory;
use Limenius\Liform\Form\Extension\AddLiformExtension;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;

class FirstTest extends TestCase
{
    public function test(): void
    {
        // This form could be mapped to a class/model, have its own type class etc
        $peopleType = new class() extends AbstractType {
            public function buildForm(FormBuilderInterface $builder, array $options): void
            {
                $builder
                    ->add('name', TextType::class)
                    ->add('biography', TextareaType::class, [
                        'liform' => [
                            'description' => 'Succintly describe the person\'s life.',
                        ],
                        'constraints' => [
                            new Length([
                                'min' => 75,
                            ]),
                            new Regex(
                                '/ (et|de|pour|est|connu) /i',
                                message: 'The sentences must be written in french, not english.'
                            ),
                            new Regex(
                                '/DAMN/',
                                message: 'You must include "DAMN".',
                            ),
                        ],
                    ])
                ;
            }
        };

        $instructrice = InstructriceFactory::create(
            logger: new Logger('instructrice', [
                new ConsoleHandler(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG)),
            ])
        );

        $form = $instructrice->handleFormLLMSubmit(
            context: 'Jason fried, david cramer',
            newForm: fn () => $this->getArrayForm($peopleType),
            retries: 3
        );

        dump('final result', $form->getData());

        $this->assertTrue($form->isValid());
    }

    /**
     * @param list<FormTypeInterface> $types
     */
    public function getFormFactory(array $types): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addTypeExtension(new AddLiformExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addTypes($types)
            ->getFormFactory();
    }

    private function getArrayForm(AbstractType $peopleType): FormInterface
    {
        $formFactory = $this->getFormFactory([
            $peopleType,
        ]);

        $form = $formFactory
            ->createBuilder()
            ->add('people', CollectionType::class, [
                'entry_type' => get_class($peopleType),
                'allow_add' => true,
            ])
            ->getForm();
        return $form;
    }
}
