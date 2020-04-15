# Přenosy

## Základní informace
- Tropic Fishing používá **IS Pohoda**
- [Tabulka s atributy pro přenosy](https://docs.google.com/spreadsheets/u/1/d/1goPq-6M8Zn98U3cGLYoAERHhrpiuHbL6zCKur345348/edit#gid=0)
- [Seznam tabulek v DB Pohody](https://drive.google.com/open?id=0B4aqla6AxjwnaGhJVkhvSUJXbG8)
- [Složka vytvořená kdysi pro SS5 (obsahuje i info o Pohodě) od Mira Stopky](https://drive.google.com/drive/folders/0B4aqla6AxjwnQVo2MUpVblFkTTg?usp=sharing)

## Přístupy

### Vzdálená plocha
- **Server:** `tropic.chcisystem.cz`
- **Jméno:** `chcisystem\chmandokt`
- **Heslo:** `tK.7496321`
- Připojit se je možné pomocí vzdálené plochy na Shopsys síti nebo VPN

### Pohoda
- Pohoda se spustí po připojení na vzdálenou plochu automaticky, případně otevřít z plochy Ekonomický software Pohoda (modrá ikona)
- **Jméno:** `chmandokt`
- **Heslo:** `tK.7496321`

### Databáze Pohody
- Dá se připojit pomocí jakéhokoli MSSQL klienta - já používám HeidiSQL nebo PhpStorm
- **IP:** `46.234.110.124`
- **Port:** `1614`
- **User:** `chmandokt`
- **Heslo:** `tK.7496321`
- **Databáze (testovací):** `StwPhTRP_TESTSHOPSYS`

### mServer
- **IP:** `46.234.124.195`
- **Port:** `4444`
- **Kam posílat request:** `http://46.234.124.195:4444/xml`

## IS Pohoda → E-shop
- Funguje pomocí MSSQL dotazů do databáze Pohody ze strany e-shopu
- [Schéma databáze](https://drive.google.com/file/d/0B4aqla6AxjwnaGhJVkhvSUJXbG8/view) - nemusí být úplně aktuální a přesné

### Architektura
- Existuje komponenta v `src\Component\Transfer`, která zajišťuje komunikaci s IS Pohoda
    - **Veškerá pojmenování v komponentě jsou z pohledu Pohody** - tedy pro IS → E-shop jde o export (např. `PohodaProductExportFacade` slouží pro export produktů z Pohody).
    - Zde jsou SQL dotazy na databázi pohody
    - Pro SQL dotazy je používán `PohodaEntityManager`
- Samotný import (pojmenováno již z pohledu e-shopu) volá `PohodaProductExportFacade`, která získává data z Pohody
- Veškeré importy jsou poté již u daného modelu. Pro produkty například v: `App\Model\Product\Transfer`
- Přenosy se spouští pomocí cronů

### Jak si přidat databázi do PhpStormu
- Blok `Database` + vybrat `MSSQL`
- Vložit přihlašovací údaje. To stejné i pro ostatní klienty… Například pro HeidiSQL
- **Abyste viděli tabulky je nutné přidat si uživatele dbo (viz foto níže)**

![PhpStorm Data Source Dialog](../img/phpstorm-data-source-dialog.jpg 'PhpStorm Data Source Dialog')
![PhpStorm DBO user select](../img/phpstorm-database-select-dbo-user.jpg 'PhpStorm DBO user select')

## E-shop → IS Pohoda
- Funguje pomocí mServeru pomocí XML - podobně jako jShopsys
- Na mServer se pošle XML request a mServer vrátí response
- [Příklady XML požadavků](https://www.stormware.cz/pohoda/xml/dokladyimport/)
- [XML Schemata](https://www.stormware.cz/pohoda/xml/seznamschemat/)
- Oproti jShopsysu mServer nespouští sám přenosy ale pouze naslouchá a vykonává požadavky

### Architektura
- bude dopsáno jakmile bude implementován přenos

### Jak zapnout mServer
- **mServer vypínejte, když s ním nepracujete!**


- Na vzdálené ploše otevřeme na agendě Účetní jednotky (`Soubor` → `Účetní jednotky`) menu `Databáze` → `POHODA mServer`
- Vybereme ze seznamu `TestShopsys` s portem `4444` a klikneme na `Spustit`
- Spustí se okno (mServer), kde se budou zobrazovat požadavky, které na mServer pošlete
- Kliknutím pravým na daný záznam si můžete stáhnout XML s requestem nebo responsem
- **Pokud chcete přejít do Pohody je nutno otevřít Pohodu znova z plochy a přejít do účetnictví.**
- **Okno + Pohoda s mServerem musí být celou dobu zapnuté**

![mServer Navigation](../img/mserver-menu.jpg 'mServer Navigation')
![mServer Dialog](../img/mserver-dialog.jpg 'mServer Dialog')

### Jak poslat testovací request na mServer
- [Dokumentace mServeru](https://www.stormware.cz/pohoda/xml/mserver/provyvojare/)
- Testoval jsem pomoci aplikace Postman
- V headeru se posílá:
```
Content-Type: text/xml
STW-Authorization: Basic auth zakódovaný s jménem a heslem do Pohody  (já mám v Postmanovi naprudko výsledek: Basic Y2htYW5kb2t0OnRLLjc0OTYzMjE=)
STW-Application: slouží pro identifikaci aplikace - zde je dobré uvést např. E-shop nebo Shopsys pro logy
STW-Instance: Instance přenosu - například Objednávky - nepovinné, lepší pro logování
```
- V HTTP Body je samotné XML s requestem
- [Mé testovací požadavky v Postmanovi](https://drive.google.com/open?id=1XUdu8o8xR5gGIeAW6BTRHyPhvNoJfcFP)

