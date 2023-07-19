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
                document.addEventListener("DOMContentLoaded", function() {
                    var top = document.getElementById("' . $term->getAnchor() . '").offsetTop;

                    var scroll = function(duration, callback) {
                        var start = document.documentElement.scrollTop || document.body.scrollTop;
                        var change = top - start;
                        var startTime = null;

                        function animateScroll(timestamp) {
                            if (!startTime) startTime = timestamp;
                            var progress = timestamp - startTime;
                            var percent = Math.min(progress / duration, 1);
                            document.documentElement.scrollTop = document.body.scrollTop = start + (change * percent);
                            if (progress < duration) {
                                window.requestAnimationFrame(animateScroll);
                            } else if (callback && typeof(callback) === \'function\') {
                                callback();
                            }
                        }
                        window.requestAnimationFrame(animateScroll);
                    };

                    scroll(700, function(){
                        document.getElementById("' . $term->getAnchor() . '").classList.add("highlight");
                    });
                });

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
