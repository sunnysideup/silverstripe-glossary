<?php
namespace Sunnysideup\Glossary\Middleware;

use DOMDocument;
use DOMXPath;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;

class GlossaryMiddleware implements HTTPMiddleware
{
    /**
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);

        if (!$response) {
            return null;
        }

        if (
            $request->routeParams()['Controller'] != 'SilverStripe\Admin\AdminRootController'
            && $request->routeParams()['Controller'] != '%$SilverStripe\GraphQL\Controller.admin'
            && strpos(strtolower($response->getHeader('content-type')), 'text/html') !== false
        ) {
            $exceptionList = [];

            // Get document content as a string
            $body = $response->getBody();

            if ($body) {
                // Load document content into DOMDocument
                $dom = new DOMDocument();
                @$dom->loadHTML($body);

                // Create a new DOMXPath instance to query the DOMDocument
                $xpath = new DOMXPath($dom);

                // Find all span elements with the class name "glossary-button-and-annotation-holder"
                $spanNodes = $xpath->query("//span[contains(@class, 'glossary-button-and-annotation-holder')]");

                // Iterate over the found span nodes and replace them with their own plain text
                foreach ($spanNodes as $spanNode) {
                    $oncePerPage = false;

                    foreach ($spanNode->attributes as $attr) {
                        if ($attr->name === 'data-once-per-page' && (bool)$attr->value === true) {
                            $oncePerPage = true; break;
                        }
                    }

                    // Get plain text from the first child of the span node
                    if ($oncePerPage && $spanNode->firstChild && $term = $spanNode->firstChild->nodeValue) {
                        if (in_array(strtolower($term), $exceptionList)) {
                            // Create a plain text node
                            $plainText = $dom->createTextNode($term);
        
                            // Replace the span element with the new plain text node
                            $spanNode->parentNode->replaceChild($plainText, $spanNode);
                        } else {
                            $exceptionList[] = strtolower($term);
                        }
                    }                
                }

                // Update response body with modified HTML content
                if (!empty($exceptionList)) {
                    $response->setBody($dom->saveHTML());
                }

                //
                unset($body, $dom, $xpath, $spanNodes, $spanNode);
            }
        }

        return $response;
    }
}
