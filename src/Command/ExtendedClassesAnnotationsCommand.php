<?php

declare(strict_types=1);

namespace App\Command;

use Roave\BetterReflection\Reflection\ReflectionObject;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Shopsys\FrameworkBundle\Command\ExtendedClassesAnnotationsCommand as BaseExtendedClassesAnnotationsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtendedClassesAnnotationsCommand extends BaseExtendedClassesAnnotationsCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:extended-classes:annotations';

    /**
     * Overridden to disable spam of deprecation warnings
     * @see https://github.com/shopsys/shopsys/issues/2013
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        error_reporting(error_reporting() & ~E_DEPRECATED);

        return parent::execute($input, $output);
    }

    /**
     * Copy pasted from the framework, added try-catch block for IdentifierNotFound exception so it is possible to run the command on project
     * @see https://github.com/shopsys/shopsys/issues/1612
     * When the linked issue is solved, this overridden class can be removed.
     * @param bool $isDryRun
     * @return array
     */
    protected function addPropertyAndMethodAnnotationsToProjectClasses(bool $isDryRun): array
    {
        $classExtensionMap = $this->classExtensionRegistry->getClassExtensionMap();
        $filesForAddingPropertyOrMethodAnnotations = [];
        foreach ($classExtensionMap as $frameworkClass => $projectClass) {
            $frameworkClassBetterReflection = ReflectionObject::createFromName($frameworkClass);
            $projectClassBetterReflection = ReflectionObject::createFromName($projectClass);

            try {
                $projectClassNecessaryPropertyAnnotationsLines = $this->propertyAnnotationsFactory->getProjectClassNecessaryPropertyAnnotationsLines(
                    $frameworkClassBetterReflection,
                    $projectClassBetterReflection
                );
                $projectClassNecessaryMethodAnnotationsLines = $this->methodAnnotationsAdder->getProjectClassNecessaryMethodAnnotationsLines(
                    $frameworkClassBetterReflection,
                    $projectClassBetterReflection
                );
            } catch (IdentifierNotFound $exception) {
                $projectClassNecessaryPropertyAnnotationsLines = '';
                $projectClassNecessaryMethodAnnotationsLines = '';
            }
            if (!$isDryRun) {
                $this->annotationsAdder->addAnnotationToClass($projectClassBetterReflection, $projectClassNecessaryPropertyAnnotationsLines . $projectClassNecessaryMethodAnnotationsLines);
            }

            if (!empty($projectClassNecessaryPropertyAnnotationsLines) || !empty($projectClassNecessaryMethodAnnotationsLines)) {
                $filesForAddingPropertyOrMethodAnnotations[] = $projectClassBetterReflection->getFileName();
            }
        }

        return $filesForAddingPropertyOrMethodAnnotations;
    }
}
