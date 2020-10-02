<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Shopsys\FrameworkBundle\Model\Product\Flag\Flag as BaseFlag;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagData as BaseFlagData;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagDataFactory as BaseFlagDataFactory;

class FlagDataFactory extends BaseFlagDataFactory
{
    /**
     * @return \App\Model\Product\Flag\FlagData
     */
    public function create(): BaseFlagData
    {
        $flagData = new FlagData();
        $this->fillNew($flagData);

        return $flagData;
    }

    /**
     * @param \App\Model\Product\Flag\Flag $flag
     * @return \App\Model\Product\Flag\FlagData
     */
    public function createFromFlag(BaseFlag $flag): BaseFlagData
    {
        $flagData = new FlagData();
        $this->fillFromFlag($flagData, $flag);

        return $flagData;
    }

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     * @param \App\Model\Product\Flag\Flag $flag
     */
    protected function fillFromFlag(BaseFlagData $flagData, BaseFlag $flag)
    {
        parent::fillFromFlag($flagData, $flag);
        $flagData->pohodaId = $flag->getPohodaId();
    }

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     */
    protected function fillNew(BaseFlagData $flagData): void
    {
        parent::fillNew($flagData);
        $flagData->position = 0;
        $flagData->pohodaId = null;
    }
}
