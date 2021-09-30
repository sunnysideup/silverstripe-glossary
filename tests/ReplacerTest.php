<?php

use SilverStripe\Dev\SapphireTest;
use Sunnysideup\Glossary\API\ReplacerBuilder;

/**
 * @internal
 * @coversNothing
 */
class ReplacerTest extends SapphireTest
{
    public function testLoaf()
    {
        $test_cases = [
            'A loaf of bread.' => 1,
            'Get your meatloaf here.' => 0,
            'Get your meat loaf here.' => 0,
            'I lost my loafer.' => 0,
            'Grab some loaf bread.' => 0,
            "Let's meet loaf here." => 1,
            'Grab some loaf breads.' => 0,
        ];

        $replacer = ReplacerBuilder::from('loaf')
            ->addArrayDataValue('Link', '#')
            ->addArrayDataValue('ExplanationShort', 'Kind of bread.')
            ->addIgnoreAfters(['meat'])     // ignore 'meat loaf'
            ->addIgnoreBefores(['bread'])   // ignore 'loaf bread'
            ->build()
        ;

        foreach ($test_cases as $test => $expected) {
            $result = $replacer->replace($test);
            $count = self::countPopups($result);
            $this->assertSame($expected, $count, $result);
        }
    }

    public function testNZ()
    {
        $test_cases = [
            'This is New Zealand.' => 1,
            'Maori people call it Aotearoa.' => 1,
            'Jetstar is cheaper than Air New Zealand.' => 0,
            "It's not New Zealand Airline, right?" => 1,
            "Isn't it a New Zealand owned company?" => 1,
            'This is [New Zealand].' => 0,
            'NZ. ANZ. A NZ.' => 2,
            'Blah blah <a href="#" title="This is New Zealand baby">foo bar baz</a> blah blah.' => 0,
            'Hello H<b>ell<i>o New Zealand Mo</i>rn</b>ing.' => 1,
            '<img src="nz.jpg" alt="New Zealand">' => 0,
        ];

        $replacer = ReplacerBuilder::from('New Zealand')
            ->addArrayDataValue('Link', '//www.nz.com')
            ->addArrayDataValue('ExplanationShort', 'A country full of kiwi.')
            ->addIgnoreAfters(['Air'])    // ignore 'Air New Zealand'
            ->addSynonyms(['Aotearoa', 'NZ'])   // Maori name, short name
            ->caseSensitive(true)
            ->build()
        ;

        foreach ($test_cases as $test => $expected) {
            $result = $replacer->replace($test);
            $count = self::countPopups($result);
            $this->assertSame($expected, $count, $result);
        }
    }

    private static function countPopups($html): int
    {
        return substr_count($html, 'glossary-button-and-annotation-holder');
    }
}
