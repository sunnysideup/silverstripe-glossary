<?php

namespace Sunnysideup\Glossary\API;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

class ReplacerBuilder
{
    use Extensible;
    use Injectable;
    use Configurable;

    private $term;

    private $dataList = [];

    private $synonymList = [];

    private $ignoreBeforeList = [];

    private $ignoreAfterList = [];

    private $isCaseSensitive = false;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public static function from($term): self
    {
        return self::create($term);
    }

    public function tag($tagName): self
    {
        $this->tag = $tagName;

        return $this;
    }

    public function addArrayData($data): self
    {
        $this->dataList = array_merge($this->dataList, $data);

        return $this;
    }

    public function addArrayDataValue($key, $value): self
    {
        return $this->addArrayData([$key => $value]);
    }

    public function addSynonyms($words): self
    {
        $this->synonymList = array_merge($this->synonymList, $words);

        return $this;
    }

    public function addIgnoreBefores($words): self
    {
        $this->ignoreBeforeList = array_merge($this->ignoreBeforeList, $words);

        return $this;
    }

    public function addIgnoreAfters($words): self
    {
        $this->ignoreAfterList = array_merge($this->ignoreAfterList, $words);

        return $this;
    }

    public function caseSensitive($caseSens = true): self
    {
        $this->isCaseSensitive = $caseSens;

        return $this;
    }

    public function build(): Replacer
    {
        return new Replacer(
            $this->term,
            $this->dataList,
            $this->synonymList,
            $this->ignoreBeforeList,
            $this->ignoreAfterList,
            $this->isCaseSensitive
        );
    }
}
