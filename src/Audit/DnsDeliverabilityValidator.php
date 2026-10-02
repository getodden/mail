<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Audit;

class DnsDeliverabilityValidator
{
    /**
     * @var (callable(string, int): list<array<string, mixed>>)|null
     */
    protected static $dnsResolver = null;

    /**
     * Set a custom DNS record resolver (useful for testing and offline environments).
     *
     * @param  (callable(string, int): list<array<string, mixed>>)|null  $resolver
     */
    public static function setDnsResolver(?callable $resolver): void
    {
        self::$dnsResolver = $resolver;
    }

    /**
     * Fetch DNS TXT records for a given host name.
     *
     * @return list<string>
     */
    protected static function getTxtRecords(string $hostname): array
    {
        if (self::$dnsResolver !== null) {
            $records = (self::$dnsResolver)($hostname, DNS_TXT);
        } else {
            $records = @dns_get_record($hostname, DNS_TXT) ?: [];
        }

        $txtEntries = [];
        foreach ($records as $record) {
            if (isset($record['txt']) && is_string($record['txt'])) {
                $txtEntries[] = $record['txt'];
            } elseif (isset($record['entries']) && is_array($record['entries'])) {
                foreach ($record['entries'] as $entry) {
                    if (is_string($entry)) {
                        $txtEntries[] = $entry;
                    }
                }
            }
        }

        return $txtEntries;
    }

    /**
     * Validate SPF, DMARC, and BIMI DNS records for a given sending domain.
     *
     * @return array{
     *     domain: string,
     *     overall_status: 'optimal'|'good'|'warning'|'critical',
     *     spf: array{found: bool, record: ?string, valid: bool},
     *     dmarc: array{found: bool, record: ?string, policy: ?string, enforced: bool},
     *     bimi: array{found: bool, record: ?string, logo_url: ?string, cert_url: ?string, ready: bool},
     *     recommendations: list<string>
     * }
     */
    public static function validate(string $domain): array
    {
        $domain = strtolower(trim($domain));
        if (str_contains($domain, '@')) {
            $parts = explode('@', $domain);
            $domain = end($parts);
        }

        $recommendations = [];

        // 1. Check SPF on apex / subdomain
        $domainTxt = self::getTxtRecords($domain);
        $spfRecord = null;
        foreach ($domainTxt as $txt) {
            if (str_starts_with($txt, 'v=spf1')) {
                $spfRecord = $txt;
                break;
            }
        }

        $hasSpf = $spfRecord !== null;
        if (! $hasSpf) {
            $recommendations[] = "Missing SPF record on {$domain}. Add a 'v=spf1 ... -all' TXT record to prevent sender spoofing.";
        }

        // 2. Check DMARC at _dmarc.domain
        $dmarcTxt = self::getTxtRecords("_dmarc.{$domain}");
        $dmarcRecord = null;
        foreach ($dmarcTxt as $txt) {
            if (str_starts_with(strtoupper($txt), 'V=DMARC1')) {
                $dmarcRecord = $txt;
                break;
            }
        }

        $hasDmarc = $dmarcRecord !== null;
        $dmarcPolicy = null;
        $dmarcEnforced = false;

        if ($dmarcRecord !== null) {
            if (preg_match('/;\s*p=([a-zA-Z]+)/i', $dmarcRecord, $pMatch)) {
                $dmarcPolicy = strtolower($pMatch[1]);
                $dmarcEnforced = in_array($dmarcPolicy, ['reject', 'quarantine'], true);
            }
        }

        if (! $hasDmarc) {
            $recommendations[] = "Missing DMARC record at _dmarc.{$domain}. Google & Yahoo bulk sender requirements mandate DMARC.";
        } elseif (! $dmarcEnforced) {
            $recommendations[] = "DMARC policy on {$domain} is set to p=none (monitoring only). Upgrade to p=quarantine or p=reject to enforce deliverability.";
        }

        // 3. Check BIMI at default._bimi.domain
        $bimiTxt = self::getTxtRecords("default._bimi.{$domain}");
        $bimiRecord = null;
        foreach ($bimiTxt as $txt) {
            if (str_starts_with(strtoupper($txt), 'V=BIMI1')) {
                $bimiRecord = $txt;
                break;
            }
        }

        $hasBimi = $bimiRecord !== null;
        $bimiLogo = null;
        $bimiCert = null;

        if ($bimiRecord !== null) {
            if (preg_match('/;\s*l=([^;]+)/i', $bimiRecord, $lMatch)) {
                $bimiLogo = trim($lMatch[1]);
            }
            if (preg_match('/;\s*a=([^;]+)/i', $bimiRecord, $aMatch)) {
                $bimiCert = trim($aMatch[1]);
            }
        }

        $bimiReady = $hasBimi && $dmarcEnforced && ! empty($bimiLogo);
        if (! $hasBimi && $dmarcEnforced) {
            $recommendations[] = "DMARC is enforced on {$domain}, but no BIMI record found at default._bimi.{$domain}. Add BIMI to display your verified logo in inboxes.";
        }

        // Overall status determination
        $overallStatus = match (true) {
            $hasSpf && $dmarcEnforced && $bimiReady => 'optimal',
            $hasSpf && $dmarcEnforced => 'good',
            $hasSpf && $hasDmarc => 'warning',
            default => 'critical',
        };

        return [
            'domain' => $domain,
            'overall_status' => $overallStatus,
            'spf' => [
                'found' => $hasSpf,
                'record' => $spfRecord,
                'valid' => $hasSpf,
            ],
            'dmarc' => [
                'found' => $hasDmarc,
                'record' => $dmarcRecord,
                'policy' => $dmarcPolicy,
                'enforced' => $dmarcEnforced,
            ],
            'bimi' => [
                'found' => $hasBimi,
                'record' => $bimiRecord,
                'logo_url' => $bimiLogo,
                'cert_url' => $bimiCert,
                'ready' => $bimiReady,
            ],
            'recommendations' => $recommendations,
        ];
    }
}
