# Translation tools for OpenCity CMS

## Come funziona questo tool?
Si tratta di un'estensione per la distribuzione OpencityItalia CMS del cms eZPublish Legacy 
che si occupa di integrare un sistema di traduzione automatica nel meccanismo del content model del cms.

Per installare l'estensione:
 - clonare il repository nella cartella `extension` della propria installazione del cms
 - rigenerare gli autoload con il comando `php bin/php/ezpgenerateautoloads.php -e`
 - attivare l'estensione modificando il file `settings/override/site.ini.append.php` aggiungendo:
```
[ExtensionSettings]
ActiveExtensions[]=octranslate
```

Attraverso il modulo custom `translate/content`, il redattore potrà scegliere se pubblicare automaticamente 
il contenuto nella lingua selezionata o se modificare la traduzione automatica per revisionarla.

Nel codice di questo repository non è presente alcuna libreria che esegua effettivamente la traduzione:
è infatti necessario installare un'estensione dedicata (ad esempio [octranslate_deepl](https://github.com/OpencontentCoop/octranslate_deepl)) 
o produrre un handler di traduzione personalizzato 

Per attivare l'handler di traduzione occorre creare o modificare il file `settings/override/octranslate.ini.append.php`:
```
[Settings]
HandlerClassName=MyAwesomeTranslatorHandler
```

## Come creare un handler di traduzione?
Per creare un handler di traduzione personalizzato occorre creare una classe php 
che implementi l'interfaccia `TranslatorHandlerInterface` opportunamente documentata nel codice


## Copyright (C)
Il titolare del software è la Provincia Autonoma di Trento
Il software è rilasciato con licenza aperta ai sensi dell'art. 69 comma 1
del Codice dell’Amministrazione Digitale

## Maintainer
OpenCity Labs srl è responsabile della progettazione, realizzazione e manutenzione tecnica di OpenCity

## Licenza e modalità d'uso
Il software è rilasciato con GNU General Public License v2.0 che puoi leggere integralmente qui: http://www.gnu.org/licenses/gpl-2.0.txt

Grazie a questa licenza, qualsiasi utente gode delle quattro libertà fondamentali:
* Libertà di eseguire il programma come si desidera, per qualsiasi scopo (libertà 0).
* Libertà di studiare come funziona il programma e di modificarlo in modo da adattarlo alle proprie necessità (libertà 1). L'accesso al codice sorgente ne è un prerequisito.
* Libertà di ridistribuire copie in modo da aiutare gli altri (libertà 2).
* Libertà di migliorare il programma e distribuirne pubblicamente i miglioramenti da voi apportati (e le vostre versioni modificate in genere), in modo tale che tutta la comunità ne tragga beneficio (libertà 3). L'accesso al codice sorgente ne è un prerequisito.
