<?php

namespace Sunnysideup\Glossary\Admin;

use SilverStripe\Admin\ModelAdmin;
use Sunnysideup\Glossary\Model\Synonym;
use Sunnysideup\Glossary\Model\Term;

class CMSAdmin extends ModelAdmin
{
    public $showImportForm = false;

    private static $managed_models = [
        Term::class,
        Synonym::class,
    ];

    private static $url_segment = 'glossary';

    private static $menu_title = 'Glossary';

    private static $menu_icon_class = 'font-icon-book';
}
