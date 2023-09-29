<?php

namespace Sunnysideup\Glossary\API;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\ArrayData;

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
        bool $isCaseSensitive
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
        // $html = iconv('utf-8','ascii//TRANSLIT', $html);

        // do NOT process between <a> and </a>
        return self::for_each_captures_all('/<a\b\s*(.*?)<\/a>/', $html, 1, function ($outerAnchorHtml) {
            // do NOT process inside *.donotannotate
            return self::for_each_captures_all('/([^=]*)([^(a-z|A-Z|0-9|\-|_)])donotannotate("|([^(a-z|A-Z|0-9|\-|_)]).*")/u', $outerAnchorHtml, 1, function ($outerDoNotAnnotate) {
                // do NOT process inside shortcodes
                return self::for_each_captures_all('/\[[^\]]*?\]/u', $outerDoNotAnnotate, 0, function ($outerShortcodeHtml) {
                    // do NOT process inside HTML tags i.e. '<' and '>'
                    return self::for_each_captures_all('/\<[^\>]*?\>/u', $outerShortcodeHtml, 0, function ($outerTagsHtml) {
                        // annotate the rest of html
                        return self::for_each_captures_all($this->pattern, $outerTagsHtml, 1, null, function ($term) {
                            // override 'Title' with the current term
                            $array = array_merge($this->dataList, ['Title' => $term]);
                            // create an ArrayData
                            $arrayData = ArrayData::create($array);
                            // render it - TRIM is important here so that a fullstop can sit flush against item.
                            return trim($arrayData->renderWith('GlossaryItemAsPopUp'));
                        });
                    }, null);
                }, null);
            }, null);
        }, null);
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

        if($individual) {
            $return = '';
            foreach($array as $item) {
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
            $pos = $match[1];
            $len = strlen($inner);
            $outer = substr($subject, $offset, $pos - $offset);
            $offset += $pos + strlen($inner);
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
