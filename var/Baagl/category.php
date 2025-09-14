<?php

function getCategoryId(string $company, string $extId, string $name): ?array {

    $url = __DIR__ .  '/category.xml';
    $xml = simplexml_load_file($url);
    if (!$xml) {
        throw new Exception("Nelze načíst XML.");
    };

$sortId = 0;

switch ($extId) {

    case 101: //Notesy, kalendáře, diáře &gt; Kalendáře
    switch (true) {
        case mb_stripos($name, 'rodinný') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'poznámkový') !== false:
            $sortId = 2;
            break;
        case mb_stripos($name, 'kolíček s magnetem') !== false:
            $sortId = 3;
            break;
    }
    break;

    case 103:  //Notesy, kalendáře, diáře &gt; Kalendáře &gt; Nástěnný kalendáře (1x rodinný)
    switch (true) {
        case mb_stripos($name, 'nástěnný') !== false && mb_stripos($name, 'kalendář') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'rodinný') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 105:  //Notesy, kalendáře, diáře &gt; Kalendáře &gt; Rodinné kalendáře
    switch (true) {
        case mb_stripos($name, 'kalendář') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'nedatovaný') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 107:  //Notesy, kalendáře, diáře &gt; Kalendáře &gt; Stolní kalendáře
    switch (true) {
        case mb_stripos($name, 'kalendář') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 108:  //Notesy, kalendáře, diáře &gt; Kalendáře &gt; Nástěnný kalendáře
    switch (true) {
        case mb_stripos($name, 'kalendář') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 112: //Notesy, kalendáře, diáře &gt; Ostatní
    switch (true) {
        case mb_stripos($name, 'diář') !== false:
        case mb_stripos($name, 'kalendář') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 120:  //Notesy, kalendáře, diáře &gt; Diáře &gt; Denní diáře
    switch (true) {
        case mb_stripos($name, 'denní diář') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 121:  //Notesy, kalendáře, diáře &gt; Diáře &gt; 18měsíční diáře
    switch (true) {
        case mb_stripos($name, '18měsíční') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 122:  //Notesy, kalendáře, diáře &gt; Diáře &gt; Týdenní diáře
    switch (true) {
        case mb_stripos($name, 'týdenní') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 124:  //Notesy, kalendáře, diáře &gt; Diáře &gt; Kapesní diáře
    switch (true) {
        case mb_stripos($name, 'kapesní') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 125:  //Notesy, kalendáře, diáře &gt; Diáře &gt; Kapesní diáře
    switch (true) {
        case mb_stripos($name, 'kapesní') !== false:
            $sortId = 1;
            break;
    }
    break;
    
    case 126: //Notesy, kalendáře, diáře &gt; Diáře
    switch (true) {
        case mb_stripos($name, '18měsíční') !== false:
            $sortId = 1;
            break;

        case mb_stripos($name, 'týdenní') !== false:
        case mb_stripos($name, 'diář/notes') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 127:  //Notesy, kalendáře, diáře &gt; Diáře &gt; Týdenní diáře (1x Notesy, kalendáře, diáře &gt; Ostatní)
    switch (true) {
        case mb_stripos($name, 'týdenní') !== false:
        case mb_stripos($name, 'Zábavný kalendář Kateřiny Winterové') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'návlek') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 128:  //Notesy, kalendáře, diáře &gt; Diáře &gt; Školní diáře
    switch (true) {
        case mb_stripos($name, 'školní') !== false:
        case mb_stripos($name, 'studentský') !== false:
            $sortId = 1;
            break;
    }
    break;

    case 134:  //Notesy, kalendáře, diáře &gt; Notesy
    switch (true) {
        case mb_stripos($name, 'notes') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'poutko') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 135:  //Notesy, kalendáře, diáře &gt; Notesy
    switch (true) {
        case mb_stripos($name, 'notes') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'památník') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 136:  //Notesy, kalendáře, diáře &gt; Bloky
    switch (true) {
        case mb_stripos($name, 'blok') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'notes') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 144: //Knihy
    switch (true) {
        case mb_stripos($name, 'omalovánky') !== false:
        case mb_stripos($name, 'kawaii manga') !== false:
        case mb_stripos($name, 'oblékáme') !== false && mb_stripos($name, 'panenky') !== false:
            $sortId = 2;
            break;
        case mb_stripos($name, 'do kočárku') !== false:
        case mb_stripos($name, 's pohyblivými prvky') !== false:
        case mb_stripos($name, 'statečná autíčka') !== false:
        case mb_stripos($name, 'klap klap obrázky') !== false:
        case mb_stripos($name, 'kde je králíček max') !== false:
        case mb_stripos($name, 'výlet do divočiny') !== false:
        case mb_stripos($name, 'hurá na řeku') !== false:
            $sortId = 3;
            break;
        case mb_strpos($name, 'Samolepková knížka') !== false:
        case mb_strpos($name, 'Knížka s plakátem a samolepkami') !== false:
        case mb_stripos($name, 'samolepek') !== false:
        case mb_stripos($name, 'se samolepkami') !== false:
        case mb_stripos($name, 'samolepková') !== false:
        case mb_stripos($name, 'samolepkový') !== false:
            $sortId = 4;
            break;
        case mb_stripos($name, 'odklápěcími') !== false:
            $sortId = 5;
            break;
        case mb_stripos($name, 'na cesty') !== false:
            $sortId = 6;
            break;
        case mb_stripos($name, 'pro malé vypravěče') !== false:
        case mb_stripos($name, 'pátrej & vyprávěj') !== false:
        case mb_stripos($name, 'rozkládací kniha') !== false:            
            $sortId = 7;
            break;
        case mb_stripos($name, 'skicák') !== false:           
            $sortId = 8;
            break;
        case mb_stripos($name, 'bezlepkov') !== false:
        case mb_stripos($name, 'knedlíková revoluce') !== false:        
            $sortId = 9;
            break;
        case mb_stripos($name, 'únikovka') !== false:
        case mb_stripos($name, 'velký obrazový průvodce') !== false:
        case mb_stripos($name, 'pro fanoušky') !== false:
        case mb_stripos($name, 'dektektivky s hádankou') !== false:
        case mb_stripos($name, 'nebojí být výjimečn') !== false:
        case mb_stripos($name, 'Motivační zápisník pro kluky a holky') !== false:                    
            $sortId = 10;
            break;

        default:
            $sortId = 1;
    };
    break;

    case 160:  //Školní potřeby &gt; Aktovky / Školní batoh
    switch (true) {
        case mb_stripos($name, 'školní aktovka') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'školní batoh') !== false:
            $sortId = 2;
            break; 
    }
    break;


    case 161: //Školní potřeby &gt; Aktovky / Školní batoh / Předškolní batoh
    switch (true) {
        case mb_strpos($name, 'Školní batoh') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'předškolní') !== false:
        case mb_stripos($name, 'batoh buddy') !== false:
            $sortId = 2;
            break;
        case mb_stripos($name, 'zavinovací batoh') !== false:
            $sortId = 3;
            break;
        case mb_stripos($name, 'city batoh rpet') !== false:
        case mb_stripos($name, 'batoh earth') !== false:
        case mb_stripos($name, 'batoh coolmate') !== false:
            $sortId = 4;
            break;
        case mb_stripos($name, 'batoh tracker') !== false:
            $sortId = 5;
            break;

    }
    break;

    case 162:  //Školní potřeby &gt; Školní sety
    switch (true) {
        case mb_stripos($name, 'SET') !== false && mb_stripos($name, '3') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'SET') !== false && mb_stripos($name, '5') !== false:
            $sortId = 2;
            break;
    }
    break;

    case 163:  //Školní potřeby &gt; Penály
    switch (true) {
        case mb_stripos($name, 'školní penál') !== false && mb_stripos($name, 'jednopatrový') !== false:
        case mb_stripos($name, 'školní penál') !== false && mb_stripos($name, 'klasik') !== false:
        case mb_stripos($name, 'školní penál s náplní Příšerky Girls') !== false:
        case mb_stripos($name, 'školní penál supergirl – stay calm') !== false:
        case mb_stripos($name, 'školní penál soy luna') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'školní penál') !== false && mb_stripos($name, 'dvoupatrový') !== false:
            $sortId = 2;
            break;
        case mb_stripos($name, 'školní penál') !== false && mb_stripos($name, 'třípatrový') !== false:
            $sortId = 3;
            break;
        case mb_stripos($name, 'školní') !== false && mb_stripos($name, 'etue') !== false:
        case mb_stripos($name, 'penál etue mickey') !== false:
        case mb_stripos($name, 'penál etue skate teribear') !== false:
            $sortId = 4;
            break;
        case mb_stripos($name, 'studentský') !== false && mb_stripos($name, 'etue') !== false:
        case mb_stripos($name, 'etue recykl') !== false:
            $sortId = 5;
            break;
        case mb_stripos($name, 'studentské') !== false && mb_stripos($name, 'pouzdro') !== false:
            $sortId = 6;
            break;
    }
    break;

    case 164:
    switch (true) {
        case mb_stripos($name, 'předškolní sáček') !== false: 
            $sortId = 1;
            break;
        case mb_stripos($name, 'sáček') !== false && mb_stripos($name, 'předškolní') == false && mb_stripos($name, 's kapsou') == false:
        case mb_stripos($name, 'džínový vak na záda mickey') !== false:            
            $sortId = 2;
            break;
        case mb_stripos($name, 'sáček s kapsou') !== false:
        case mb_strpos($name, 'Sáček Dinosauři') !== false:
            $sortId = 3;
            break;

    }
    break;

    case 165:  //Školní potřeby &gt; Peněženky
    switch (true) {
        case mb_stripos($name, 'peněženka') !== false && mb_stripos($name, 'na krk') !== false:        
            $sortId = 1;
            break;
        case mb_stripos($name, 'peněženka') !== false && mb_stripos($name, 'na krk') == false && mb_stripos($name, 'studentská') == false:            
            $sortId = 2;
            break;
        case mb_stripos($name, 'peněženka') !== false && mb_stripos($name, 'studentská') !== false: 
            $sortId = 3;
            break;
    }
    break;

    case 166:  //Školní potřeby &gt; Školní desky
    switch (true) {
        case mb_stripos($name, 'školní sešity A5') !== false:        
            $sortId = 1;
            break;
        case mb_stripos($name, 'školní sešity A4') !== false && mb_stripos($name, 'jumbo') == false:            
            $sortId = 2;
            break;
        case mb_stripos($name, 'školní sešity A4') !== false && mb_stripos($name, 'jumbo') !== false: 
            $sortId = 3;
            break;
    }
    break;

    case 167: //Školní potřeby &gt; Boxy na svačinu
    switch (true) {
        case mb_stripos($name, 'box na svačinu') !== false && mb_stripos($name, '4 v 1') == false && mb_stripos($name, 's přihrádkami') == false:       
            $sortId = 1;
            break;
        case mb_stripos($name, 'box na svačinu') !== false && mb_stripos($name, 's přihrádkami') !== false:            
            $sortId = 2;
            break;
        case mb_stripos($name, 'box na svačinu') !== false && mb_stripos($name, '4 v 1') !== false: 
            $sortId = 3;
            break;
    }
    break;

    case 168:
    switch (true) {
        case mb_stripos($name, 'rouška') !== false:
        case mb_stripos($name, 'samolepka') !== false:
        case mb_stripos($name, 'samolepky') !== false:
        case mb_stripos($name, 'bandana') !== false:
        case mb_stripos($name, 'Dětské nádobí Kouzelná školka') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'kufřík') !== false:
            $sortId = 2;
            break;

        // Láhve na pití, termosky
        case mb_stripos($name, 'tritanová') !== false && mb_stripos($name, '350 ml') !== false && mb_stripos($name, 's brčkem') == false:
            $sortId = 31;
            break;
        case mb_stripos($name, 'tritanová') !== false && mb_stripos($name, '500 ml') !== false && mb_stripos($name, 's brčkem') == false:
        case mb_stripos($name, 'Láhev na pití Srdce') !== false:
        case mb_stripos($name, 'Láhev na pití NASA') !== false:            
            $sortId = 32;
            break;
        case mb_stripos($name, 'tritanová') !== false && mb_stripos($name, '700 ml') !== false && mb_stripos($name, 's brčkem') == false:
            $sortId = 33;
            break;
        case mb_stripos($name, 'tritanová') !== false && mb_stripos($name, '800 ml') !== false && mb_stripos($name, 's brčkem') == false:
            $sortId = 34;
            break;
        case mb_stripos($name, 'tritanová') !== false && mb_stripos($name, '500 ml') !== false && mb_stripos($name, 's brčkem') !== false:
            $sortId = 35;
            break;
        case mb_stripos($name, 'termoláhev') !== false && mb_stripos($name, '450 ml') !== false && mb_stripos($name, 'víčkem') == false:
            $sortId = 36;
            break;
        case mb_stripos($name, 'termoláhev') !== false && mb_stripos($name, '550 ml') !== false && mb_stripos($name, 'víčkem') == false:
            $sortId = 37;
            break;
        case mb_stripos($name, 'termoláhev') !== false && mb_stripos($name, '500 ml') !== false && mb_stripos($name, 'bambusovým víčkem') !== false:
            $sortId = 38;
            break;
        case mb_stripos($name, 'termoláhev') !== false && mb_stripos($name, '500 ml') !== false && mb_stripos($name, 'kovovým víčkem') !== false:
            $sortId = 39;
            break;
        case mb_stripos($name, 'sportovní taška') !== false:
            $sortId = 4;
            break;
        case mb_stripos($name, 'klíčenka') !== false:
        case mb_stripos($name, 'přívěsek na klíče') !== false:
            $sortId = 5;
            break;

        // Pláštěnky          
        case mb_stripos($name, 'pláštěnka') !== false && mb_stripos($name, 'vel. S') !== false:
            $sortId = 60;
            break;
        case mb_stripos($name, 'pláštěnka') !== false && mb_stripos($name, 'vel. XS') !== false:
            $sortId = 61;
            break;
        case mb_stripos($name, 'pláštěnka') !== false && mb_stripos($name, 'vel. M') !== false:
            $sortId = 62;
            break;
        case mb_stripos($name, 'pláštěnka') !== false && mb_stripos($name, 'vel. L') !== false:
            $sortId = 63;
            break;
        case mb_stripos($name, 'pláštěnka') !== false && mb_stripos($name, 'vel. XL') !== false:
            $sortId = 64;
            break;
        case mb_stripos($name, 'Pláštěnka na školní aktovku') !== false:
            $sortId = 65;
            break;
        case mb_stripos($name, 'ledvinka') !== false:
            $sortId = 7;
            break;
        case mb_stripos($name, 'zástěra') !== false:
            $sortId = 8;
            break;

    }
    break;

    case 171:
    switch (true) {
        case mb_stripos($name, 'ledvinka') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'batoh Dash') !== false:
            $sortId = 2;
            break;
        case mb_stripos($name, 'batoh Roll') !== false:
            $sortId = 3;
            break;
    }
    break;

    case 173:  //Školní potřeby &gt; Školní sety
    switch (true) {
        case mb_stripos($name, 'SET') !== false && mb_stripos($name, '2') !== false:
            $sortId = 1;
            break;
        case mb_stripos($name, 'SET') !== false && mb_stripos($name, '3') !== false:
            $sortId = 2;
            break;
        case mb_stripos($name, 'sáček') !== false:
            $sortId = 3;
            break;
        case mb_stripos($name, 'Školní aktovka Ergo Minecraft Blue') !== false:
            $sortId = 4;
            break;
        case mb_stripos($name, 'Školní penál jednopatrový Minecraft Blue') !== false:
            $sortId = 5;
            break;
    }
    break;

}

    foreach ($xml->CATEGORY as $category) {
        if ((string)$category->COMPANY == $company && (string)$category->EXT_ID == $extId && (string)$category->SORT_ID == $sortId ) {
        return [
            'shoptet_id' => (string)$category->SHOPTET_ID,
            'cat_name' => (string)$category->NAME
        ];
        }
    }

    // Nenalezeno
    return null;
}
?>