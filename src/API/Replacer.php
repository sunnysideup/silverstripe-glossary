<?php

namespace Sunnysideup\Glossary\API;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\ArrayData;
use DOMDocument;
use DOMXPath;
use SilverStripe\CMS\Model\SiteTree;

class Replacer
{
    use Extensible;
    use Injectable;
    use Configurable;

    /**
     * @var string
     */
    private $pattern;

    private $dataList;

    /**
     * Constructor. Use the builder class instead.
     *
     * @param array $dataList
     */
    public function __construct(
        string $term,
        $dataList,
        array $synonymList,
        array $ignoreBeforeList,
        array $ignoreAfterList,
        bool $isCaseSensitive,
        ?SiteTree $page = null
    ) {
        $termsAll = self::escape_str($term);

        // add synonyms
        $termsAll .= self::join_array_as_regexp('|', $synonymList, '');

        // add ignore lists
        $negLookBehind = self::join_array_as_regexp('(?<!', $ignoreAfterList, '\s)', true);
        $negLookAhead = self::join_array_as_regexp('(?!\s', $ignoreBeforeList, ')', true);

        // add options
        $options = '';
        if (! $isCaseSensitive) {
            // case INsensitive
            $options .= 'i';
        }

        // create a pattern string by combining a word boundary, negative look behind, terms, negative look ahead and options
        $this->pattern = '/\b' . $negLookBehind . '(' . $termsAll . ')' . $negLookAhead . '\b/u' . $options;

        // clone a data list for later use
        $this->dataList = $dataList;
    }

    /**
     * Replace html with glossary links.
     */
    public function replace(string $html): string
    {
        // 1) Parse HTML as UTF-8 (do NOT pre-encode the whole string)
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // (A) Provide encoding hint so libxml parses UTF-8 correctly
        $dom->loadHTML('<?xml encoding="UTF-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 2) Select text nodes that are safe to annotate
        //    - not inside .donotannotate
        //    - not inside <a>
        //    - not inside existing glossary markup
        $nodes = $xpath->query(
            '//text()[' .
                'normalize-space(.) != "" and ' .
                'not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " donotannotate ")]) and ' .
                'not(ancestor::a) and ' .
                'not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " glossary-button-and-annotation-holder ")]) and ' .
                'not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " glossary-annotation-holder ")]) and ' .
                'not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " glossary-button ")])' .
                ']'
        );


        // 3) Replace eligible text nodes with annotated fragments
        // Convert NodeList to array first because we’ll mutate the DOM
        foreach (iterator_to_array($nodes) as $node) {
            /** @var \DOMText $node */
            $original = $node->nodeValue;

            // Quick check: if no term present, skip fast
            if (!preg_match($this->pattern, $original)) {
                continue;
            }

            $fragment = $this->annotateChunk($dom, $original);
            // Replace text node with our mixed (text + HTML) fragment
            $node->parentNode->replaceChild($fragment, $node);
        }

        // 4) Output HTML
        $html = $dom->saveHTML();
        // hack to remove the xml comment...
        return str_replace('<?xml encoding="UTF-8" ?>', '', $html);
    }

    protected function annotateChunk(DOMDocument $domDoc, string $text)
    {
        // Split out shortcodes so we never touch them
        $parts = preg_split('/(\[[^\]]*\])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $frag = $domDoc->createDocumentFragment();

        foreach ($parts as $i => $part) {
            $isShortcode = ($i % 2) === 1;
            if ($isShortcode) {
                // Keep shortcode untouched as plain text
                $frag->appendChild($domDoc->createTextNode($part));
                continue;
            }

            // Further split non-shortcode text by glossary pattern.
            // Because your pattern has a capturing group for the term, PREG_SPLIT_DELIM_CAPTURE
            // yields [text, TERM, text, TERM, ...]
            $pieces = preg_split($this->pattern, $part, -1, PREG_SPLIT_DELIM_CAPTURE);
            // If no split (no matches), just append as text and continue
            if ($pieces === null || count($pieces) === 1) {
                $frag->appendChild($domDoc->createTextNode($part));
                continue;
            }

            foreach ($pieces as $j => $piece) {
                $isCapturedTerm = ($j % 2) === 1; // odd indices are the captured ( ... ) group in $this->pattern
                if (!$isCapturedTerm) {
                    // Plain text → append safely
                    if ($piece !== '') {
                        $frag->appendChild($domDoc->createTextNode($piece));
                    }
                } else {
                    // Matched term → render your template and append as HTML
                    $array = array_merge($this->dataList, ['Title' => $piece]);
                    $arrayData = ArrayData::create($array);
                    $htmlFragment = trim($arrayData->renderWith('GlossaryItemAsPopUp'));

                    if ($htmlFragment !== '') {
                        // (B) Parse the fragment with an explicit UTF-8 hint
                        $tmp = new DOMDocument('1.0', 'UTF-8');
                        libxml_use_internal_errors(true);
                        $tmp->loadHTML(
                            '<?xml encoding="UTF-8" ?><div>' . $htmlFragment . '</div>',
                            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
                        );
                        libxml_clear_errors();

                        $wrapper = $tmp->getElementsByTagName('div')->item(0);
                        if ($wrapper) {
                            foreach (iterator_to_array($wrapper->childNodes) as $child) {
                                $frag->appendChild($domDoc->importNode($child, true));
                            }
                        }
                    }
                }
            }
        }
        return $frag;
    }




    /**
     * Create a regexp pattern from an array.
     *
     * @param string $prefix
     * @param array  $array
     * @param string $suffix
     * @param boolean $individual
     */
    private static function join_array_as_regexp($prefix, &$array, $suffix, $individual = false): string
    {
        // remove empty strings
        $array = array_filter($array, function ($e) {
            return '' !== $e;
        });
        if (0 === count($array)) {
            return '';
        }

        if ($individual) {
            $return = '';
            foreach ($array as $item) {
                $return .= $prefix . self::escape_str($item) . $suffix;
            }
            return $return;
        } else {
            return $prefix . implode('|', self::escape_array($array)) . $suffix;
        }
    }

    /**
     * Quote each array element.
     *
     * @param array $array
     */
    private static function escape_array($array): array
    {
        return array_map(function ($e) {
            return self::escape_str($e);
        }, $array);
    }

    /**
     * Quote a string.
     *
     * @param string $str
     */
    private static function escape_str($str): string
    {
        return preg_quote($str);
    }

    /**
     * Call callback functions for each captured inner/outer strings.
     *
     * @param function $callbackOuter
     * @param function $callbackInner
     *
     * @return string
     */
    private static function for_each_captures_all(string $pattern, string $subject, string $capture, $callbackOuter, $callbackInner)
    {
        $hits = preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);
        if (0 === $hits) {
            if (is_callable($callbackOuter)) {
                $outer = call_user_func($callbackOuter, $subject);
            }
        }

        // split the $subject into the chunks of [outer] <inner> [outer] <inner> ... [outer]
        $result = '';
        $offset = 0;
        foreach ($matches[$capture] as $i => $match) {
            $inner = $match[0];
            $pos   = $match[1];
            $len   = strlen($inner);

            $outer = substr($subject, $offset, $pos - $offset);
            if (is_callable($callbackOuter)) {
                $outer = call_user_func($callbackOuter, $outer);
            }
            if (is_callable($callbackInner)) {
                $inner = call_user_func($callbackInner, $inner);
            }

            $result .= $outer . $inner;
            $offset = $pos + $len;
        }

        if (strlen($subject) > $offset) {
            $tail = substr($subject, $offset);
            if (is_callable($callbackOuter)) {
                $tail = call_user_func($callbackOuter, $tail);
            }
            $result .= $tail;
        }

        return $result;
    }
}
