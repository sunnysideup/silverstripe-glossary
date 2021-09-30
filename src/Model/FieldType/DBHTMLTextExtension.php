<?php

namespace Sunnysideup\Glossary\Model\FieldType;

use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Sunnysideup\Glossary\Model\Term;

class DBHTMLTextExtension extends Extension
{
    public function Annotated(?int $pageID = 0)
    {
        $html = $this->owner->getValue();
        if (! $pageID) {
            $page = Director::get_current_page();
            if ($page) {
                $pageID = $page->ID;
            }
        }
        $newHTML = Term::link_glossary_terms((string) $html, (int) $pageID);

        $field = DBField::create_field(DBHTMLText::class, $newHTML);
        $field->setProcessShortcodes(true);

        return $field;
    }
}
