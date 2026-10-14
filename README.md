# WPU Dela

Gratis plugin från [wordpressutveckling.se](https://wordpressutveckling.se/). Koden förklaras rad för rad i guiden: **[https://wordpressutveckling.se/delningsknappar-i-wordpress-utan-sparning/](https://wordpressutveckling.se/delningsknappar-i-wordpress-utan-sparning/)**

Byggt av [Kristoffer Pihlfelt](https://pihlfelt.se) på [Pihlfelt & Vänner](https://pihlfelt.se). Licens: GPL-2.0-or-later.

- **Bidragsgivare:** Pihlfelt & Vänner
- **Kräver WordPress:** 6.7
- **Kräver PHP:** 8.2
- **Version:** 1.0.0
- **Licens:** GPL-2.0-or-later

## Vad det gör

Ett litet delningsplugin, samma som körs på wordpressutveckling.se. Under inläggen visas en lugn rad med små länkar:

* LinkedIn
* Facebook
* X
* E-post
* Kopiera länk (med beskedet "Länken är kopierad")

Det är vanliga länkar till respektive tjänsts delningssida. Inga externa skript, ingen spårning, inga räknare och inga kakor. Länken är inläggets permalänk utan UTM-parametrar och texten är rubriken.

På mobiler med inbyggd delning ersätts tjänsterna av en knapp "Dela inlägget" som öppnar telefonens delningsmeny. Skriptet är runt 1 KB, laddas med defer och bara på sidor där raden finns.

Under Inställningar > Dela väljer du tjänster, automatisk placering (efter innehållet, före, båda eller av) och vilka innehållstyper som får raden. Standard: alla tjänster, efter innehållet, bara inlägg.

## Manuell placering

* Kortkoden [wpu_dela]
* Blocket "Dela"
* I temat: <?php wpu_dela(); ?> eller do_action( 'wpu_dela' )

Står kortkoden eller blocket redan i inlägget läggs ingen automatisk rad till.

## För utvecklare

* wpu_dela_auto – returnera false för att stoppa den automatiska raden för ett visst inlägg.
* Stilen har handtaget wpu-dela och är några få regler som ärver typsnitt och färg. Skriv över i temat eller avregistrera med wp_dequeue_style( 'wpu-dela' ).

## Installation

Tillägg > Lägg till nytt > Ladda upp tillägg, välj zip-filen och aktivera. Raden visas direkt under inläggen.

## Avinstallation

När pluginet raderas tas inställningen wpu_dela_settings bort.

## Ändringslogg

### 1.0.0
* Första versionen.
