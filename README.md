# Verzetskranten Tweede Wereldoorlog - Koninklijke Bibliotheek

Deze tool dient ervoor om informatie over Nederlandse verzetskranten uit de Tweede Wereldoorlog
snel en consistent om te zetten naar beginnetjes van Wikipedia artikelen, opdat deze door de Wikipedia-gemeenschap aangevuld en verrijkt kunnen worden tot volwaardige artikelen.
Zie https://nl.wikipedia.org/wiki/Wikipedia:Wikiproject/Verzetskranten voor meer informatie over het project waarbinnen deze tool ontwikkeld is

## Requirements
De tool is afhankelijk van de onderstaande software om te kunnen functioneren:
- [PHP](http://php.net)
- [composer](https://getcomposer.org)
- [Git](http://git-scm.com)

### PHP
Tijdens ontwikkeling is versie 5.5.9 gebruikt. Het wordt aangeraden om een versie uit de 5.5 serie te gebruiken om
compatibiliteits problemen te vermijden.

### Composer
Composer dient geinstalleerd te zijn. De meest recente versie kan hiervoor gebruikt worden.

### Git
Git dient geinstalleerd te zijn. De meest recente versie voor uw besturingssysteem kan hiervoor gebruikt worden.

## Installatie
Voer onderstaande commandos uit via de opdrachtregel
    git clone git@github.com:ookgezellig/verzetskranten.git
    cd verzetskranten
    composer install
    php bin/console server:run

De tool kan nu in een browser geopend worden op het volgende adres:
[http://127.0.0.1:8000](http://127.0.0.1:8000)

De installatie kan gecontroleerd worden door in de browser te navigeren naar: 
[http://127.0.0.1:8000/config.php](http://127.0.0.1:8000/config.php)
of op de opdrachtregel uite te voeren:

    php bin/symfony_requirements
