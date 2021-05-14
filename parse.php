<?php
/** ******************** HLAVICKA SKRIPTU ******************** **/
/** PROJEKT 1:      Analayzator kodu IPPcode20                 **/
/** VERZIA:         1.0                                        **/
/** AUTOR:          Lubos Bever                                **/
/** DATUM:          04.03.2020                                 **/
/** PREDMET:        Principy programovacich jazykov a OOP      **/
/** ********************************************************** **/

/** ------------------- DEKLARACIA KONSTANT ------------------ **/
const ERR_HELP = 10;
const ERR_STATS = 100;
const ERR_OUTPUT_FOPEN = 12;
const ERR_IPPCODE20 = 21;
const ERR_OPCODE = 22;
const ERR_LEXICAL = 23;
const ERR_SYNTACTIC = 230;
const ERR_INTERNAL = 99;

/** -------------------- DEFINICIE FUNKCII ------------------- **/
/**
 * Vypis napovedy help a korektne ukoncenie behu programu.
 */
function write_help()
{
    fwrite(STDOUT, "POUZITIE: php7.4 parse.php [--help]\n");
    fwrite(STDOUT, "POUZITIE: php7.4 parse.php [--stats=file [--loc] ");
    fwrite(STDOUT, "[--comments] [--labels] [--jumps]]\n");
    fwrite(STDOUT, "POUZITIE: php7.4 parse.php [-h]\n");
    fwrite(STDOUT, "POUZITIE: php7.4 parse.php [-s file [-l|-c|-b|-j]]\n\n");

    fwrite(STDOUT, "MOZNE PREPINACE:\n");
    fwrite(STDOUT, "\t-h,      --help\t\tVypis napovedy HELP.\n");
    fwrite(STDOUT, "\t-s file, --stats=file\tUmiestnenie statistik do suboru 'file'.\n");
    fwrite(STDOUT, "\t-l,      --loc\t\tVypis poctu instrukcii na vstupe do 'file'.\n");
    fwrite(STDOUT, "\t-c,      --comments\tVypis poctu komentarov do 'file'.\n");
    fwrite(STDOUT, "\t-b,      --labels\tVypis poctu navesti do 'file'.\n");
    fwrite(STDOUT, "\t-j,      --jumps\tVypis poctu (ne)podmienenych skokov a navratov z funkcii do 'file'.\n\n");

    fwrite(STDOUT, "parse.php je skript typu filter, ktory nacita zo\n");
    fwrite(STDOUT, "standardneho vstupu zdrojovy kod v jazyku IPPcode20,\n");
    fwrite(STDOUT, "kontroluje lexikalnu a syntakticku spravnost kodu a\n");
    fwrite(STDOUT, "vypise na standardny vystup XML reprezentaciu programu.\n");
    fwrite(STDOUT, "Skript je odporucane spustat s verziou PHP 7.0+\n\n");

    fwrite(STDOUT, "PRIKLADY:\n");
    fwrite(STDOUT, "\tphp7.4 parse.php -h\n");
    fwrite(STDOUT, "\tphp7.4 parse.php --stats=stats.txt --jumps\n");
    fwrite(STDOUT, "\tphp7.4 parse.php -s stats.txt -lcbj\n\n");

    fwrite(STDOUT, "CHYBOVE NAVRATOVE KODY:\n");
    fwrite(STDOUT, "10 - Chybajuci parameter skriptu alebo pouzitie zakazanej\n");
    fwrite(STDOUT, "     kombinacie parametrov.\n");
    fwrite(STDOUT, "12 - Chyba pri otvoreni vystupneho suboru pre zapis.\n");
    fwrite(STDOUT, "21 - Chybna alebo chybajuca hlavicka v zdrojovom kode\n");
    fwrite(STDOUT, "     zapisanom v jazyku IPPcode20.\n");
    fwrite(STDOUT, "22 - Neznamy alebo chybny operacny kod v zdrojovom kode\n");
    fwrite(STDOUT, "     zapisanom v jazyku IPPcode20.\n");
    fwrite(STDOUT, "23 - Ina lexikalna alebo syntakticka chyba zdrojoveho\n");
    fwrite(STDOUT, "     kodu zapisaneho v jazyku IPPcode20.\n");
    fwrite(STDOUT, "99 - Interna chyba neovplyvnena parametrami prikazoveho\n");
    fwrite(STDOUT, "     riadku ani vstupnymi subormi (napr. chybna alokacia pamati).\n");
    exit(0);
}

/**
 * Vypis chyboveho hlasenia a ukoncenie programu so specifickym navratovym kodom.
 * @param int $errno    Kod konkretnej chyby.
 */
function write_err(int $errno)
{
    switch ($errno) {
        case 10:
            fwrite(STDERR, "CHYBA! Nespravne pouzitie prepinacu --help.\n");
            exit(10);
        case 12:
            fwrite(STDERR, "CHYBA! Nepodarilo sa vytvorit/otvorit subor pre zapis.\n");
            exit(12);
        case 100:
            fwrite(STDERR, "CHYBA! Vynechany/duplikovany prepinac --stats.\n");
            exit(10);
        case 21:
            fwrite(STDERR, "CHYBA! Chybna alebo chybajuca uvodna hlavicka .IPPcode20.\n");
            exit(21);
        case 22:
            fwrite(STDERR, "CHYBA! Nespravny operacny kod instrukcie.\n");
            exit(22);
        case 23:
            fwrite(STDERR, "CHYBA! Lexikalna chyba v IPPcode20.\n");
            exit(23);
        case 230:
            fwrite(STDERR, "CHYBA! Syntakticka chyba v IPPcode20.\n");
            exit(23);
        case 99:
            fwrite(STDERR, "CHYBA! Nastala interna chyba pocas behu skriptu.\n");
            exit(99);
        default:
            break;
    }
}

/**
 * Priprava prostriedkov pre tvorbu XML, vygenerovanie uvodnej hlavicky a korenoveho elementu kodu XML.
 */
function start_xml()
{
    if (($GLOBALS['xml'] = xmlwriter_open_memory()) === false) {
        write_err(ERR_INTERNAL);
    }
    if (mb_regex_encoding('UTF-8') === false) {                        /// Nastavenie kodovania pre Regexy.
        write_err(ERR_INTERNAL);
    }
    xmlwriter_set_indent($GLOBALS['xml'], true);                       /// Zapnutie odsadenia pri zanoreni.
    xmlwriter_set_indent_string($GLOBALS['xml'], '   ');               /// Zanorenie = 3 medzery.
    xmlwriter_start_document($GLOBALS['xml'], '1.0', 'UTF-8');         /// Vytvorenie XML hlavicky.

    xmlwriter_start_element($GLOBALS['xml'], 'program');
    xmlwriter_start_attribute($GLOBALS['xml'], 'language');
    xmlwriter_text($GLOBALS['xml'], 'IPPcode20');
    xmlwriter_end_attribute($GLOBALS['xml']);
}

/**
 * Orezanie komentarov a krajnych bielych znakov z riadku.
 * @param string $line  Nacitany riadok.
 * @return false|string Orezany riadok.
 */
function trim_line_and_comment(string $line)
{
    if (($pos = mb_strpos($line, '#')) !== false) {            /// Odrezanie komentaru, ak sa v nom vyskytuje.
        $line = mb_substr($line, 0, $pos);
        $GLOBALS['commentsCntr'] += 1;
    }
    $line = trim($line);                                    /// Odstranenie krajnych bielych znakov
    return $line;
}

/**
 * Samostatne orezanie slov v riadku.
 * @param array $arr    Pole slov z riadku.
 * @return array        Pole orezanych slov.
 */
function trim_words(array $arr)
{
    foreach ($arr as $wordPos => $word) {
        if (mb_strlen($word) == 0) {                        /// Detekcia prazdneho stringu ako samostatneho elementu pola.
            unset($arr["$wordPos"]);                        /// Odstranenie prazdneho elementu z pola (prazdneho slova).
        }
        else {
            $arr["$wordPos"] = trim($word);                 /// Orezanie slova od krajnych bielych znakov.
        }
    }
    $arr = array_values($arr);                              /// Nastavenie klucov v poli od nuly.
    return $arr;
}

/**
 * Generovanie zaciatku XML kodu pre konkretnu instrukciu.
 * @param int $order        Poradie instrukcie.
 * @param string $opcode    Operacny kod instrukcie.
 */
function start_instr_elem(int $order, string $opcode) {
    xmlwriter_start_element($GLOBALS['xml'], 'instruction');
    xmlwriter_start_attribute($GLOBALS['xml'], 'order');
    xmlwriter_text($GLOBALS['xml'], "$order");
    xmlwriter_end_attribute($GLOBALS['xml']);
    xmlwriter_start_attribute($GLOBALS['xml'], 'opcode');
    xmlwriter_text($GLOBALS['xml'], "$opcode");
    xmlwriter_end_attribute($GLOBALS['xml']);
}

/**
 * Generovanie XML kodu pre 1., 2. alebo 3. argument instrukcie.
 * @param string $typeVal   Typ argumentu danej instrukcie.
 * @param string $text      Hodnota argumentu danej instrukcie.
 * @param int $i            Poradie instrukcie.
 */
function write_arg_elem(string $typeVal, string $text, int $i = 1) {
        xmlwriter_start_element($GLOBALS['xml'], 'arg'."$i");
        xmlwriter_start_attribute($GLOBALS['xml'], 'type');
        xmlwriter_text($GLOBALS['xml'], "$typeVal");
        xmlwriter_end_attribute($GLOBALS['xml']);
        xmlwriter_text($GLOBALS['xml'], "$text");
        xmlwriter_end_element($GLOBALS['xml']);
}

/**
 * Rozlisenie argumentu typu <symb>, ci ide o premennu alebo o konstantu, a nasledne generovanie prislusneho XML kodu.
 * @param string $arg   Instrukcia v poli slov.
 * @param int $i        Index argumentu v instrukcii.
 */
function write_arg_elem_symb(string $arg, int $i = 1) {
    if (mb_ereg('^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$', $arg) !== false) {   /// <symb> je premenna.
        write_arg_elem('var', $arg, $i);
    }
    else {              /// <symb> je konstanta.
        $arrTmp = explode('@', $arg);          /// Rozdeli vyraz na dve casti v mieste vyskytu '@', pricom tento znak zanikne.
        write_arg_elem($arrTmp['0'], $arrTmp['1'], $i);       /// Zmena na XML entitu v pripade stringu.
    }
}

/**
 * Ukoncenie instrukcie v XML kode.
 */
function end_instr_elem() {
    xmlwriter_end_element($GLOBALS['xml']);
}

/**
 * Ukoncenie hlavneho elementu, dokumentu a vypis XMl kodu na vystup.
 */
function end_xml() {
    xmlwriter_end_element($GLOBALS['xml']);              /// Koniec elementu 'program'.
    xmlwriter_end_document($GLOBALS['xml']);
    echo xmlwriter_flush($GLOBALS['xml'], true);         /// Vypis a premazanie bufferu.
}

/** --------------------- HLAVNY PROGRAM --------------------- **/
/*
 * Pociatocna inicializacia hodnot premennych.
 */
$arrOpts = array();                         /// Pole ziskanych parametrov z prikazoveho riadku.
$numofOpts = 0;                             /// Pocet ziskanych prepinacov.
$isStats = false;                           /// Premenna indikuje vyskyt prepinacu --stats.
$wantStats = false;                         /// Premenna indikuje nutnost vyskytu --stats.
$statsFile = '';                            /// Premenna obsahujuca nazov pripadneho suboru pre zapis statistik.
$locCntr = 0;                               /// Pocitadlo instrukcii.
$commentsCntr = 0;                          /// Pocitadlo komentarov.
$jumpsCntr = 0;                             /// Pocitadlo skokov.
$labelsCntr = 0;                            /// Pocitadlo navesti.
$arrLabels = array();                       /// Pole uchovavajuce navestia, nie duplicitne.
$gotLine = '';                              /// Premenna obsahujuca ziskany riadok zo vstupu.
$arrLine = array();                         /// Pole obsahujuce slova zo ziskaneho riadku.
$isIPPcode20 = false;                       /// Premenna indikuje vyskyt uvodneho riadku.
$numofWords = 0;                            /// Pocitadlo slov v riadku.

ini_set('display_errors', 'stderr');        /// Presmerovanie chybovych hlaseni na STDERR, namiesto STDOUT.

/*
 * Rozpoznanie a ziskanie parametrov z prikazoveho riadku a ich poctu.
 */
$arrOpts = getopt('hlcbjs:', array('help', 'stats:', 'loc', 'comments', 'labels', 'jumps'));
$numofOpts = sizeof($arrOpts, COUNT_RECURSIVE);
foreach ($arrOpts as $opt => $optVal) {                 /// Dodatocna uprava poctu prepinacov, kvoli duplikatnym.
    if (is_array($optVal))
        $numofOpts -= 1;
}

/*
 * Detekcia a kontrola parametrov prikazoveho riadku.
 */
foreach ($arrOpts as $opt => $optVal) {
    if (($opt == 'help' || $opt == 'h') && $numofOpts == 1) {                    /// Spravne pouzity parameter --help.
        write_help();
    }
    elseif (($opt == 'help' || $opt == 'h') && $numofOpts > 1) {                 /// Nespravne pouzity parameter --help.
        write_err(ERR_HELP);
    }
    elseif ($wantStats === false && (($opt == 'loc' || $opt == 'l') ||       /// Vznika poziadavka na parameter --stats.
            ($opt == 'comments' || $opt == 'c') ||
            ($opt == 'labels' || $opt == 'b') ||
            ($opt == 'jumps' || $opt == 'j'))) {
        $wantStats = true;
    }
    elseif ($opt == 'stats' || $opt == 's') {           /// Dostali sme parameter --stats.
        if (is_array($optVal) || (array_key_exists('stats', $arrOpts) && array_key_exists('s', $arrOpts))) {
            write_err(ERR_STATS);               /// ^^Test pre duplikatny parameter --stats.
        }
        $isStats = true;
        $statsFile = $optVal;               /// Subor kam sa vypisu statistiky.
    }
}

if ($wantStats === true && $isStats === false) {          /// Nebol detekovany parameter --stats nutny pre konkretny pripad.
    write_err(ERR_STATS);
}

/*
 * Zacina analyza kodu.
 */
while (feof(STDIN) !== true) {
    $gotLine = fgets(STDIN);
    $gotLine = trim_line_and_comment($gotLine);

    if (mb_strlen($gotLine) != 0) {                /// Ignoracia prazdnych riadkov.
        $arrLine = explode(' ', $gotLine);
        $arrLine = trim_words($arrLine);
        $numofWords = sizeof($arrLine, COUNT_RECURSIVE);
        $arrLine['0'] = mb_strtoupper($arrLine['0']);       /// Prevod operacneho kodu na velke pismena kvoli porovnaniu.

        /// Detekcia uvodneho riadku '.IPPcode20'.
        if ($arrLine['0'] == '.IPPCODE20') {
            if ($numofWords != 1) {
                write_err(ERR_SYNTACTIC);
            }
            $isIPPcode20 = true;
            start_xml();                /// Generovanie XML kodu.
        }
        /// Chyba, nebol zaznamenany uvodny riadok '.IPPcode20'.
        elseif ($isIPPcode20 === false) {
            write_err(ERR_IPPCODE20);
        }
        /// Operacne kody bez argumentu.
        elseif ($arrLine['0'] == 'CREATEFRAME' || $arrLine['0'] == 'PUSHFRAME' || $arrLine['0'] == 'POPFRAME' ||
                $arrLine['0'] == 'RETURN' || $arrLine['0'] == 'BREAK') {
            if ($numofWords != 1) {
                write_err(ERR_SYNTACTIC);
            }
            if ($arrLine['0'] == 'RETURN') {
                $jumpsCntr += 1;
            }

            start_instr_elem(++$locCntr, $arrLine['0']);
            end_instr_elem();
        }
        /// Operacne kody ocakavaju len jeden argument <label>.
        elseif ($arrLine['0'] == 'CALL' || $arrLine['0'] == 'JUMP' || $arrLine['0'] == 'LABEL') {
            if ($numofWords != 2) {
                write_err(ERR_SYNTACTIC);
            }
            elseif (mb_ereg('^([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$', $arrLine['1']) === false) {
                write_err(ERR_LEXICAL);                     /// Nespravny nazov navestia.
            }

            if ($arrLine['0'] == 'LABEL') {
                array_push($arrLabels, $arrLine['1']);
            }
            else {
                $jumpsCntr += 1;
            }

            start_instr_elem(++$locCntr, $arrLine['0']);
            write_arg_elem('label', $arrLine['1']);
            end_instr_elem();
        }
        /// Ocakavany neterminal <symb> (konstanta alebo premenna).
        elseif ($arrLine['0'] == 'PUSHS' || $arrLine['0'] == 'WRITE' ||
                $arrLine['0'] == 'EXIT' || $arrLine['0'] == 'DPRINT') {
            if ($numofWords != 2) {
                write_err(ERR_SYNTACTIC);
            }
            elseif (mb_ereg('(^int@([+-]?[0-9]+)$)|(^bool@(true|false)$)|(^string@[^\s#]*)|(^nil@nil$)|(^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$)',
                    $arrLine['1']) === false) {
                write_err(ERR_LEXICAL);
            }

            start_instr_elem(++$locCntr, $arrLine['0']);
            write_arg_elem_symb($arrLine['1']);
            end_instr_elem();
        }
        /// Ocakava sa jeden argument <var>.
        elseif ($arrLine['0'] == 'DEFVAR' || $arrLine['0'] == 'POPS') {
            if ($numofWords != 2) {
                write_err(ERR_SYNTACTIC);
            }
            elseif (mb_ereg('^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$', $arrLine['1']) === false) {
                write_err(ERR_LEXICAL);
            }

            start_instr_elem(++$locCntr, $arrLine['0']);
            write_arg_elem('var', $arrLine['1']);
            end_instr_elem();
        }
        /// Argumenty <var> a <type>.
        elseif ($arrLine['0'] == 'READ') {
            if ($numofWords != 3) {
                write_err(ERR_SYNTACTIC);
            }
            elseif (mb_ereg('^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$', $arrLine['1']) === false ||
                mb_ereg('^(int)|(string)|(bool)$', $arrLine['2']) === false) {
                write_err(ERR_LEXICAL);
            }

            start_instr_elem(++$locCntr, $arrLine['0']);
            write_arg_elem('var', $arrLine['1']);
            write_arg_elem('type', $arrLine['2'], 2);
            end_instr_elem();
        }
        /// Argumenty <var> a <symb>.
        elseif ($arrLine['0'] == 'MOVE' || $arrLine['0'] == 'INT2CHAR' || $arrLine['0'] == 'STRLEN' ||
                $arrLine['0'] == 'TYPE' || $arrLine['0'] == 'NOT') {
            if ($numofWords != 3) {
                write_err(ERR_SYNTACTIC);
            }
            elseif (mb_ereg('^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$', $arrLine['1']) === false ||
                    mb_ereg('(^int@([+-]?[0-9]+)$)|(^bool@(true|false)$)|(^string@[^\s#]*)|(^nil@nil$)|(^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$)',
                    $arrLine['2']) === false) {
                write_err(ERR_LEXICAL);
            }

            start_instr_elem(++$locCntr, $arrLine['0']);
            write_arg_elem('var', $arrLine['1']);
            write_arg_elem_symb($arrLine['2'], 2);
            end_instr_elem();
        }
        /// Argumenty <var>, <symb1>, <symb2>.
        elseif ($arrLine['0'] == 'ADD' || $arrLine['0'] == 'SUB' || $arrLine['0'] == 'MUL' || $arrLine['0'] == 'IDIV'||
                $arrLine['0'] == 'LT' || $arrLine['0'] == 'GT' || $arrLine['0'] == 'EQ' || $arrLine['0'] == 'AND' ||
                $arrLine['0'] == 'OR' || $arrLine['0'] == 'STRI2INT' || $arrLine['0'] == 'CONCAT' ||
                $arrLine['0'] == 'GETCHAR' || $arrLine['0'] == 'SETCHAR') {
            if ($numofWords != 4) {
                write_err(ERR_SYNTACTIC);
            }
            elseif (mb_ereg('^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$', $arrLine['1']) === false ||
                    mb_ereg('(^int@([+-]?[0-9]+)$)|(^bool@(true|false)$)|(^string@[^\s#]*)|(^nil@nil$)|(^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$)',
                    $arrLine['2']) === false ||
                    mb_ereg('(^int@([+-]?[0-9]+)$)|(^bool@(true|false)$)|(^string@[^\s#]*)|(^nil@nil$)|(^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$)',
                    $arrLine['3']) === false) {
                write_err(ERR_LEXICAL);
            }

            start_instr_elem(++$locCntr, $arrLine['0']);
            write_arg_elem('var', $arrLine['1']);
            write_arg_elem_symb($arrLine['2'], 2);
            write_arg_elem_symb($arrLine['3'], 3);
            end_instr_elem();
        }
        /// Argumenty <label>, <symb1>, <symb2>.
        elseif ($arrLine['0'] == 'JUMPIFEQ' || $arrLine['0'] == 'JUMPIFNEQ') {
            if ($numofWords != 4) {
                write_err(ERR_SYNTACTIC);
            }
            elseif (mb_ereg('^([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$', $arrLine['1']) === false ||
                    mb_ereg('(^int@([+-]?[0-9]+)$)|(^bool@(true|false)$)|(^string@[^\s#]*)|(^nil@nil$)|(^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$)',
                    $arrLine['2']) === false ||
                    mb_ereg('(^int@([+-]?[0-9]+)$)|(^bool@(true|false)$)|(^string@[^\s#]*)|(^nil@nil$)|(^([GLT]F@)([a-zA-Z_\-\$&%\*\!\?][0-9a-zA-Z_\-\$&%\*\!\?]*)$)',
                    $arrLine['3']) === false) {
                write_err(ERR_LEXICAL);
            }
            $jumpsCntr += 1;

            start_instr_elem(++$locCntr, $arrLine['0']);
            write_arg_elem('label', $arrLine['1']);
            write_arg_elem_symb($arrLine['2'], 2);
            write_arg_elem_symb($arrLine['3'], 3);
            end_instr_elem();
        }
        /// Chyba, nespravny operacny kod instrukcie
        else {
            write_err(ERR_OPCODE);
        }
    }
}

end_xml();              /// Ukonci hlavny element, dokument a vypise cely XML kod.

/*
 * Vypis statistik do zadaneho suboru v pripade poziadavky od uzivatela.
 */
if ($statsFile != '') {
    if (($file = fopen("$statsFile", "w")) === false) {
        write_err(ERR_OUTPUT_FOPEN);
    }
    $labelsCntr = sizeof(array_unique($arrLabels));
    foreach ($argv as $key => $value) {
        if ($value != 'parse.php' && (mb_ereg('(--stats.*|-s.*)', $value)) === false) {
            if ($value == '--loc' || $value == '-l') {
                fwrite($file, "$locCntr\n");
            }
            elseif ($value == '--comments' || $value == '-c') {
                fwrite($file, "$commentsCntr\n");
            }
            elseif ($value == '--labels' || $value == '-b') {
                fwrite($file, "$labelsCntr\n");
            }
            elseif ($value == '--jumps' || $value == '-j') {
                fwrite($file, "$jumpsCntr\n");
            }
        }
    }
    fclose($file);
}
