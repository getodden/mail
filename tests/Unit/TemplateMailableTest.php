<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Mail\TemplateMailable;
use Odden\MailBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TemplateMailableTest extends TestCase
{
    public function test_mailable_compiles_slots_and_interpolates_data(): void
    {
        $slots = [
            [
                'type' => SlotType::Hero->value,
                'data' => [
                    'title' => 'Welcome, {{contact.first_name}}!',
                    'subtitle' => 'Thank you for choosing {{company.name}}.',
                    'button_text' => 'Get Started',
                    'button_url' => 'https://focal.test/app',
                ],
            ],
            [
                'type' => SlotType::RatingBar->value,
                'data' => [
                    'question' => 'Rate our onboarding',
                    'scale_type' => '5',
                    'base_url' => 'https://focal.test/rate',
                ],
            ],
            [
                'type' => SlotType::CountdownTimer->value,
                'data' => [
                    'heading' => 'Special Welcome Deal',
                    'deadline_text' => 'Ends tomorrow',
                    'button_text' => 'Upgrade Now',
                    'button_url' => 'https://focal.test/upgrade',
                ],
            ],
        ];

        $data = [
            'contact' => ['first_name' => 'Samantha'],
            'company' => ['name' => 'Stark Industries'],
        ];

        $mailable = new TemplateMailable(
            template: $slots,
            data: $data,
            subjectLine: 'Welcome to {{company.name}}, {{contact.first_name}}!',
            fromEmail: 'onboarding@focal.test',
            fromName: 'Focal Team'
        );

        $this->assertSame('Welcome to Stark Industries, Samantha!', $mailable->subjectLine);
        $this->assertStringContainsString('Welcome, Samantha!', $mailable->compiledHtml);
        $this->assertStringContainsString('Stark Industries', $mailable->compiledHtml);
        $this->assertStringContainsString('Rate our onboarding', $mailable->compiledHtml);
        $this->assertStringContainsString('Special Welcome Deal', $mailable->compiledHtml);

        // Check envelope
        $envelope = $mailable->envelope();
        $this->assertSame('Welcome to Stark Industries, Samantha!', $envelope->subject);

        // Check content
        $content = $mailable->content();
        $this->assertNotNull($content->htmlString);
        $this->assertStringContainsString('Samantha', (string) $content->htmlString);
        $this->assertStringContainsString('Samantha', $mailable->compiledPlainText);
    }

    public function test_mailable_injects_rfc_8058_one_click_unsubscribe_headers(): void
    {
        $mailable = new TemplateMailable(
            template: '<p>Hello world</p>',
            data: ['unsubscribe_url' => 'https://focal.test/unsubscribe/user123'],
        );

        $headers = $mailable->headers();
        $this->assertNotEmpty($headers->text);
        $this->assertSame('List-Unsubscribe=One-Click', $headers->text['List-Unsubscribe-Post']);
        $this->assertSame('<https://focal.test/unsubscribe/user123>', $headers->text['List-Unsubscribe']);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function unsafeInterpolations(): array
    {
        return [
            'context value' => ['{{link}}', ['link' => 'javascript:alert(1)']],
            'default filter' => ['{{x|default(javascript:alert(1))}}', ['name' => 'a']],
            'mixed case context value' => ['{{link}}', ['link' => 'JaVaScRiPt:alert(1)']],
        ];
    }

    /**
     * @param  array<string, string>  $data
     */
    #[DataProvider('unsafeInterpolations')]
    public function test_a_url_that_only_becomes_unsafe_after_interpolation_is_scrubbed(string $url, array $data): void
    {
        $mailable = new TemplateMailable(
            template: [['type' => SlotType::Event->value, 'data' => ['title' => 'Party', 'date' => 'Soon', 'button_text' => 'RSVP', 'button_url' => $url]]],
            data: $data,
        );

        $this->assertDoesNotMatchRegularExpression('/javascript:/i', $mailable->compiledHtml);
        $this->assertStringContainsString('<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="#"', $mailable->compiledHtml);
        $this->assertStringContainsString('<a href="#" style=', $mailable->compiledHtml);
    }

    public function test_a_string_template_is_scrubbed_after_interpolation(): void
    {
        $mailable = new TemplateMailable(template: '<p><a href="{{link}}">go</a></p>', data: ['link' => 'javascript:alert(1)']);

        $this->assertSame('<p><a href="#">go</a></p>', $mailable->compiledHtml);
    }

    public function test_a_safe_interpolated_url_is_left_alone(): void
    {
        $mailable = new TemplateMailable(template: '<p><a href="{{link}}">go</a></p>', data: ['link' => 'https://example.test/a?b=1']);

        $this->assertSame('<p><a href="https://example.test/a?b=1">go</a></p>', $mailable->compiledHtml);
    }
}
