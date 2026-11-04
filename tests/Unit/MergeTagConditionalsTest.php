<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\MergeTags\MergeTagInterpolator;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MergeTagConditionalsTest extends TestCase
{
    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function conditionals(): array
    {
        $on = ['a' => true, 'b' => true];
        $aOnly = ['a' => true, 'b' => false];
        $bOnly = ['a' => false, 'b' => true];
        $off = ['a' => false, 'b' => false];
        $nested = '{% if a %}1{% if b %}2{% endif %}3{% endif %}';
        $nestedElse = '{% if a %}1{% if b %}2{% else %}x{% endif %}3{% else %}4{% endif %}';

        return [
            'if true' => ['<{% if a %}yes{% endif %}>', $on, '<yes>'],
            'if false' => ['<{% if a %}yes{% endif %}>', $off, '<>'],
            'if else true' => ['{% if a %}yes{% else %}no{% endif %}', $on, 'yes'],
            'if else false' => ['{% if a %}yes{% else %}no{% endif %}', $off, 'no'],
            'dotted key' => ['{% if c.vip %}yes{% else %}no{% endif %}', ['c' => ['vip' => true]], 'yes'],
            'two sequential' => ['{% if a %}1{% endif %}-{% if b %}2{% else %}3{% endif %}', $aOnly, '1-3'],
            'multiline' => ["{% if a %}\nyes\n{% else %}\nno\n{% endif %}", $off, "\nno\n"],
            'upper case tags' => ['{% IF a %}yes{% ELSE %}no{% ENDIF %}', $off, 'no'],
            'nested all true' => [$nested, $on, '123'],
            'nested inner false' => [$nested, $aOnly, '13'],
            'nested outer false inner true' => [$nested, $bOnly, ''],
            'nested all false' => [$nested, $off, ''],
            'nested with else a and b' => [$nestedElse, $on, '123'],
            'nested with else a only' => [$nestedElse, $aOnly, '1x3'],
            'nested with else neither' => [$nestedElse, $off, '4'],
            'nested with else b only' => [$nestedElse, $bOnly, '4'],
            'unclosed if is false to the end' => ['x {% if a %}y {% if b %}z{% endif %} tail', $on, 'x '],
            'unclosed if with outer block closed' => ['{% if a %}1{% endif %}2{% if b %}3', $on, '12'],
            'the leak when subscribed' => ['Hi {% if subscribed %}welcome {% if is_vip %}VIPSECRET{% endif %}', ['subscribed' => true, 'is_vip' => false], 'Hi '],
            'the leak when everything is true' => ['Hi {% if subscribed %}welcome {% if is_vip %}VIPSECRET{% endif %}', ['subscribed' => true, 'is_vip' => true], 'Hi '],
            'the leak when unsubscribed' => ['Hi {% if subscribed %}welcome {% if is_vip %}VIPSECRET{% endif %}', ['subscribed' => false, 'is_vip' => true], 'Hi '],
            'stray endif is dropped' => ['a{% endif %}b', $on, 'ab'],
            'stray else is dropped' => ['a{% else %}b', $on, 'ab'],
            'extra endif after a block' => ['{% if a %}x{% endif %}{% endif %}y', $on, 'xy'],
            'extra endif after a false block' => ['{% if a %}x{% endif %}{% endif %}y', $off, 'y'],
            'second else hides the whole block' => ['[{% if a %}1{% else %}2{% else %}3{% endif %}]', $on, '[]'],
            'second else hides the whole block when false' => ['[{% if a %}1{% else %}2{% else %}3{% endif %}]', $off, '[]'],
            'broken inner block hides itself only' => ['{% if a %}p{% if b %}1{% else %}2{% else %}3{% endif %}q{% endif %}', $on, 'pq'],
            'a condition that is not a plain key is false' => ['{% if a == b %}SECRET{% endif %}', $on, ''],
            'an empty condition is false' => ['{% if %}SECRET{% endif %}', $on, ''],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    #[DataProvider('conditionals')]
    public function test_conditionals_are_evaluated_with_a_stack_and_fail_closed(string $template, array $context, string $expected): void
    {
        $this->assertSame($expected, (new MergeTagInterpolator)->interpolate($template, $context));
    }

    public function test_variables_inside_a_kept_branch_still_resolve(): void
    {
        $output = (new MergeTagInterpolator)->interpolate('{% if vip %}Hi {{ name }}{% else %}Hi guest{% endif %}', ['vip' => true, 'name' => 'Ada']);

        $this->assertSame('Hi Ada', $output);
    }

    public function test_a_long_run_of_unclosed_tags_is_handled_quickly(): void
    {
        $started = microtime(true);

        $output = (new MergeTagInterpolator)->interpolate(str_repeat('{% if a %}x', 20_000).str_repeat(' ', 100_000), ['a' => true]);

        $this->assertSame('', $output);
        $this->assertLessThan(1.0, microtime(true) - $started);
    }
}
