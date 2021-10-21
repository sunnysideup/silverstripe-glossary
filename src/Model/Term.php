<?php

namespace Sunnysideup\Glossary\Model;

use Page;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use Sunnysideup\Glossary\API\ReplacerBuilder;
use Sunnysideup\Glossary\PageTypes\GlossaryPage;

// use Sunnysideup\SiteTreeCanEdit\Model\Traits\SiteTreeCanEdit;

class Term extends DataObject
{
    // use SiteTreeCanEdit;
    /**
     * @var string
     */
    public const TEMP_PLACE_HOLDER = '|?';

    /**
     * @var string
     */
    public const HTML_CLASS = 'glossary-annotation';

    private static $table_name = 'GlossaryTerm';

    // private static $table_name = 'Term';

    //######################
    //## Names Section
    //######################

    private static $singular_name = 'Glossary Term';

    private static $plural_name = 'Glossary Terms';

    //######################
    //## Model Section
    //######################

    private static $db = [
        'Title' => 'Varchar(255)',
        'ExplanationShort' => 'Varchar(255)',
        'ExplanationFull' => 'HTMLText',
        'IgnoreAfter' => 'Text',
        'IgnoreBefore' => 'Text',
        'IsCaseSensitive' => 'Boolean(0)',
        'DoNotAnnotate' => 'Boolean(0)',
        'Archived' => 'Boolean(0)',
        'URLSegment' => 'Varchar',
    ];

    private static $has_many = [
        'Synonyms' => Synonym::class,
    ];

    private static $many_many = [
        'DoNotAnnotateOn' => Page::class,
        'OnlyAnnotateOn' => Page::class,
    ];

    //######################
    //## Further DB Field Details
    //######################

    private static $indexes = [
        'Title' => [
            'type' => 'unique',
        ],
        'Archived' => true,
        'DoNotAnnotate' => true,
    ];

    private static $defaults = [
        'Archived' => false,
        'DoNotAnnotate' => false,
        'IsCaseSensitive' => false,
    ];

    private static $default_sort = [
        'Archived' => 'ASC',
        'Title' => 'ASC',
    ];

    private static $required_fields = [
        'Title',
        'ExplanationShort',
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'ExplanationShort' => 'PartialMatchFilter',
        'ExplanationFull' => 'PartialMatchFilter',
        'IgnoreAfter' => 'PartialMatchFilter',
        'IgnoreBefore' => 'PartialMatchFilter',
        'IsCaseSensitive' => 'ExactMatchFilter',
        'Archived' => 'ExactMatchFilter',
    ];

    //######################
    //## Field Names and Presentation Section
    //######################

    // private static $field_labels = [
    //     'ClassName' => 'asdf'
    // ];
    //
    // private static $field_labels_right = [
    //     'Created' => 'asdf'
    // ];

    private static $summary_fields = [
        'Title' => 'Term',
        'ExplanationShort' => 'Explanation',
        'DoNotAnnotate.Nice' => 'Do not Annotate',
        'Archived.Nice' => 'No longer in use',
    ];

    //######################
    //## Casting Section
    //######################
    private static $casting = [
        'ListOfSynonyms' => 'Varchar',
        'ListOfDoNotUseOn' => 'Varchar',
        'ListOfOnlyUseOn' => 'Varchar',
        'Link' => 'Varchar',
        'FirstLetter' => 'Varchar',
        'Anchor' => 'Varchar',
    ];

    //######################
    //## THE REPLACEMENTS ...
    //######################

    private static $_glossary_cache;

    private $replacer;

    public function i18n_singular_name()
    {
        return _t(self::class . '.SINGULAR_NAME', 'Glossary Term');
    }

    public function i18n_plural_name()
    {
        return _t(self::class . '.PLURAL_NAME', 'Glossary Terms');
    }

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
                        'REQUIRED_Term_' . $field
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
                            'UNIQUE_Term_' . $field
                        );
                    }
                }
            }
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
        $labels['Title'] = _t(self::class . '.Title', 'Term');
        $labels['ExplanationShort'] = _t(self::class . '.ExplanationShort', 'Short Explanation');
        $labels['ExplanationFull'] = _t(self::class . '.ExplanationFull', 'Full Explanation');
        $labels['Synonyms'] = _t(self::class . '.Synonyms', 'Synonyms');
        $labels['IgnoreAfter'] = _t(self::class . '.IgnoreAfter', 'Ignore After');
        $labels['IgnoreBefore'] = _t(self::class . '.IgnoreBefore', 'Ignore Before');
        $labels['IsCaseSensitive'] = _t(self::class . '.IsCaseSensitive', 'Case-sensitive');
        $labels['DoNotAnnotateOn'] = _t(self::class . '.DoNotAnnotateOn', 'Ignore on ...');
        $labels['OnlyAnnotateOn'] = _t(self::class . '.OnlyAnnotateOn', 'Only annotate on ...');
        $labels['ListOfSynonyms'] = _t(self::class . '.ListOfSynonyms', 'List of Synonyms');
        $labels['Archived'] = _t(self::class . '.Archived', 'Archived');
        $labels['DoNotAnnotate'] = _t(self::class . '.DoNotAnnotate', 'Do not annotate');

        return $labels;
    }

    public function fieldLabelsRight($includerelations = true)
    {
        $labels = [];
        $labels['Title'] = _t(self::class . '.Title_RightLabel', 'The word or phrase to be annotated.');
        $labels['ExplanationShort'] = _t(self::class . '.ExplanationShort_RightLabel', 'Short Description of the Title.');
        $labels['ExplanationFull'] = _t(self::class . '.ExplanationFull_RightLabel', 'Full description of the Title.');
        $labels['Synonyms'] = _t(self::class . '.Synonyms_RightLabel', 'e.g. kiwi and antipodean could be synonyms for New Zealander.');
        $labels['IgnoreAfter'] = _t(self::class . '.IgnoreAfter_RightLabel', 'If this terms comes after [terms entered above] then do not annotate it. Separate entries with a new line. Example: enter "<em>Ministry of</em>" above when you do not want to annotate "<em>Ministry of Food</em>" but you do want to annotate the current term <em>"Food"</em>.');
        $labels['IgnoreBefore'] = _t(self::class . '.IgnoreBefore_RightLabel', 'If this terms is immediately before [terms entered above] then do not annotate it. . Separate entries with a new line. Example: enter <em>"Builder"</em> here when you do not want to annotate "<em>Boat Builder</em>" but would like to annotate "<em>Boat</em>" by itself.');
        $labels['IsCaseSensitive'] = _t(self::class . '.IsCaseSensitive_RightLabel', 'Only annotate if case matches?');
        $labels['DoNotAnnotateOn'] = _t(self::class . '.DoNotAnnotateOn_RightLabel', 'You can select pages on which this term should not be annotated.');
        $labels['OnlyAnnotateOn'] = _t(self::class . '.OnlyAnnotateOn_RightLabel', 'You can select specific pages on which this annotation should take place.');
        $labels['Archived'] = _t(self::class . '.Archived_RightLabel', 'No longer in use. The reason you can archive it here is so you can record that a term is no longer in use rather than just removing it (with the risk of it being added again). ');
        $labels['DoNotAnnotate'] = _t(self::class . '.DoNotAnnotate_RightLabel', 'Show in glossary, but do not use for annotate in other pages.');
        $labels['ListOfSynonyms'] = _t(self::class . '.ListOfSynonyms_RightLabel', 'Similar words that should also be annotated.');

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
        return $this->modelAdminSingleton()->Link() .
            $this->classNameForCMS();
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        //do first?? - add right fields
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

        //create advanced tab
        $fields->addFieldsToTab(
            'Root.Advanced',
            [
                $fields->dataFieldByName('IgnoreAfter'),
                $fields->dataFieldByName('IgnoreBefore'),
                $fields->dataFieldByName('IsCaseSensitive'),
                $fields->dataFieldByName('DoNotAnnotate'),
                $fields->dataFieldByName('Archived'),
            ]
        );

        //make pages easier to edit
        if ($this->exists()) {
            $fields->addFieldsToTab(
                'Root.PageExceptions',
                [
                    $fieldA = $fields->dataFieldByName('DoNotAnnotateOn'),
                    LiteralField::create('Separation4', '<br />'),
                    LiteralField::create('Separation5', '<br />'),
                    LiteralField::create('Separation2', '<hr />'),
                    LiteralField::create('Separation6', '<br />'),
                    LiteralField::create('Separation7', '<br />'),
                    $fieldB = $fields->dataFieldByName('OnlyAnnotateOn'),
                ]
            );
            foreach ([$fieldA, $fieldB] as $tempField) {
                $tempField
                    ->getConfig()
                    ->removeComponentsByType(GridFieldAddNewButton::class)
                ;
            }
            $fields->removeFieldFromTab('Root', 'DoNotAnnotateOn');
            $fields->removeFieldFromTab('Root', 'OnlyAnnotateOn');
        }

        //turn off everything when archived
        if ($this->Archived) {
            $archived = $fields->dataFieldByName('Archived');
            $achived = clone $archived;
            $fields->removeByName([
                'Archived'
            ]);
            $this->performReadonlyTransformationForFields($fields, 'db');
            $this->performReadonlyTransformationForFields($fields, 'has_one');
            $this->performReadonlyTransformationForFields($fields, 'belongs_to');

            if ($this->exists()) {
                $this->performReadonlyTransformationForFields($fields, 'has_many');
                $this->performReadonlyTransformationForFields($fields, 'many_many');
                $this->performReadonlyTransformationForFields($fields, 'belongs_many_many');
            }
            $fields->addFieldToTab('Root.Main', $archived);
        }

        $fields->removeByName('ExplanationFull');
        $fields->removeByName('URLSegment');

        return $fields;
    }

    //######################
    //## CASTED Variables
    //######################

    public function getFirstLetter(): string
    {
        if ($this->Title) {
            return strtoupper($this->Title[0]);
        }

        return '';
    }

    public function getAnchor(): string
    {
        return 'position-for-' . Convert::raw2att($this->URLSegment);
    }

    /**
     * @return string
     */
    public function getLink()
    {
        $page = DataObject::get_one(GlossaryPage::class);
        if ($page) {
            return $page->LinkToTitle($this);
        }

        return '404-no-glossary-page';
    }

    public function getArrayOfSynonyms(): array
    {
        return $this->Synonyms()->column('Title');
    }

    public function getArrayOfDoNotAnnotateOnIDs(): array
    {
        return $this->DoNotAnnotateOn()->column('ID') + $this->getArrayOfNeverAnnotateOn();
    }

    public function getArrayOfNeverAnnotateOn(): array
    {
        return Page::get()->filter(['NoAnnotationOnThisPage' => 1])->column('ID');
    }

    public function getArrayOfOnlyAnnotateOnIDs(): array
    {
        return $this->OnlyAnnotateOn()->column('ID');
    }

    public function getListDoNotAnnotateOn()
    {
        return $this->getListRelationsAsPages('DoNotAnnotateOn');
    }

    public function getListOnlyAnnotateOn()
    {
        return $this->getListRelationsAsPages('OnlyAnnotateOn');
    }

    /**
     * @param string $html   html to be annotated
     * @param int    $pageID
     *
     * @return string (html)
     */
    public static function link_glossary_terms(string $html, ?int $pageID = 0): string
    {
        if (null === self::$_glossary_cache) {
            self::$_glossary_cache = self::get()->filter(
                [
                    'Archived' => 0,
                    'DoNotAnnotate' => 0,
                ]
            );
        }
        foreach (self::$_glossary_cache as $term) {
            $html = $term->linkGlossaryTermsWorker($html, $pageID);
        }

        return $html;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Title = trim($this->Title);
        $this->URLSegment = strtolower(urlencode($this->Title));

        $this->IgnoreBefore = str_ireplace($this->Title, '', $this->IgnoreBefore);
        $this->IgnoreAfter = str_ireplace($this->Title, '', $this->IgnoreAfter);
        //...
    }

    protected function modelAdminSingleton()
    {
        return Injector::inst()->get('Sunnysideup\\Glossary\\Admin\\CMSAdmin');
    }

    protected function classNameForCMS(): string
    {
        return CMSMenu::get_menu_code($this->ClassName);
    }

    protected function performReadonlyTransformationForFields(FieldList $fields, string $rel)
    {
        $arrayOfFieldNames = $this->config()->get($rel);
        if(! empty($arrayOfFieldNames)) {
            $arrayOfFieldNameKeys = array_keys($arrayOfFieldNames);
            foreach ($arrayOfFieldNameKeys as $tempField) {
                $field = $fields->dataFieldByName($tempField);
                if($field) {
                    $fields->replaceField($tempField, $field->performReadonlyTransformation());
                }
            }
        }
    }

    protected function getListRelationsAsPages($method): string
    {
        if ($this->{$method}()->count()) {
            $array = [];
            foreach ($this->{$method}() as $page) {
                $array[] = $page->Breadcrumb();
            }

            return implode(',', str_replace(',', ';', $array));
        }

        return _t(self::class . '.NONE', '(none)');
    }

    /**
     * Annotate html by inserting hyperlinks of terms.
     *
     * @param string $html
     * @param int    $pageID
     */
    private function linkGlossaryTermsWorker($html, $pageID): string
    {
        $allowed = $this->isAnnotationEnabled($pageID);
        if ($allowed) {
            // create and cache a replacer
            if (! $this->replacer) {
                $this->replacer = ReplacerBuilder::from($this->Title)
                    //->addArrayDataValue('Title', $this->Title) // NOTE: Title is always overridden by the Replacer class
                    ->addArrayDataValue('Link', $this->getLink())
                    ->addArrayDataValue('ExplanationShort', $this->ExplanationShort)
                    ->addSynonyms($this->getArrayOfSynonyms())
                    ->addIgnoreBefores(self::list_to_array($this->IgnoreBefore))
                    ->addIgnoreAfters(self::list_to_array($this->IgnoreAfter))
                    ->caseSensitive($this->IsCaseSensitive)
                    ->build()
                ;
            }
            // run
            $html = $this->replacer->replace($html);
        }

        return $html;
    }

    /**
     * Convert a line separated string into an array.
     *
     * @param string $string
     */
    private static function list_to_array($string): array
    {
        return array_map('trim', explode("\n", $string));
    }

    /**
     * see if the page is allowed to be annotated.
     */
    private function isAnnotationEnabled(int $pageID): bool
    {
        if ($this->DoNotAnnotate) {
            return false;
        }
        if ($this->Archived) {
            return false;
        }
        // always allow if the page ID is null
        if (! $pageID) {
            return true;
        }

        // see if the term has the white list
        $whiteList = $this->getArrayOfOnlyAnnotateOnIDs();
        $blackList = $this->getArrayOfDoNotAnnotateOnIDs();
        if (count($whiteList) > 0) {
            // if so, see if the page is in the list and NOT in blacklist ...
            return in_array($pageID, $whiteList, true) && ! in_array($pageID, $blackList, true);
        }

        // see if the page is in the black list

        return ! in_array($pageID, $blackList, true);
    }
}
