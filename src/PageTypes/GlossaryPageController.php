<?php

namespace Sunnysideup\Glossary\PageTypes;

use PageController;
use PharIo\Manifest\Requirement;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\GroupedList;
use SilverStripe\View\Requirements;
use Sunnysideup\Glossary\Model\Term;

class GlossaryPageController extends PageController
{
    private static $allowed_actions = [
        'index',
        'showterm',
    ];

    public function init()
    {
        parent::init();
        Requirements::javascript('sunnysideup/glossary: client/dist/app.js');
        Requirements::css('sunnysideup/glossary: client/dist/main.css');
    }

    public function getTerms()
    {
        return Term::get()->exclude(['Archived' => 1])->sort(['Title' => 'ASC']);
    }

    public function getGroupedTerms()
    {
        return GroupedList::create($this->getTerms());
    }

    public function index($request)
    {
        return [];
    }

    public function showterm($request)
    {
        $URLSegment = Convert::raw2sql($request->param('ID'));
        if (! $URLSegment) {
            return $this->redirect($this->Link());
        }
        $term = Term::get()->filter(['URLSegment' => $URLSegment])->first();

        // does the page exist?
        if ($term && $term->exists()) {
            // is the term available?
            if ($term->Archived) {
                return $this->httpError(403);
            }

            if ($request->isAjax()) {
                return $term->renderWith([
                    'type' => 'Includes',
                    'GlossaryItemAsPopUp',
                ]);
            }
            Requirements::customScript(
                '
                window.glossaryPageAnchorSetByPHP = "' . $term->getAnchor() . '";',
                'TermURLSegment'
            );

            return [];
        }

        return $this->httpError(404);
    }

    public function ClassName()
    {
        return 'GlossaryPage';
    }
}
