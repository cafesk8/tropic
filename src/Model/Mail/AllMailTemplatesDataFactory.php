<?php

declare(strict_types=1);

namespace App\Model\Mail;

use Shopsys\FrameworkBundle\Model\Mail\AllMailTemplatesData as BaseAllMailTemplatesData;

class AllMailTemplatesDataFactory
{
    /**
     * @return \App\Model\Mail\AllMailTemplatesData
     */
    public function create(): AllMailTemplatesData
    {
        return new AllMailTemplatesData();
    }

    /**
     * @param \App\Model\Mail\AllMailTemplatesData $baseAllMailTemplatesData
     * @return \App\Model\Mail\AllMailTemplatesData
     */
    public function createFromBase(BaseAllMailTemplatesData $baseAllMailTemplatesData): AllMailTemplatesData
    {
        $allMailTemplatesData = $this->create();
        $allMailTemplatesData->orderStatusTemplates = $baseAllMailTemplatesData->orderStatusTemplates;
        $allMailTemplatesData->registrationTemplate = $baseAllMailTemplatesData->registrationTemplate;
        $allMailTemplatesData->resetPasswordTemplate = $baseAllMailTemplatesData->resetPasswordTemplate;
        $allMailTemplatesData->personalDataAccessTemplate = $baseAllMailTemplatesData->personalDataAccessTemplate;
        $allMailTemplatesData->personalDataExportTemplate = $baseAllMailTemplatesData->personalDataExportTemplate;
        $allMailTemplatesData->domainId = $baseAllMailTemplatesData->domainId;

        return $allMailTemplatesData;
    }
}
