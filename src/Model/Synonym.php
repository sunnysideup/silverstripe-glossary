<?php

namespace Sunnysideup\Glossary\Model;

use SilverStripe\Admin\CMSMenu;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;

// use Sunnysideup\SiteTreeCanEdit\Model\Traits\SiteTreeCanEdit;

class Synonym extends DataObject
{
    //     use SiteTreeCanEdit;

    private static $table_name = 'GlossarySynonym';

    // private static $table_name = 'Synonym';

    //######################
    //## Names Section
    //######################

    private static $singular_name = 'Synonym';

    private static $plural_name = 'Synonyms';

    //######################
    //## Model Section
    //######################

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_one = [
        'ParentTerm' => Term::class,
    ];

    //######################
    //## Further DB Field Details
    //######################

    private static $indexes = [
        'Title' => [
            'type' => 'unique',
        ],
    ];

    private static $default_sort = [
        'Title' => 'ASC',
    ];

    private static $required_fields = [
        'Title',
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
    ];

    //######################
    //## Field Names and Presentation Section
    //######################
    //
    // private static $field_labels = [
    //     'ClassName' => 'asdf'
    // ];
    //
    // private static $field_labels_right = [
    //     'Created' => 'asdf'
    // ];

    private static $summary_fields = [
        'Title' => 'Synonym',
        'ParentTerm.Title' => 'Synonym for',
    ];

    public function i18n_singular_name()
    {
        return _t(self::class . '.SINGULAR_NAME', 'Synonym');
    }

    public function i18n_plural_name()
    {
        return _t(self::class . '.PLURAL_NAME', 'Synonyms');
    }

    //######################
    //## Casting Section
    //######################

    //######################
    //## can Section
    //######################

    //######################
    //## write Section
    //######################

    public function validate()
    {
        $result = parent::validate();
        $fieldLabels = $this->FieldLabels();
        $indexes = $this->Config()->get('indexes');
        $requiredFields = $this->Config()->get('required_fields');
        if (is_array($requiredFields)) {
            foreach ($requiredFields as $field) {
                $value = $this->{$field};
                if (! $value) {
                    $fieldWithoutID = $field;
                    if ('ID' === substr($fieldWithoutID, -2)) {
                        $fieldWithoutID = substr($fieldWithoutID, 0, -2);
                    }
                    $myName = isset($fieldLabels[$fieldWithoutID]) ? $fieldLabels[$fieldWithoutID] : $fieldWithoutID;
                    $result->addError(
                        _t(
                            self::class . '.' . $field . '_REQUIRED',
                            $myName . ' is required'
                        ),
                        'REQUIRED_SYNONYM_' . strtoupper($field)
                    );
                }
                if (isset($indexes[$field], $indexes[$field]['type']) && 'unique' === $indexes[$field]['type']) {
                    $id = (empty($this->ID) ? 0 : $this->ID);
                    $count = self::get()
                        ->filter([$field => $value])
                        ->exclude(['ID' => $id])
                        ->count()
                    ;
                    if ($count > 0) {
                        $myName = $fieldLabels[$field];
                        $result->addError(
                            _t(
                                self::class . '.' . $field . '_UNIQUE',
                                $myName . ' needs to be unique'
                            ),
                            'UNIQUE__SYNONYM_' . strtoupper($field)
                        );
                    }
                }
            }
        }
        //check if it exists as term!
        if (Term::get()->filter(['Title' => $this->Title])->count()) {
            $myName = $fieldLabels['Title'];
            $result->addError(
                _t(
                    self::class . '.TITLE_TERM_UNIQUE',
                    $myName . ' exists as Glossary Term and therefore can not be used.'
                ),
                'UNIQUE__SYNONYM_WITH_TERM' . strtoupper('TITLE')
            );
        }

        return $result;
    }

    //######################
    //## Import / Export Section
    //######################

    //######################
    //## CMS Edit Section
    //######################

    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Title'] = _t(self::class . '.Title', 'Synonym');
        $labels['ParentTerm'] = _t(self::class . '.ParentTerm', 'Synonym For');

        return $labels;
    }

    public function fieldLabelsRight($includerelations = true)
    {
        $labels = [];
        $labels['Title'] = _t(self::class . '.Title_RightLabel', 'Another phrase that will be annotated just like its parent term.');
        $labels['ParentTerm'] = _t(self::class . '.ParentTerm_RightLabel', 'Acts just like ...');

        return $labels;
    }

    public function CMSEditLink()
    {
        return $this->modelAdminSingleton()->Link() .
            $this->classNameForCMS() . '/EditForm/field/' .
            $this->classNameForCMS() . '/item/' . $this->ID . '/edit';
    }

    public function CMSAddLink()
    {
        return $this->modelAdminSingleton()->Link() .
            $this->classNameForCMS() . '/EditForm/field/' .
            $this->classNameForCMS() . '/item/new';
    }

    public function CMSListLink()
    {
        return $this->modelAdminSingleton()->getLinkForModelClass($this->ClassName);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        //do first??
        $rightFieldDescriptions = $this->fieldLabelsRight();
        foreach ($rightFieldDescriptions as $field => $desc) {
            $formField = $fields->DataFieldByName($field);
            if (! $formField) {
                $formField = $fields->DataFieldByName($field . 'ID');
            }
            if ($formField) {
                $formField->setDescription($desc);
            }
        }

        return $fields;
    }

    protected function modelAdminSingleton()
    {
        return Injector::inst()->get('Sunnysideup\\Glossary\\Admin\\CMSAdmin');
    }

    protected function classNameForCMS()
    {
        return CMSMenu::get_menu_code($this->ClassName);
    }

    //######################
    //## CASTED Variables
    //######################
}
