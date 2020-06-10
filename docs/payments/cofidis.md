# Cofidis

- [Dokumentace](https://drive.google.com/file/d/1aorYorlUNX8k-O9nNxLBupsmsoS-Cqpy/view?usp=sharing)
- Technický kontakt Cofidis: [Zdeněk Navrátil](zdenek.navratil@cofidis.cz), +420 234 120 582


- **Je implementovaná verze s extranetem.** 
- E-shop zpracovává pouze počáteční request. Informace o stavu úvěru jsou v extranetové aplikaci.

## Jak to ve zkratce funguje?
- E-shop pošle POST request na URL iplatby (viz dokumentace), která vrátí URL pro přesměrování na "platební bránu"
- Poté je uživatel přesměrován na URL z response.
- Jakmile uživatel vyplní forulář na platební bráně je zpět přesměrován na e-shop.
- Další kontroly stavu úvěru již neprobíhají.

## Kalkulačka
- Implementována pouze jako iframe na kalkulačku iplatba.

## Generování podpisu
- Slouží pro něj třída CofidisSignatureFacade dle dokumentace Cofidisu
- V dokumentaci kapitola 11
- Konfigurace šifrování:
    - Šifra: `TripleDES`
    - Mód: `ECB`
    - Padding: `PKCS5/7`
    - Inicializační vektor: `Nulový (0x00 0x00 0x00 0x00 0x00 0x00 0x00 0x00)`

## Testovací prostředí
- **Na testovacím prostředí je restrikce minimální hodnoty objednávky na 3 000 Kč!**
- I na testovacím prostředí chodí SMS o autorizaci, je třeba tedy při testování uvádět existující číslo a e-mail.
- Pro testovací prostředí platí jiné přístupové údaje:
    - Merchant ID: `15002`
    - Login: `NESS`
    - Cofisun heslo: `F3C0K54C`
    - Inbound heslo: `password`
    - Outbound heslo: `password`
