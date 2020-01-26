<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command;

use Roave\BetterReflection\Reflection\ReflectionObject;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Shopsys\FrameworkBundle\Command\ExtendedClassesAnnotationsCommand as BaseExtendedClassesAnnotationsCommand;

class ExtendedClassesAnnotationsCommand extends BaseExtendedClassesAnnotationsCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:extended-classes:annotations';

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
