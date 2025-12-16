<?php

namespace Sunnysideup\Glossary\PageTypes;

use Page;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\Glossary\Model\Term;

class GlossaryPage extends Page
{
    private static $description = 'Provides definitions of all Glossary (Annotated) Terms';

    private static $table_name = 'GlossaryPage';

    private static $singular_name = 'Glossary Page';

    private static $plural_name = 'Glossary Pages';

    private static $icon = 'sunnysideup/glossary: client/images/icons/treeicons/GlossaryPage.png';

    private static $defaults = [
        'NoAnnotationOnThisPage' => true,
    ];


    public function LinkToTitle($term)
    {
        return $this->Link('showterm/' . $term->URLSegment . '/');
    }

    public function canCreate($member = null, $context = [])
    {
        return GlossaryPage::get()->filter(['ClassName' => GlossaryPage::class])->exists() ? false : parent::canCreate($member, $context);
    }


    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(
            function (FieldList $fields) {
                //remove all fields and add a link to glossary model admin
                $term = Injector::inst()->get(Term::class);
                $fields->addFieldToTab(
                    'Root.Terms',
                    LiteralField::create(
                        'GlossaryMessage',
                        '<h2>
                            The content for this page is automatically generated from your Glossary Terms
                        </h2>
                        <p>
                            <a href="' . $term->CMSListLink() . '">View / edit</a> existing glossary terms OR
                            <a href="' . $term->CMSAddLink() . '">add</a> a new glossary term.
                        </p>'
                    ),
                    'Metadata'
                );
            }
        );


        $fields = parent::getCMSFields();
        $fields->removeByName('NoAnnotationOnThisPage');
        return $fields;
    }

    protected function onBeforeWrite()
    {
        $this->NoAnnotationOnThisPage = true;
        return parent::onBeforeWrite();
    }
}
