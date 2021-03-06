<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Category\Category;
use App\Model\Category\CategoryData;
use Doctrine\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\CategoryDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;

class CategoryDataFixture extends AbstractReferenceFixture
{
    public const CATEGORY_ELECTRONICS = 'category_electronics';
    public const CATEGORY_TV = 'category_tv';
    public const CATEGORY_PHOTO = 'category_photo';
    public const CATEGORY_PRINTERS = 'category_printers';
    public const CATEGORY_PC = 'category_pc';
    public const CATEGORY_PHONES = 'category_phones';
    public const CATEGORY_COFFEE = 'category_coffee';
    public const CATEGORY_BOOKS = 'category_books';
    public const CATEGORY_GARDEN_TOOLS = 'category_garden_tools';
    public const CATEGORY_FOOD = 'category_food';
    public const CATEGORY_SALE = 'category_sale';
    public const CATEGORY_NEWS = 'category_news';

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    protected $categoryFacade;

    /**
     * @var \App\Model\Category\CategoryDataFactory
     */
    protected $categoryDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Category\CategoryDataFactory $categoryDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        CategoryDataFactoryInterface $categoryDataFactory,
        Domain $domain
    ) {
        $this->categoryFacade = $categoryFacade;
        $this->categoryDataFactory = $categoryDataFactory;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /**
         * Root category is created in database migration.
         * @see \Shopsys\FrameworkBundle\Migrations\Version20180603135345
         * @var \App\Model\Category\Category
         */
        $rootCategory = $this->categoryFacade->getRootCategory();
        $categoryData = $this->categoryDataFactory->create();

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Elektro', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainId] = t('Our electronics include devices used for entertainment (flat screen TVs, DVD players, DVD movies, iPods, '
                . 'video games, remote control cars, etc.), communications (telephones, cell phones, email-capable laptops, etc.) '
                . 'and home office activities (e.g., desktop computers, printers, paper shredders, etc.).', [], 'dataFixtures', $locale);
            $categoryData->leftBannerTexts[$locale] = t('Nejeden filozof by mohl tvrdit, ??e bal??nky se sluncem z??vod??, ale fyzikov?? by to jist?? vyvr??tili', [], 'dataFixtures');
            $categoryData->rightBannerTexts[$locale] = t('Red seems a little smaller next to blue and green, but that\'s probably just an optical illusion', [], 'dataFixtures');
            $categoryData->tipShown[$domainId] = true;
            $categoryData->tipName[$domainId] = t('Elektronick?? sou????stky', [], 'dataFixtures');
            $categoryData->tipText[$domainId] = t('Elektronika je elektrotechnick?? obor, kter?? studuje a vyu????v?? p????stroj?? funguj??c??ch na principu ????zen?? toku elektron?? nebo jin??ch elektricky nabit??ch ????stic, zejm??na pomoc?? polovodi????. Toho se dosahuje pomoc?? r??zn??ch elektronick??ch sou????stek.', [], 'dataFixtures');
        }
        $categoryData->preListingCategory = true;
        $categoryData->parent = $rootCategory;
        $this->createCategory($categoryData, self::CATEGORY_ELECTRONICS);

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Televize, audio', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainId] = t('Television or TV is a telecommunication medium used for transmitting sound with moving images in monochrome '
                . '(black-and-white), or in color, and in two or three dimensions', [], 'dataFixtures', $locale);
            $categoryData->leftBannerTexts[$locale] = null;
            $categoryData->rightBannerTexts[$locale] = null;
            $categoryData->containsSaleProducts[$domainId] = true;
            $categoryData->containsNewsProducts[$domainId] = true;
            $categoryData->tipShown[$domainId] = true;
            $categoryData->tipName[$domainId] = t('Elektronick?? televize', [], 'dataFixtures');
            $categoryData->tipText[$domainId] = t('Televize je ??iroce pou????van?? jednosm??rn?? d??lkov?? telekomunika??n?? a plo??n?? vys??l??n?? kombinace obrazu a zvuku a jeho individu??ln?? p????jem pomoc?? televizoru.', [], 'dataFixtures');
        }
        $categoryData->preListingCategory = false;
        $categoryElectronics = $this->getReference(self::CATEGORY_ELECTRONICS);
        $categoryData->parent = $categoryElectronics;
        $this->createCategory($categoryData, self::CATEGORY_TV);

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Fotoapar??ty', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('A camera is an optical instrument for recording or capturing images, which may be stored locally, '
                . 'transmitted to another location, or both.', [], 'dataFixtures', $locale);
            $categoryData->containsSaleProducts[$domainId] = false;
            $categoryData->containsNewsProducts[$domainId] = false;
            $categoryData->tipShown[$domainId] = false;
            $categoryData->tipName[$domainId] = null;
            $categoryData->tipText[$domainId] = null;
        }
        $this->createCategory($categoryData, self::CATEGORY_PHOTO);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Tisk??rny', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('A printer is a peripheral which makes a persistent human readable representation of graphics or text on paper '
                . 'or similar physical media.', [], 'dataFixtures', $locale);
        }
        $this->createCategory($categoryData, self::CATEGORY_PRINTERS);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Po????ta??e & p????slu??enstv??', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('A personal computer (PC) is a general-purpose computer whose size, capabilities, and original sale price '
                . 'make it useful for individuals, and is intended to be operated directly by an end-user with no intervening computer '
                . 'time-sharing models that allowed larger, more expensive minicomputer and mainframe systems to be used by many people, '
                . 'usually at the same time.', [], 'dataFixtures', $locale);
        }
        $this->createCategory($categoryData, self::CATEGORY_PC);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Mobiln?? telefony', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('A telephone is a telecommunications device that permits two or more users to conduct a conversation when they are '
                . 'too far apart to be heard directly. A telephone converts sound, typically and most efficiently the human voice, '
                . 'into electronic signals suitable for transmission via cables or other transmission media over long distances, '
                . 'and replays such signals simultaneously in audible form to its user.', [], 'dataFixtures', $locale);
        }
        $this->createCategory($categoryData, self::CATEGORY_PHONES);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('K??vovary', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('Coffeemakers or coffee machines are cooking appliances used to brew coffee. While there are many different types '
                . 'of coffeemakers using a number of different brewing principles, in the most common devices, coffee grounds '
                . 'are placed in a paper or metal filter inside a funnel, which is set over a glass or ceramic coffee pot, '
                . 'a cooking pot in the kettle family. Cold water is poured into a separate chamber, which is than heated up to the '
                . 'boiling point, and directed into the funnel.', [], 'dataFixtures', $locale);
        }
        $this->createCategory($categoryData, self::CATEGORY_COFFEE);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Knihy', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('A book is a set of written, printed, illustrated, or blank sheets, made of ink, paper, parchment, or other '
                . 'materials, fastened together to hinge at one side. A single sheet within a book is a leaf, and each side of a leaf '
                . 'is a page. A set of text-filled or illustrated pages produced in electronic format is known as an electronic book, '
                . 'or e-book.', [], 'dataFixtures', $locale);
        }
        $categoryData->parent = $rootCategory;
        $this->createCategory($categoryData, self::CATEGORY_BOOKS);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Zahradn?? n????in??', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('A garden tool is any one of many tools made for gardens and gardening and overlaps with the range of tools '
                . 'made for agriculture and horticulture. Garden tools can also be hand tools and power tools.', [], 'dataFixtures', $locale);
        }
        $this->createCategory($categoryData, self::CATEGORY_GARDEN_TOOLS);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('J??dlo', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('Food is any substance consumed to provide nutritional support for the body. It is usually of plant or '
                . 'animal origin, and contains essential nutrients, such as fats, proteins, vitamins, or minerals. The substance '
                . 'is ingested by an organism and assimilated by the organism\'s cells to provide energy, maintain life, '
                . 'or stimulate growth.', [], 'dataFixtures', $locale);
        }
        $this->createCategory($categoryData, self::CATEGORY_FOOD);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('Novinky', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('Nov?? produkty v na???? nab??dce', [], 'dataFixtures', $locale);
            $categoryData->type = Category::NEWS_TYPE;
        }
        $this->createCategory($categoryData, self::CATEGORY_NEWS);

        foreach ($this->domain->getAll() as $domainConfig) {
            $locale = $domainConfig->getLocale();
            $categoryData->name[$locale] = t('V??prodej', [], 'dataFixtures', $locale);
            $categoryData->descriptions[$domainConfig->getId()] = t('Prohl??dn??te si produkty s v??prodejov??mi cenami', [], 'dataFixtures', $locale);
            $categoryData->type = Category::SALE_TYPE;
        }
        $this->createCategory($categoryData, self::CATEGORY_SALE);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     * @param string|null $referenceName
     * @return \App\Model\Category\Category
     */
    protected function createCategory(CategoryData $categoryData, $referenceName = null)
    {
        /** @var \App\Model\Category\Category $category */
        $category = $this->categoryFacade->create($categoryData);
        if ($referenceName !== null) {
            $this->addReference($referenceName, $category);
        }

        return $category;
    }
}
