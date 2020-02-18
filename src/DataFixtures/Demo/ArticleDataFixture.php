<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Article\Article;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Article\ArticleData;
use Shopsys\FrameworkBundle\Model\Article\ArticleDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Article\ArticleFacade;

class ArticleDataFixture extends AbstractReferenceFixture
{
    public const ARTICLE_TERMS_AND_CONDITIONS = 'article_terms_and_conditions';
    public const ARTICLE_PRIVACY_POLICY = 'article_privacy_policy';
    public const ARTICLE_COOKIES = 'article_cookies';
    public const ARTICLE_PRODUCT_SIZE = 'article_product_size';

    protected const ATTRIBUTE_NAME_KEY = 'name';
    protected const ATTRIBUTE_TEXT_KEY = 'text';
    protected const ATTRIBUTE_PLACEMENT_KEY = 'placement';
    protected const REFERENCE_NAME_KEY = 'referenceName';

    /**
     * @var \App\Model\Article\ArticleFacade
     */
    protected $articleFacade;

    /**
     * @var \App\Model\Article\ArticleDataFactory
     */
    protected $articleDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Article\ArticleFacade $articleFacade
     * @param \App\Model\Article\ArticleDataFactory $articleDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        ArticleFacade $articleFacade,
        ArticleDataFactoryInterface $articleDataFactory,
        Domain $domain
    ) {
        $this->articleFacade = $articleFacade;
        $this->articleDataFactory = $articleDataFactory;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $data = $this->getDataForArticles($domainConfig->getLocale());
            $this->createArticlesFromArray($data, $domainConfig->getId());
        }

        if ($this->domain->isMultidomain()) {
            $this->changeDataForSecondDomain();
        }
    }

    /**
     * @param string $locale
     * @return string[][]
     */
    protected function getDataForArticles(string $locale): array
    {
        return [
            [
                self::ATTRIBUTE_NAME_KEY => t('Novinky', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus felis nisi, tincidunt sollicitudin augue eu, laoreet blandit sem. Donec rutrum augue a elit imperdiet, eu vehicula tortor porta. Vivamus pulvinar sem non auctor dictum. Morbi eleifend semper enim, eu faucibus tortor posuere vitae. Donec tincidunt ipsum ullamcorper nisi accumsan tincidunt. Aenean sed velit massa. Nullam interdum eget est ut convallis. Vestibulum et mauris condimentum, rutrum sem congue, suscipit arcu.\nSed tristique vehicula ipsum, ut vulputate tortor feugiat eu. Vivamus convallis quam vulputate faucibus facilisis. Curabitur tincidunt pulvinar leo, eu dapibus augue lacinia a. Fusce sed tincidunt nunc. Morbi a nisi a odio pharetra laoreet nec eget quam. In in nisl tortor. Ut fringilla vitae lectus eu venenatis. Nullam interdum sed odio a posuere. Fusce pellentesque dui vel tortor blandit, a dictum nunc congue.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_TOP_MENU,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Jak nakupovat', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus felis nisi, tincidunt sollicitudin augue eu, laoreet blandit sem. Donec rutrum augue a elit imperdiet, eu vehicula tortor porta. Vivamus pulvinar sem non auctor dictum. Morbi eleifend semper enim, eu faucibus tortor posuere vitae. Donec tincidunt ipsum ullamcorper nisi accumsan tincidunt. Aenean sed velit massa. Nullam interdum eget est ut convallis. Vestibulum et mauris condimentum, rutrum sem congue, suscipit arcu.\nSed tristique vehicula ipsum, ut vulputate tortor feugiat eu. Vivamus convallis quam vulputate faucibus facilisis. Curabitur tincidunt pulvinar leo, eu dapibus augue lacinia a. Fusce sed tincidunt nunc. Morbi a nisi a odio pharetra laoreet nec eget quam. In in nisl tortor. Ut fringilla vitae lectus eu venenatis. Nullam interdum sed odio a posuere. Fusce pellentesque dui vel tortor blandit, a dictum nunc congue.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_TOP_MENU,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Obchodní podmínky', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Morbi posuere mauris dolor, quis accumsan dolor ullamcorper eget. Phasellus at elementum magna, et pretium neque. Praesent tristique lorem mi, eget varius quam aliquam eget. Vivamus ultrices interdum nisi, sed placerat lectus fermentum non. Phasellus ac quam vitae nisi aliquam vestibulum. Sed rhoncus tortor a arcu sagittis placerat. Nulla lectus nunc, ultrices ac faucibus sed, accumsan nec diam. Nam auctor neque quis tincidunt tempus. Nunc eget risus tristique, lobortis metus vitae, pellentesque leo. Vivamus placerat turpis ac dolor vehicula tincidunt. Sed venenatis, ante id ultrices convallis, lacus elit porttitor dolor, non porta risus ipsum ac justo. Integer id pretium quam, id placerat nulla.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_FOOTER,
                self::REFERENCE_NAME_KEY => self::ARTICLE_TERMS_AND_CONDITIONS,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Zásady ochrany osobních údajů', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Morbi posuere mauris dolor, quis accumsan dolor ullamcorper eget. Phasellus at elementum magna, et pretium neque. Praesent tristique lorem mi, eget varius quam aliquam eget. Vivamus ultrices interdum nisi, sed placerat lectus fermentum non. Phasellus ac quam vitae nisi aliquam vestibulum. Sed rhoncus tortor a arcu sagittis placerat. Nulla lectus nunc, ultrices ac faucibus sed, accumsan nec diam. Nam auctor neque quis tincidunt tempus. Nunc eget risus tristique, lobortis metus vitae, pellentesque leo. Vivamus placerat turpis ac dolor vehicula tincidunt. Sed venenatis, ante id ultrices convallis, lacus elit porttitor dolor, non porta risus ipsum ac justo. Integer id pretium quam, id placerat nulla.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_NONE,
                self::REFERENCE_NAME_KEY => self::ARTICLE_PRIVACY_POLICY,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Cookies', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Morbi posuere mauris dolor, quis accumsan dolor ullamcorper eget. Phasellus at elementum magna, et pretium neque. Praesent tristique lorem mi, eget varius quam aliquam eget. Vivamus ultrices interdum nisi, sed placerat lectus fermentum non. Phasellus ac quam vitae nisi aliquam vestibulum. Sed rhoncus tortor a arcu sagittis placerat. Nulla lectus nunc, ultrices ac faucibus sed, accumsan nec diam. Nam auctor neque quis tincidunt tempus. Nunc eget risus tristique, lobortis metus vitae, pellentesque leo. Vivamus placerat turpis ac dolor vehicula tincidunt. Sed venenatis, ante id ultrices convallis, lacus elit porttitor dolor, non porta risus ipsum ac justo. Integer id pretium quam, id placerat nulla.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_NONE,
                self::REFERENCE_NAME_KEY => self::ARTICLE_COOKIES,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Kontakt', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Donec at dolor mi. Nullam ornare, massa in cursus imperdiet, felis nisl auctor ante, vel aliquet tortor lacus sit amet ipsum. Proin ultrices euismod elementum. Integer sodales hendrerit tortor, vel semper turpis interdum eu. Phasellus quam tortor, feugiat vel condimentum vel, tristique et ipsum. Duis blandit lectus in odio cursus rutrum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Aliquam pulvinar massa at imperdiet venenatis. Maecenas convallis lobortis quam in fringilla. Mauris gravida turpis eget sapien imperdiet pulvinar. Nunc velit urna, fringilla nec est sit amet, accumsan varius nunc. Morbi sed tincidunt diam, sit amet laoreet nisl. Nulla tempus id lectus non lacinia.\n\nVestibulum interdum adipiscing iaculis. Nunc posuere pharetra velit. Nunc ac ante non massa scelerisque blandit sit amet vel velit. Integer in massa sed augue pulvinar malesuada. Pellentesque laoreet orci augue, in fermentum nisl feugiat ut. Nunc congue et nisi a interdum. Aenean mauris mi, interdum vel lacus et, placerat gravida augue. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed sagittis ipsum et consequat euismod. Praesent a ipsum dapibus, aliquet justo a, consectetur magna. Phasellus imperdiet tempor laoreet. Sed a accumsan lacus, accumsan faucibus dolor. Praesent euismod justo quis ipsum aliquam suscipit. Sed quis blandit urna.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_ABOUT,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Historie společnosti', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Vítr skoro nefouká a tak by se na první pohled mohlo zdát, že se balónky snad vůbec nepohybují. Jenom tak klidně levitují ve vzduchu. Jelikož slunce jasně září a na obloze byste od východu k západu hledali mráček marně, balónky působí jako jakási fata morgána uprostřed pouště. Zkrátka široko daleko nikde nic, jen zelenkavá tráva, jasně modrá obloha a tři křiklavě barevné pouťové balónky, které se téměř nepozorovatelně pohupují ani ne moc vysoko, ani moc nízko nad zemí. Kdyby pod balónky nebyla sytě zelenkavá tráva, ale třeba suchá silnice či beton, možná by bylo vidět jejich barevné stíny - to jak přes poloprůsvitné barevné balónky prochází ostré sluneční paprsky.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_ABOUT,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Kariéra', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('V tuto chvíli nemáme žádné otevřené pozice.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_ABOUT,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Nabídka potisků triček', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Donec at dolor mi. Nullam ornare, massa in cursus imperdiet, felis nisl auctor ante, vel aliquet tortor lacus sit amet ipsum. Proin ultrices euismod elementum. Integer sodales hendrerit tortor, vel semper turpis interdum eu. Phasellus quam tortor, feugiat vel condimentum vel, tristique et ipsum. Duis blandit lectus in odio cursus rutrum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Aliquam pulvinar massa at imperdiet venenatis. Maecenas convallis lobortis quam in fringilla. Mauris gravida turpis eget sapien imperdiet pulvinar. Nunc velit urna, fringilla nec est sit amet, accumsan varius nunc. Morbi sed tincidunt diam, sit amet laoreet nisl. Nulla tempus id lectus non lacinia.\n\nVestibulum interdum adipiscing iaculis. Nunc posuere pharetra velit. Nunc ac ante non massa scelerisque blandit sit amet vel velit. Integer in massa sed augue pulvinar malesuada. Pellentesque laoreet orci augue, in fermentum nisl feugiat ut. Nunc congue et nisi a interdum. Aenean mauris mi, interdum vel lacus et, placerat gravida augue. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed sagittis ipsum et consequat euismod. Praesent a ipsum dapibus, aliquet justo a, consectetur magna. Phasellus imperdiet tempor laoreet. Sed a accumsan lacus, accumsan faucibus dolor. Praesent euismod justo quis ipsum aliquam suscipit. Sed quis blandit urna.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_SERVICES,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Pro obchodníky', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => t('Proti slunci to vypadá, že se slunce pohybuje k západu rychleji než balónky, a možná to tak skutečně je. Nejeden filozof by mohl tvrdit, že balónky se sluncem závodí, ale fyzikové by to jistě vyvrátili. Z fyzikálního pohledu totiž balónky působí zcela nezajímavě. Nejvíc bezpochyby zaujmou děti - jedna malá holčička zrovna včera div nebrečela, že by snad balónky mohly prasknout. A co teprve ta stuha. Stuha, kterou je každý z trojice balónků uvázán, aby se nevypustil.', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_SERVICES,
            ], [
                self::ATTRIBUTE_NAME_KEY => t('Tabulka velikostí pro produkty', [], 'dataFixtures', $locale),
                self::ATTRIBUTE_TEXT_KEY => '',
                self::ATTRIBUTE_PLACEMENT_KEY => Article::PLACEMENT_NONE,
                self::REFERENCE_NAME_KEY => self::ARTICLE_PRODUCT_SIZE,
            ],
        ];
    }

    /**
     * @param array $articles
     * @param int $domainId
     */
    protected function createArticlesFromArray(array $articles, int $domainId): void
    {
        foreach ($articles as $article) {
            $this->createArticleFromArray($article, $domainId);
        }
    }

    /**
     * @param array $data
     * @param int $domainId
     */
    protected function createArticleFromArray(array $data, int $domainId): void
    {
        $articleData = $this->articleDataFactory->create();
        $articleData->domainId = $domainId;
        $articleData->name = $data[self::ATTRIBUTE_NAME_KEY];
        $articleData->text = $data[self::ATTRIBUTE_TEXT_KEY];
        $articleData->placement = $data[self::ATTRIBUTE_PLACEMENT_KEY];

        $this->createArticleFromArticleData($articleData, $data[self::REFERENCE_NAME_KEY] ?? null);
    }

    /**
     * @param \App\Model\Article\ArticleData $articleData
     * @param string|null $referenceName
     */
    protected function createArticleFromArticleData(ArticleData $articleData, ?string $referenceName = null): void
    {
        $article = $this->articleFacade->create($articleData);
        if ($referenceName !== null) {
            $this->addReferenceForDomain($referenceName, $article, $articleData->domainId);
        }
    }

    protected function changeDataForSecondDomain()
    {
        /** @var \App\Model\Article\Article $cookiesArticle */
        $cookiesArticle = $this->getReferenceForDomain(self::ARTICLE_COOKIES, 2);
        $cookiesArticleData = $this->articleDataFactory->createFromArticle($cookiesArticle);
        $cookiesArticleData->placement = Article::PLACEMENT_FOOTER;

        $this->articleFacade->edit($cookiesArticle->getId(), $cookiesArticleData);
    }
}
