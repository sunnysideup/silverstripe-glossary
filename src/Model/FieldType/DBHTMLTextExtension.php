<?php

namespace Sunnysideup\Glossary\Model\FieldType;

use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Sunnysideup\Glossary\Model\Term;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class DBHTMLTextExtension extends Extension
{
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
        if (! $pageID) {
            $page = Director::get_current_page();
            if ($page) {
                $pageID = $page->ID;
            }
        }
        $newHTML = Term::link_glossary_terms((string) $html, (int) $pageID);

        // Once Per Term option
        if(isset($options['once_per_term']) && $options['once_per_term'] === true) {
            $crawler = HtmlPageCrawler::create($newHTML);

            $exceptionList = [];

            $crawler->filter('span.glossary-button-and-annotation-holder')->each(
                function($element) use (&$exceptionList) {

                    $term = $element->filter('dfn')->first()->html();

                    if(in_array(strtolower($term), $exceptionList)) {
                        return $element->replaceWith($term);
                    } else {
                        $exceptionList[] = strtolower($term);
                    }

                }
            );

            // A soft replace html entities (SS editor exceptions)
            $editorExceptionFrom = [
                '%5B',
                '%5D'
            ];

            $editorExceptionTo = [
                '[',
                ']'
            ];

            $newHTML = str_replace($editorExceptionFrom, $editorExceptionTo, $crawler->saveHTML());
        }

        $field = DBField::create_field(DBHTMLText::class, $newHTML);
        $field->setProcessShortcodes(true);

        return $field;
    }
}
