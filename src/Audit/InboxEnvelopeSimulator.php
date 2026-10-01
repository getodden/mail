<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Audit;

class InboxEnvelopeSimulator
{
    public const MOBILE_SUBJECT_LIMIT = 38;

    public const DESKTOP_SUBJECT_LIMIT = 65;

    public const MOBILE_PREVIEW_LIMIT = 45;

    public const DESKTOP_PREVIEW_LIMIT = 90;

    public const APPLE_LOCKSCREEN_SUBJECT_LIMIT = 42;

    public const APPLE_LOCKSCREEN_PREVIEW_LIMIT = 60;

    /**
     * Simulate how an email appears across major inbox client envelopes.
     *
     * @return array<string, mixed>
     */
    public static function simulate(
        string $subject,
        ?string $previewText = null,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): array {
        $subject = trim($subject);
        $previewText = $previewText !== null ? trim($previewText) : '';
        $fromName = $fromName !== null ? trim($fromName) : 'Company Name';
        $fromEmail = $fromEmail !== null ? trim($fromEmail) : 'hello@example.com';

        $subjectLen = mb_strlen($subject);
        $subjectWords = count(preg_split('/\s+/', $subject, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        $previewLen = mb_strlen($previewText);

        $isSubjectTruncatedMobile = $subjectLen > self::MOBILE_SUBJECT_LIMIT;
        $isSubjectTruncatedDesktop = $subjectLen > self::DESKTOP_SUBJECT_LIMIT;
        $isPreviewTruncatedMobile = $previewLen > self::MOBILE_PREVIEW_LIMIT;
        $isPreviewTruncatedDesktop = $previewLen > self::DESKTOP_PREVIEW_LIMIT;

        $mobileSubject = $isSubjectTruncatedMobile
            ? mb_substr($subject, 0, self::MOBILE_SUBJECT_LIMIT - 3).'...'
            : $subject;

        $desktopSubject = $isSubjectTruncatedDesktop
            ? mb_substr($subject, 0, self::DESKTOP_SUBJECT_LIMIT - 3).'...'
            : $subject;

        $mobilePreview = $isPreviewTruncatedMobile
            ? mb_substr($previewText, 0, self::MOBILE_PREVIEW_LIMIT - 3).'...'
            : $previewText;

        $desktopPreview = $isPreviewTruncatedDesktop
            ? mb_substr($previewText, 0, self::DESKTOP_PREVIEW_LIMIT - 3).'...'
            : $previewText;

        $appleLockscreenSubject = mb_strlen($subject) > self::APPLE_LOCKSCREEN_SUBJECT_LIMIT
            ? mb_substr($subject, 0, self::APPLE_LOCKSCREEN_SUBJECT_LIMIT - 3).'...'
            : $subject;

        $appleLockscreenPreview = mb_strlen($previewText) > self::APPLE_LOCKSCREEN_PREVIEW_LIMIT
            ? mb_substr($previewText, 0, self::APPLE_LOCKSCREEN_PREVIEW_LIMIT - 3).'...'
            : $previewText;

        // Status determinations
        $subjectStatus = match (true) {
            $subjectLen === 0 => 'empty',
            $subjectLen < 25 => 'short',
            $subjectLen <= 55 => 'optimal',
            $subjectLen <= 70 => 'long',
            default => 'critical',
        };

        $previewStatus = match (true) {
            $previewLen === 0 => 'empty',
            $previewLen < 35 => 'short',
            $previewLen <= 90 => 'optimal',
            default => 'long',
        };

        // Extract domain from sender email for BIMI check
        $fromDomain = '';
        if (str_contains($fromEmail, '@')) {
            $parts = explode('@', $fromEmail);
            $fromDomain = strtolower(trim($parts[1] ?? ''));
        }

        $isFreeWebmail = in_array($fromDomain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com'], true);
        $bimiReady = ! empty($fromDomain) && ! $isFreeWebmail;

        $recommendations = [];
        if ($subjectStatus === 'critical') {
            $recommendations[] = "Subject line ({$subjectLen} chars) will be truncated on almost all mobile and desktop inboxes. Aim for under 50 characters.";
        } elseif ($subjectStatus === 'long') {
            $recommendations[] = "Subject line ({$subjectLen} chars) will be truncated on mobile clients (~38 chars). Place key hooks and keywords first.";
        } elseif ($subjectStatus === 'short') {
            $recommendations[] = "Subject line ({$subjectLen} chars) is very short. Add context or a clear value proposition.";
        }

        if ($previewStatus === 'empty') {
            $recommendations[] = "No preheader text set. Inboxes will pull raw template HTML/text (such as 'View in browser' or navigation links).";
        } elseif ($previewStatus === 'short') {
            $recommendations[] = "Preheader text is only {$previewLen} chars. Expanding it to 50-80 chars gives you a secondary headline in the inbox.";
        }

        if ($isFreeWebmail) {
            $recommendations[] = "Sender domain ({$fromDomain}) is a free webmail provider and cannot authenticate with DMARC/BIMI brand logos.";
        }

        return [
            'from_name' => $fromName,
            'from_email' => $fromEmail,
            'subject' => $subject,
            'subject_length' => $subjectLen,
            'subject_words' => $subjectWords,
            'subject_status' => $subjectStatus,
            'preview_text' => $previewText,
            'preview_length' => $previewLen,
            'preview_status' => $previewStatus,
            'combined_length' => $subjectLen + $previewLen,
            'is_subject_truncated_mobile' => $isSubjectTruncatedMobile,
            'is_subject_truncated_desktop' => $isSubjectTruncatedDesktop,
            'is_preview_truncated_mobile' => $isPreviewTruncatedMobile,
            'is_preview_truncated_desktop' => $isPreviewTruncatedDesktop,
            'previews' => [
                'mobile' => [
                    'subject' => $mobileSubject,
                    'preview_text' => $mobilePreview,
                    'is_truncated' => $isSubjectTruncatedMobile || $isPreviewTruncatedMobile,
                ],
                'desktop' => [
                    'subject' => $desktopSubject,
                    'preview_text' => $desktopPreview,
                    'is_truncated' => $isSubjectTruncatedDesktop || $isPreviewTruncatedDesktop,
                ],
                'apple_lockscreen' => [
                    'subject' => $appleLockscreenSubject,
                    'preview_text' => $appleLockscreenPreview,
                    'is_truncated' => mb_strlen($subject) > self::APPLE_LOCKSCREEN_SUBJECT_LIMIT,
                ],
            ],
            'bimi' => [
                'ready' => $bimiReady,
                'domain' => $fromDomain,
                'is_free_provider' => $isFreeWebmail,
            ],
            'recommendations' => $recommendations,
        ];
    }
}
