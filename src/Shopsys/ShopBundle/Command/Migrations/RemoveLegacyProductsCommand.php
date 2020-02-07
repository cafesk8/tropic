<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RemoveLegacyProductsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:remove:legacy-products';

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFacade $productFacade
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->productFacade = $productFacade;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Remove legacy products');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        foreach ($this->getCatnumsFromRemove() as $catnum) {
            $productsForRemove = $this->productFacade->getByCatnum($catnum);

            if (count($productsForRemove) === 0) {
                $symfonyStyleIo->success(sprintf('There are no products with catnum `%s` in database', $catnum));
                continue;
            }

            foreach ($productsForRemove as $product) {
                $productId = $product->getId();
                $this->productFacade->delete($productId);
                $symfonyStyleIo->success(sprintf('Product with ID `%s` and catnum `%s` has been removed', $productId, $catnum));
            }

            $this->entityManager->clear();
        }

        return 0;
    }

    /**
     * @return string[]
     */
    private function getCatnumsFromRemove(): array
    {
        $catnumsForRemove = [];
        $lines = explode("\n", $this->getCatnumsFromRemoveInString());

        foreach ($lines as $line) {
            $catnumsForRemove[] = trim($line);
        }

        return $catnumsForRemove;
    }

    /**
     * @return string
     */
    private function getCatnumsFromRemoveInString(): string
    {
        return '101005
            101006
            101007
            101009
            101010
            101014
            102010
            102011
            102014
            102015
            102017
            102018
            102020
            102022
            111063
            111073
            111087
            111105
            111106
            111108
            111111
            111112
            111113
            111115
            111116
            111118
            111119
            111123
            111124
            111125
            111126
            111127
            111129
            111132
            111134
            111135
            111136
            111137
            111138
            111139
            111140
            111141
            111142
            111145
            111146
            111147
            111148
            111149
            111150
            111151
            111152
            111153
            111154
            111155
            111156
            111157
            111158
            111159
            111160
            111161
            111162
            111163
            111164
            111165
            111166
            111167
            111168
            111169
            111170
            111172
            111174
            111175
            111176
            111177
            111178
            111179
            111180
            111181
            111182
            111183
            111184
            111185
            111186
            111187
            111188
            111190
            111191
            111192
            111193
            111194
            111195
            111196
            111197
            111198
            111199
            111200
            111201
            111202
            111203
            111204
            111205
            111206
            111207
            111208
            111209
            111210
            111211
            111212
            111213
            111214
            111215
            111216
            111217
            111218
            111219
            111220
            111221
            111222
            111225
            111228
            111230
            111231
            111232
            111234
            111235
            111236
            111237
            111238
            111239
            111240
            111241
            111242
            111244
            111245
            111248
            111250
            111251
            111254
            111255
            111257
            111259
            111260
            111261
            111265
            111266
            111267
            111268
            111270
            111271
            111272
            111277
            111278
            111279
            111283
            111311
            111336
            112008
            112009
            112022
            112026
            112033
            112036
            112041
            112052
            112055
            112056
            112057
            112059
            112061
            112062
            112063
            112064
            112065
            112066
            112067
            112068
            112069
            112070
            112071
            112073
            112074
            112075
            112076
            112078
            112079
            112080
            112081
            112082
            112083
            112084
            112085
            112086
            112087
            112088
            112089
            112090
            112091
            112092
            112093
            112095
            112096
            112098
            112099
            112100
            112101
            112103
            112104
            112105
            112106
            112107
            112108
            112111
            112112
            112113
            112114
            112116
            112120
            112126
            113011
            113012
            113013
            113014
            122001
            132004
            132005
            132006
            132010
            132011
            132012
            141015
            141019
            141023
            141025
            141026
            141029
            141030
            141031
            141032
            141033
            141039
            141041
            141050
            142008
            142015
            142017
            142018
            142019
            142024
            142025
            142027
            142028
            142036
            211008
            211009
            211023
            211029
            211033
            211035
            211036
            211037
            211038
            211039
            211043
            211045
            211046
            211047
            211049
            211051
            211053
            211054
            211057
            211058
            211059
            211062
            211064
            212007
            212009
            212012
            212013
            212014
            212015
            232004
            241023
            241024
            241025
            241026
            241028
            241029
            241032
            241033
            241034
            241035
            241036
            241038
            241039
            241040
            241042
            241044
            241045
            241046
            241047
            241050
            241052
            241070
            242016
            242018
            242019
            242020
            242021
            242022
            242024
            242027
            242029
            301006
            301019
            301022
            301023
            301024
            301028
            301030
            301033
            301035
            301036
            301037
            301039
            301044
            302004
            302005
            302007
            302008
            302015
            302016
            311004
            311007
            311009
            311012
            312005
            312008
            312009
            312011
            312012
            321036
            321040
            321041
            321045
            321047
            321048
            321054
            321055
            321058
            321059
            321061
            321062
            321063
            321064
            321065
            321071
            321073
            321074
            321077
            321080
            321081
            321082
            321083
            321085
            321086
            321091
            321093
            321095
            321100
            321104
            321106
            321107
            321110
            321113
            321119
            321122
            321123
            321124
            321135
            321136
            321137
            321138
            321139
            321140
            321144
            322010
            322012
            322029
            322030
            322031
            322032
            322036
            322037
            322039
            322040
            322043
            322045
            322066
            322075
            322076
            401005
            401013
            401019
            401020
            401021
            401025
            402010
            402011
            402013
            402014
            402016
            402017
            411007
            411011
            411029
            411030
            411034
            411035
            411041
            411042
            411043
            411044
            411048
            411049
            411051
            412012
            412018
            412024
            412025
            421007
            421009
            421015
            421017
            421019
            421022
            421023
            421024
            421026
            421028
            421029
            421031
            421032
            421033
            421034
            421035
            421036
            421039
            422012
            422014
            422016
            422017
            422019
            422020
            422023
            422033
            431008
            431012
            431014
            431015
            431016
            431021
            432004
            432005
            501005
            501011
            501013
            501017
            501018
            501020
            501028
            502013
            502014
            502015
            502017
            502020
            502021
            502022
            502026
            502027
            502028
            502030
            502032
            502033
            511042
            511047
            511048
            511049
            511050
            511052
            511053
            511054
            511055
            511056
            511057
            511059
            511061
            511064
            511066
            511072
            511075
            511076
            511081
            511092
            512015
            512020
            512023
            512024
            512025
            512027
            512028
            512029
            512030
            512031
            512033
            512035
            512038
            602011
            602014
            602016
            602017
            612008
            612009
            612011
            612012
            612013
            612014
            612015
            612021
            632006
            702000
            703000
            711000
            800001
            800002
            800702
            800801
            800806
            800902
            800903
            801001
            801002
            801003
            801005
            801600
            802001
            802002
            803000
            810001
            810002
            810003
            810004
            810005
            810007
            810009
            810010
            810012
            810013
            812001
            812003
            812005
            812006
            812008
            812013
            813001
            813002
            813003
            820001
            820002
            820004
            820005
            820019
            820020
            820021
            820022
            820023
            820032
            820200
            820300
            820400
            820500
            820501
            820502
            820503
            820504
            820505
            820506
            820600
            820800
            820900
            821001
            821100
            821102
            821103
            821200
            821300
            821400
            821420
            821430
            821490
            821540
            821550
            821560
            823000
            823100
            824000
            824100
            824410
            824414
            824416
            825001
            826600
            830001
            830002
            830003
            830005
            830008
            830012
            830013
            830600
            831002
            831003
            831004
            840001
            840002
            840003
            841001
            841002
            841010
            841016
            841017
            843000
            843001
            843600
            843800
            845000
            851001
            851005
            851036
            851038
            852003
            852006
            852012
            852025
            852028
            852029
            853002
            853003
            853005
            853008
            853013
            853018
            853019
            860000
            860006
            861001
            861006
            861009
            861013
            861017
            861019
            861021
            861023
            861026
            861027
            861031
            861033
            861113
            861114
            861157
            862001
            863001
            863002
            864055
            864056
            864057
            864063
            864090
            864094
            864100
            864101
            864106
            864109
            864116
            864134
            864143
            864157
            864158
            864159
            865001
            865012
            865019
            865027
            873004
            873005
            875031
            875036
            875038
            876001
            876002
            881001
            881004
            881005
            888002
            888003
            888006
            888009
            890001
            890002
            890004
            891006
            899008
            903001
            903002
            903003
            903008
            903013
            903017
            903018
            905081
            905082
            905123
            905196
            905198
            905199
            905200
            905201
            905202
            905203
            905205
            905206
            905208
            905209
            905210
            905211
            910016
            910023
            914117
            925013
            925014
            925025
            925069
            925070
            925080
            925083
            925084
            945010
            945012
            955H05
            955H06
            955H07
            955H10
            955H14
            955H15
            955H16
            955H18
            955H33
            955H35
            955H37
            955H60
            980006';
    }
}
