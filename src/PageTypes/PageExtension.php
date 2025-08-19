<?php

namespace Sunnysideup\Glossary\PageTypes;

use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;

class PageExtension extends SiteTreeExtension
{
    private static $db = [
        'NoAnnotationOnThisPage' => 'Boolean',
        'OneAnnotationPerTerm' => 'Boolean',
    ];

    private static $field_labels = [
        'NoAnnotationOnThisPage' => 'Do not annotate this page',
        'OneAnnotationPerTerm' => 'One annotation per term per page',
    ];

    /**
     * Modify the settings for a SiteTree.
     *
     * {@inheritDoc}
     */
    public function updateSettingsFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Annotation',
            [
                CheckboxField::create('NoAnnotationOnThisPage', $this->owner->fieldLabel('NoAnnotationOnThisPage')),
                CheckboxField::create('OneAnnotationPerTerm', $this->owner->fieldLabel('OneAnnotationPerTerm'))
                    ->setDescription(_t($this->owner->class . '.OneAnnotationPerTerm', 'Only annotate once per term for the whole page.'))
            ]
        );
    }
}
