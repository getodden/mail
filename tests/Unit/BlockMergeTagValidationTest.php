<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use InvalidArgumentException;
use Odden\MailBuilder\Exceptions\InvalidMergeTagStructureException;
use Odden\MailBuilder\Support\BlockMergeTags;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class BlockMergeTagValidationTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function texts(): array
    {
        return [
            'no tags' => ['Hello {{ name }}', []],
            'balanced if' => ['a {% if x %}b{% endif %} c', []],
            'balanced if else' => ['{% if x %}b{% else %}c{% endif %}', []],
            'two sequential blocks' => ['{% if x %}a{% endif %}{% if y %}b{% endif %}', []],
            'handlebars each with else' => ['{{#each items}}a{{else}}b{{/each}}', []],
            'handlebars unless' => ['{{#unless x}}a{{/unless}}', []],
            'handlebars inverse' => ['{{^x}}a{{/x}}', ['unknown_block_tag']],
            'unclosed if' => ['a {% if x %}b', ['unbalanced_opener']],
            'unclosed handlebars' => ['a {{#if x}}b', ['unbalanced_opener']],
            'stray endif' => ['a {% endif %}', ['unbalanced_closer']],
            'stray handlebars close' => ['a {{/if}}', ['unbalanced_closer']],
            'stray else' => ['a {% else %} b', ['unbalanced_closer']],
            'stray handlebars else' => ['a {{else}} b', ['unbalanced_closer']],
            'second else' => ['{% if x %}a{% else %}b{% else %}c{% endif %}', ['unbalanced_closer']],
            'mismatched closer' => ['{% if x %}a{% endfor %}', ['mismatched_closer']],
            'mismatched handlebars closer' => ['{{#if x}}a{{/each}}', ['mismatched_closer']],
            'nested if' => ['{% if a %}1{% if b %}2{% endif %}3{% endif %}', ['nested_block']],
            'nested mixed kinds' => ['{% if a %}{{#each xs}}x{{/each}}{% endif %}', ['nested_block']],
            'nested for in with' => ['{{#with a}}{% for i in b %}x{% endfor %}{{/with}}', ['nested_block']],
            'the leak' => ['Hi {% if subscribed %}welcome {% if is_vip %}VIPSECRET{% endif %}', ['nested_block', 'unbalanced_opener']],
            'unknown percent tag' => ['{% set x = 1 %}', ['unknown_block_tag']],
            'unknown end tag' => ['{% endfoo %}', ['unknown_block_tag']],
            'unknown handlebars' => ['{{#foo}}a{{/foo}}', ['unknown_block_tag']],
            'case and spacing' => ['{%  IF x  %}a{%ENDIF%}', []],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('texts')]
    public function test_it_reports_the_structural_problems_of_a_text(string $text, array $expected): void
    {
        $problems = BlockMergeTags::validate($text);

        sort($problems);
        sort($expected);

        $this->assertSame($expected, $problems);
    }

    public function test_the_exception_carries_the_field_and_the_codes_and_describes_each_in_plain_english(): void
    {
        $exception = InvalidMergeTagStructureException::in('content', ['nested_block', 'unbalanced_opener']);

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertSame('content', $exception->field);
        $this->assertSame(['nested_block', 'unbalanced_opener'], $exception->problems);
        $this->assertCount(2, $exception->descriptions());
        $this->assertStringContainsString('nested', $exception->descriptions()[0]);
    }
}
