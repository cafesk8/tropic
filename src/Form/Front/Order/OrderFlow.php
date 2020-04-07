<?php

declare(strict_types=1);

namespace App\Form\Front\Order;

use App\Model\Customer\User\CustomerUser;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\StepInterface;
use Shopsys\FrameworkBundle\Model\Country\Country;

class OrderFlow extends FormFlow
{
    /**
     * @var bool
     */
    protected $allowDynamicStepNavigation = true;

    /**
     * @var int
     */
    private $domainId;

    /**
     * @var \App\Model\Country\Country|null
     */
    private $country;

    /**
     * @param int $domainId
     */
    public function setDomainId($domainId)
    {
        $this->domainId = $domainId;
    }

    /**
     * @param \App\Model\Country\Country|null $country
     */
    public function setTransportCountry(?Country $country): void
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'order';
    }

    /**
     * @return array
     */
    protected function loadStepsConfig()
    {
        return [
            [
                'skip' => true, // the 1st step is the shopping cart
                'form_options' => ['js_validation' => false],
            ],
            [
                'form_type' => TransportAndPaymentFormType::class,
                'form_options' => [
                    'domain_id' => $this->domainId,
                    'country' => $this->country,
                ],
            ],
            [
                'form_type' => PersonalInfoFormType::class,
                'form_options' => [
                    'domain_id' => $this->domainId,
                    'country' => $this->country,
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    protected function determineInstanceId()
    {
        // Make instance ID constant as we do not want multiple instances of OrderFlow.
        return $this->getInstanceId();
    }

    /**
     * @param int $step
     * @param array $options
     * @return array
     */
    public function getFormOptions($step, array $options = [])
    {
        $options = parent::getFormOptions($step, $options);

        // Remove default validation_groups by step.
        // Otherwise FormFactory uses is instead of FormType's callback.
        if (isset($options['validation_groups'])) {
            unset($options['validation_groups']);
        }

        return $options;
    }

    public function saveSentStepData()
    {
        $stepData = $this->retrieveStepData();

        foreach ($this->getSteps() as $step) {
            $stepForm = $this->createFormForStep($step->getNumber());
            if ($this->getRequest()->request->has($stepForm->getName())) {
                $stepData[$step->getNumber()] = $this->getRequest()->request->get($stepForm->getName());
            }
        }

        $this->saveStepData($stepData);
    }

    /**
     * @return bool
     */
    public function isBackToCartTransition()
    {
        return $this->getRequestedStepNumber() === 2
            && $this->getRequestedTransition() === self::TRANSITION_BACK;
    }

    /**
     * @param mixed $formData
     */
    public function bind($formData)
    {
        parent::bind($formData); // load current step number

        $firstInvalidStep = $this->getFirstInvalidStep();
        if ($firstInvalidStep !== null && $this->getCurrentStepNumber() > $firstInvalidStep->getNumber()) {
            $this->changeRequestToStep($firstInvalidStep);
            parent::bind($formData); // load changed step
        }
    }

    /**
     * @return \Craue\FormFlowBundle\Form\StepInterface|null
     */
    private function getFirstInvalidStep()
    {
        foreach ($this->getSteps() as $step) {
            if (!$this->isStepValid($step)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @param \Craue\FormFlowBundle\Form\StepInterface $step
     * @return bool
     */
    private function isStepValid(StepInterface $step)
    {
        $stepNumber = $step->getNumber();
        $stepsData = $this->retrieveStepData();
        if (array_key_exists($stepNumber, $stepsData)) {
            $stepForm = $this->createFormForStep($stepNumber);
            $stepForm->submit($stepsData[$stepNumber]); // the form is validated here
            return $stepForm->isValid();
        }

        return $step->getFormType() === null;
    }

    /**
     * @param \Craue\FormFlowBundle\Form\StepInterface $step
     */
    private function changeRequestToStep(StepInterface $step)
    {
        $stepsData = $this->retrieveStepData();
        if (array_key_exists($step->getNumber(), $stepsData)) {
            $stepData = $stepsData[$step->getNumber()];
        } else {
            $stepData = [];
        }

        $request = $this->getRequest()->request;
        $requestParameters = $request->all();
        $requestParameters['flow_order_step'] = $step->getNumber();
        $requestParameters[$step->getFormType()] = $stepData;
        $request->replace($requestParameters);
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     */
    public function mergePreviouslySavedFormDataWithLoggedUserData(CustomerUser $customerUser): void
    {
        $orderFormData = $this->retrieveStepData();

        $orderStepWithAddressData = 3;

        if (!array_key_exists($orderStepWithAddressData, $orderFormData)) {
            return;
        }
        $oldFormAddressData = $orderFormData[$orderStepWithAddressData];

        $newFormAddressData = $oldFormAddressData;

        if ($oldFormAddressData['firstName'] === '') {
            $newFormAddressData['firstName'] = $customerUser->getFirstName();
        }
        if ($oldFormAddressData['lastName'] === '') {
            $newFormAddressData['lastName'] = $customerUser->getLastName();
        }
        if ($oldFormAddressData['email'] === '') {
            $newFormAddressData['email'] = $customerUser->getEmail();
        }
        if ($oldFormAddressData['telephone'] === '') {
            $newFormAddressData['telephone'] = $customerUser->getTelephone();
        }

        if ($customerUser->getDefaultDeliveryAddress() !== null) {
            if ($oldFormAddressData['street'] === '') {
                $newFormAddressData['street'] = $customerUser->getDefaultDeliveryAddress()->getStreet();
            }
            if ($oldFormAddressData['city'] === '') {
                $newFormAddressData['city'] = $customerUser->getDefaultDeliveryAddress()->getCity();
            }
            if ($oldFormAddressData['postcode'] === '') {
                $newFormAddressData['postcode'] = $customerUser->getDefaultDeliveryAddress()->getPostcode();
            }
            if ($oldFormAddressData['country'] === '') {
                $newFormAddressData['country'] = $customerUser->getDefaultDeliveryAddress()->getCountry();
            }
        }

        $newFormAddressData = $this->mergeFormCompanyDataWithLoggedUserCompanyData($oldFormAddressData, $newFormAddressData, $customerUser);

        $orderFormData[$orderStepWithAddressData] = $newFormAddressData;

        $this->saveStepData($orderFormData);
    }

    /**
     * @param array $oldFormAddressData
     * @param array $newFormAddressData
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return array
     */
    private function mergeFormCompanyDataWithLoggedUserCompanyData(array $oldFormAddressData, array $newFormAddressData, CustomerUser $customerUser)
    {
        if ($customerUser->getCustomer()->getBillingAddress()->isCompanyCustomer()) {
            if ($oldFormAddressData['companyName'] === '') {
                $newFormAddressData['companyName'] = $customerUser->getCustomer()->getBillingAddress()->getCompanyName();
            }
            if ($oldFormAddressData['companyNumber'] === '') {
                $newFormAddressData['companyNumber'] = $customerUser->getCustomer()->getBillingAddress()->getCompanyNumber();
            }
            if ($oldFormAddressData['companyTaxNumber'] === '') {
                $newFormAddressData['companyTaxNumber'] = $customerUser->getCustomer()->getBillingAddress()->getCompanyTaxNumber();
            }

            if ($oldFormAddressData['street'] === '') {
                $newFormAddressData['street'] = $customerUser->getCustomer()->getBillingAddress()->getStreet();
            }
            if ($oldFormAddressData['city'] === '') {
                $newFormAddressData['city'] = $customerUser->getCustomer()->getBillingAddress()->getCity();
            }
            if ($oldFormAddressData['postcode'] === '') {
                $newFormAddressData['postcode'] = $customerUser->getCustomer()->getBillingAddress()->getPostcode();
            }
            if ($oldFormAddressData['country'] === '') {
                $newFormAddressData['country'] = $customerUser->getCustomer()->getBillingAddress()->getCountry();
            }
        }

        return $newFormAddressData;
    }
}
