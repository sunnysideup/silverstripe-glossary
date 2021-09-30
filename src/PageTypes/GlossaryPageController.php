<?php

namespace Sunnysideup\Glossary\PageTypes;

use PageController;
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
                    jQuery(document).ready(
                        function() {
                            var top = document.getElementById("' . $term->getAnchor() . '").offsetTop;
                            jQuery("html, body").animate(
                                {
                                    scrollTop: top
                                },
                                700,
                                function(){
                                    jQuery("#' . $term->getAnchor() . '").addClass("highlight");
                                }
                            );
                        }
                    );
                    ',
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
