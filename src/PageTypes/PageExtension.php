<?php

namespace Sunnysideup\Glossary\PageTypes;

use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;

class PageExtension extends SiteTreeExtension
{
    private static $db = [
        'NoAnnotationOnThisPage' => 'Boolean',
    ];

    private static $field_labels = [
        'NoAnnotationOnThisPage' => 'Do not annotate this page',
    ];

    /**
     * Modify the settings for a SiteTree
     *
     * {@inheritDoc}
     *
     * @param FieldList $fields
     */
    public function updateSettingsFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Annotation',
            [
                CheckboxField::create('NoAnnotationOnThisPage', $this->owner->fieldLabel('NoAnnotationOnThisPage')),
            ]
        );
    }
}
