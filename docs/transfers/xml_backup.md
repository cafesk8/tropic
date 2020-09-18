# Záloha XML z přenosů pomocí mServeru
- Soubory se zálohují do složky nastavené pomoc `transfer_xml_backup_path` v `parameters.yaml`
- Soubory se nikde nezobrazují, jsou dostupné na S3
- **Soubor a objednávka/uživatel není prolinkované! Je nutno si složku stáhnout a vyhledávat v souborech text s tou danou objednávkou.**

## Stažení souboru
- **Soubory nejde vidět čistě po přístupu do složky s projektem!** Je nutné si je vytáhnout z S3
- Je potřeba se nejprve připojit na CI server a poté: 

- **Vypsání adresářů:** `s3cmd ls s3://tropic-devel/var/transfer-xml/`
- **Stažení konkrétního souboru na CI server:** `s3cmd get s3://tropic-devel/var/transfer-xml/request/exportOrders/exportOrders_1600353385.xml`
- **Stažení celé složky na CI server:** `s3cmd get s3://tropic-devel/var/transfer-xml/ [cílová adresa] --recursive`

- **Stažení z CI na počítač (Windows):** `pscp -r -i [cesta k SSH klíči] [user]@ready-made.ci.shopsys.cloud:[adrea se složkou]/* [cílová adresa]`,
**konkrétně:** `pscp -r -i C:\private.ppk stankovic@ready-made.ci.shopsys.cloud:xml-backup/* C:\Users\shopsys\Documents\tropic\xml-backup`
