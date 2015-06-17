<?php

namespace SS6\ShopBundle\Controller\Front;

use SS6\ShopBundle\Model\Customer\CurrentCustomer;
use SS6\ShopBundle\Model\Domain\Domain;
use SS6\ShopBundle\Model\Product\TopProduct\TopProductFacade;
use SS6\ShopBundle\Model\Seo\SeoSettingFacade;
use SS6\ShopBundle\Model\Slider\SliderItemFacade;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {

	/**
	 * @var \SS6\ShopBundle\Model\Customer\CurrentCustomer
	 */
	private $currentCustomer;

	/**
	 * @var \SS6\ShopBundle\Model\Product\TopProduct\TopProductFacade
	 */
	private $topProductFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Seo\SeoSettingFacade
	 */
	private $seoSettingFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Slider\SliderItemFacade
	 */
	private $sliderItemFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Domain\Domain
	 */
	private $domain;

	public function __construct(
		CurrentCustomer $currentCustomer,
		SliderItemFacade $sliderItemFacade,
		TopProductFacade $topProductsFacade,
		SeoSettingFacade $seoSettingFacade,
		Domain $domain
	) {
		$this->currentCustomer = $currentCustomer;
		$this->sliderItemFacade = $sliderItemFacade;
		$this->topProductFacade = $topProductsFacade;
		$this->seoSettingFacade = $seoSettingFacade;
		$this->domain = $domain;
	}

	public function indexAction() {
		$sliderItems = $this->sliderItemFacade->getAllOnCurrentDomain();
		$topProductsDetails = $this->topProductFacade->getAllVisibleProductDetails(
			$this->domain->getId(),
			$this->currentCustomer->getPricingGroup()
		);

		return $this->render('@SS6Shop/Front/Content/Default/index.html.twig', [
			'sliderItems' => $sliderItems,
			'topProductsDetails' => $topProductsDetails,
			'title' => $this->seoSettingFacade->getTitleMainPage($this->domain->getId()),
			'metaDescription' => $this->seoSettingFacade->getDescriptionMainPage($this->domain->getId()),
		]);
	}

}
