<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * HproseTags.php                                         *
 *                                                        *
 * hprose tags library for php5.                          *
 *                                                        *
 * LastModified: Jul 12, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

if (!extension_loaded('hprose')) {

class HproseTags {
    /* Serialize Tags */
    const TagInteger = 'i';
    const TagLong = 'l';
    const TagDouble = 'd';
    const TagNull = 'n';
    const TagEmpty = 'e';
    const TagTrue = 't';
    const TagFalse = 'f';
    const TagNaN = 'N';
    const TagInfinity = 'I';
    const TagDate = 'D';
    const TagTime = 'T';
    const TagUTC = 'Z';
    const TagBytes = 'b';
    const TagUTF8Char = 'u';
    const TagString = 's';
    const TagGuid = 'g';
    const TagList = 'a';
    const TagMap = 'm';
    const TagClass = 'c';
    const TagObject = 'o';
    const TagRef = 'r';
    /* Serialize Marks */
    const TagPos = '+';
    const TagNeg = '-';
    const TagSemicolon = ';';
    const TagOpenbrace = '{';
    const TagClosebrace = '}';
    const TagQuote = '"';
    const TagPoint = '.';
    /* Protocol Tags */
    const TagFunctions = 'F';
    const TagCall = 'C';
    const TagResult = 'R';
    const TagArgument = 'A';
    const TagError = 'E';
    const TagEnd = 'z';
}

} // endif (!extension_loaded('hprose'))
?>