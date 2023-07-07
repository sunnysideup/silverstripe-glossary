<?php

namespace Sunnysideup\Glossary\Model\FieldType;

use Page;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Sunnysideup\Glossary\Model\Term;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class DBHTMLTextExtension extends Extension
{
    protected static $exceptionList = [];
    
    public function Annotated(?int $pageID = 0)
    {
        return $this->AnnotateProcess($pageID);
    }

    public function AnnotatedOncePerTerm(?int $pageID = 0)
    {
        return $this->AnnotateProcess($pageID, ['once_per_term' => true]);
    }

    protected function AnnotateProcess(int $pageID, array $options = [])
    {
        $html = $this->owner->getValue();

        $oncePerTermPerPage = false;

        $page = $pageID ? Page::get_by_id($pageID) : Director::get_current_page();

        if ($page) {
            $pageID = $page->ID;
            $oncePerTermPerPage = (bool)$page->OneAnnotationPerTerm;
        }

        $newHTML = Term::link_glossary_terms((string) $html, (int) $pageID);
        

        // Once Per Term option
        $oncePerTerm = $options['once_per_term'] ?? false;

        if($oncePerTerm || $oncePerTermPerPage) {
            $crawler = HtmlPageCrawler::create($newHTML);

            $exceptionList = [];

            $crawler->filter('span.glossary-button-and-annotation-holder')->each(
                function($element) use (&$exceptionList, $oncePerTermPerPage) {

                    $term = $element->filter('dfn')->first()->html();

                    if ($oncePerTermPerPage) {
                        // Disable the annotation by replacing a term with annotation with a plain text term
                        if(in_array(strtolower($term), static::$exceptionList)) {
                            return $element->replaceWith($term);
                        } else {
                            static::$exceptionList[] = strtolower($term);
                        }
                    }   
                    else {
                        // Disable the annotation by replacing a term with annotation with a plain text term
                        if(in_array(strtolower($term), $exceptionList)) {
                            return $element->replaceWith($term);
                        } else {
                            $exceptionList[] = strtolower($term);
                        }
                    }
                }
            );

            $newHTML = $crawler->saveHTML();
        }

        $newHTML = $this->softStrReplacement($newHTML);

        $field = DBField::create_field(DBHTMLText::class, $newHTML);
        $field->setProcessShortcodes(true);

        return $field;
    }

    /**
     * A soft replace html entities (SS editor exceptions), newlines between punctuations
     * @param  string $html
     */
    protected function softStrReplacement($html): string
    {
        $search = [
            '%5B',
            '%5D',
            PHP_EOL . '.',
            PHP_EOL . ',',
            PHP_EOL . '!',
            PHP_EOL . '?',
            PHP_EOL . ':',
            PHP_EOL . ';',
            PHP_EOL . '-',
        ];

        $replace = [
            '[',
            ']',
            '.',
            ',',
            '!',
            '?',
            ':',
            ';',
            '-',
        ];

        return str_replace($search, $replace, $html);
    }
}
