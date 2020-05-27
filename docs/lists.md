# Výpisy produktů

|                    Výpis                   |      Název v kódu      |       Typ       |        Macro        |     Kde je na FE    |         Elastic/DB        |
|:------------------------------------------:|:----------------------:|:---------------:|:-------------------:|:-------------------:|:-------------------------:|
| Vybíráme to nejlepší                       | topProducts            |  horizontalList |   productListMacro  |       Homepage      |          databáze         |
| Cenová bomba                               | priceBombProducts      |  horizontalList |   productListMacro  |       Homepage      |          databáze         |
| Nejoblíbenější kousky                      | bestSelling            |  horizontalList |   productListMacro  | Předvýpis kategorie |       ElasticSearch       |
| K produktu budete potřebovat               | accesories             |  horizontalList |   productListMacro  |   Detail produktu   |          databáze         |
| Naposledy navštívené produkty              | LastVisitedProducts    | lastVisitedList |   productListMacro  |     Nad patičkou    |       ElasticSearch       |
| V článku píšeme o těchto produktech (blog) | articleProducts        |   verticalList  |   productListMacro  |   V článku na boku  | databáze - není přes view |
| Výpis vyhledávání                          |                        |       list      |   productListMacro  |                     |       ElasticSearch       |
| Výpis kategorie                            |                        |       list      |   productListMacro  |                     |       ElasticSearch       |
| Detail setu                                | product.groupItems     |  productSetView | productSetViewMacro |                     |          databáze         |
| Výpis setu - vyhledávání/kategorie         | productView.groupItems |  productSetView | productSetViewMacro |                     |       ElasticSearch       |
