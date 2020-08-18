# Výpisy produktů

|                    Výpis                   |      Název v kódu      |       Typ       |        Macro        |     Kde je na FE    |         Elastic/DB        |
|:------------------------------------------:|:----------------------:|:---------------:|:-------------------:|:-------------------:|:-------------------------:|
| Tento měsíc máme v akci                    | topProducts            |  horizontalList |   productListMacro  | Homepage, Předvýpis kategorie |     databáze    |
| Cenová bomba                               | priceBombProducts      |  horizontalList |   productListMacro  |       Homepage      |          databáze         |
| Nejoblíbenější kousky                      | bestSelling            |  horizontalList |   productListMacro  | Předvýpis kategorie |       ElasticSearch       |
| K produktu budete potřebovat               | accesories             |  horizontalList |   productListMacro  |   Detail produktu   |          databáze         |
| Naposledy navštívené produkty              | LastVisitedProducts    | lastVisitedList |   productListMacro  |     Nad patičkou    |       ElasticSearch       |
| V článku píšeme o těchto produktech (blog) | articleProducts        |   verticalList  |   productListMacro  |   V článku na boku  | databáze - není přes view |
| Výpis vyhledávání                          |                        |       list      |   productListMacro  |                     |       ElasticSearch       |
| Výpis kategorie                            |                        |       list      |   productListMacro  |                     |       ElasticSearch       |
| Detail setu                                | product.setItems     |  productSetView | productSetViewMacro |                     |          databáze         |
| Výpis setu - vyhledávání/kategorie         | productView.setItems |  productSetView | productSetViewMacro |                     |       ElasticSearch       |
| Novinky                                    | newProducts            |  horizontalList |   productListMacro  |       Homepage      |       ElasticSearch       |
