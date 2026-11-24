<?php

declare(strict_types=1);

return [

    'app' => [
        'name' => 'SiteCheck',
        'tagline' => 'Website health check',
        'description' => 'A bilingual website health checker for DNS, TLS, email, HTTP security and SEO.',
    ],

    'nav' => [
        'home' => 'Home',
        'history' => 'History',
        'new_scan' => 'New scan',
        'about' => 'About',
        'help' => 'Help',
        'privacy' => 'Privacy',
        'terms' => 'Terms',
        'changelog' => 'Changelog',
        'language' => 'Language',
        'arabic' => 'العربية',
        'english' => 'English',
        'skip' => 'Skip to content',
        'menu' => 'Menu',
        'close' => 'Close',
    ],

    'meta' => [
        'description' => 'SiteCheck scans DNS, TLS certificates, email authentication, HTTP security headers and SEO basics, then explains every issue in Arabic and English.',
    ],

    'home' => [
        'kicker' => 'Site check',
        'title' => 'Check a website',
        'subtitle' => 'Enter a domain or URL. SiteCheck inspects five areas and returns a graded report that explains the cause and the fix for every problem.',
        'form_label' => 'New scan',
        'what_title' => 'What the report covers',
        'what_body' => 'Five sections, each scored 0–100 and graded A–F.',
        'safety_note' => 'Public HTTP/HTTPS targets only. Every redirect is re-checked and the connection is pinned to a validated address.',
        'recent_title' => 'Recent scans',
        'recent_note' => 'Stored privately in this browser session.',
        'recent_note_demo' => 'Latest scans, including sample demo entries.',
        'view_history' => 'Full history',
    ],

    'form' => [
        'target_label' => 'Domain or URL',
        'target_help' => 'We default to HTTPS. Paths are allowed; credentials, IP ranges and internal hosts are rejected.',
        'target_placeholder' => 'example.com',
        'dkim_label' => 'DKIM selectors',
        'dkim_help' => 'Optional, comma-separated. Common selectors are checked when left empty.',
        'dkim_placeholder' => 'google, selector1',
        'submit' => 'Start scan',
        'submitting' => 'Scanning…',
        'advanced' => 'Advanced options',
        'optional' => 'optional',
        'errors_title' => 'Check the highlighted fields',
        'note' => 'Only publicly routable HTTP/HTTPS targets can be scanned.',
    ],

    'report' => [
        'title' => 'Scan report',
        'for' => 'Report for :host',
        'scanned_at' => 'Scanned',
        'duration' => 'Duration',
        'overall' => 'Overall score',
        'grade' => 'Grade',
        'print' => 'Print',
        'export' => 'Download JSON',
        'rescan' => 'Scan again',
        'delete' => 'Delete',
        'delete_confirm' => 'Delete this scan from your history?',
        'summary' => 'Summary',
        'overall_summary' => 'Weighted across DNS, TLS, email, HTTP security and SEO.',
        'checks_passed' => 'Passed',
        'warnings' => 'Warnings',
        'failures' => 'Failures',
        'sections' => 'Detailed results',
        'details' => 'Evidence',
        'fix' => 'How to fix it',
        'no_issues' => 'No issues found in this section.',
        'section_error' => 'This check could not be completed. The rest of the report is still valid.',
        'score' => 'Score',
        'back_home' => 'New scan',
        'back_history' => 'Back to history',
        'technical' => 'Technical details',
        'show_details' => 'Show details',
        'hide_details' => 'Hide details',
        'value' => 'Value',
        'type' => 'Type',
        'priority' => 'Priority',
        'tag' => 'Tag',
        'flags' => 'Flags',
        'text' => 'Text',
        'serial' => 'Serial',
        'yes' => 'Yes',
        'no' => 'No',
        'priorities' => 'Priority fixes',
        'none' => 'None',
        'not_available' => 'Not available',
        'target' => 'Target',
        'final_url' => 'Final URL',
        'response_time' => 'Response time',
        'status' => 'HTTP status',
        'redirect_chain' => 'Redirect chain',
        'hop' => 'Hop',
        'url' => 'URL',
        'dns_title' => 'DNS records',
        'dns_a' => 'A / AAAA',
        'dns_cname' => 'CNAME',
        'dns_ns' => 'NS',
        'dns_mx' => 'MX',
        'dns_txt' => 'TXT',
        'dns_caa' => 'CAA',
        'dns_soa' => 'SOA',
        'certificate_title' => 'Certificate',
        'issuer' => 'Issuer',
        'subject' => 'Subject',
        'valid_from' => 'Valid from',
        'valid_to' => 'Valid to',
        'san_list' => 'Subject alternative names',
        'protocol' => 'Protocol',
        'cipher' => 'Cipher',
        'chain_length' => 'Certificates sent',
        'tls_handshake' => 'Handshake',
        'tls_verified' => 'Verified',
        'tls_error' => 'TLS error',
        'spf_title' => 'SPF',
        'dmarc_title' => 'DMARC',
        'dkim_title' => 'DKIM',
        'policy' => 'Policy',
        'lookups' => 'DNS lookups',
        'selectors_checked' => 'Selectors checked',
        'selector' => 'Selector',
        'found' => 'Found',
        'not_found' => 'Not found',
        'revoked' => 'Revoked',
        'key_size' => 'Estimated key size',
        'headers_title' => 'Security headers',
        'cookie_name' => 'Cookie',
        'secure' => 'Secure',
        'http_only' => 'HttpOnly',
        'same_site' => 'SameSite',
        'page_title' => 'Page title',
        'meta_description' => 'Meta description',
        'canonical' => 'Canonical URL',
        'robots_meta' => 'Robots meta',
        'html_lang' => 'HTML language',
        'viewport' => 'Viewport',
        'h1' => 'H1 headings',
        'open_graph' => 'Open Graph tags',
        'images_without_alt' => 'Images without alt',
        'robots_txt' => 'robots.txt',
        'sitemap' => 'sitemap.xml',
        'redirects' => 'Redirects',
        'server' => 'Server',
        'powered_by' => 'Powered by',
        'truncated' => 'The response body was truncated to the size limit.',
        'headers_seen' => 'Headers observed',
        'body_available' => 'HTML body analysed',
        'no_cookies' => 'No cookies were set.',
        'scanned_by' => 'Report from SiteCheck',
        'sample_note' => 'This is a sample demo report.',
        'json_note' => 'The JSON export contains the full raw report for this scan.',
        'score_of' => ':score out of 100',
        'raw_meta' => 'Scan metadata',
        'pinned_ip' => 'Pinned address',
        'resolved_ips' => 'Resolved addresses',
        'checklist' => 'Checklist',
        'severity_legend' => 'Pass, warning, failure and informational findings are marked with distinct icons and text, never colour alone.',
        'no_details' => 'No additional evidence was collected.',
        'ms' => 'ms',
        'bits' => 'bits',
    ],

    'history' => [
        'title' => 'Scan history',
        'subtitle' => 'Private to this browser session. Newest scans first.',
        'sample_notice' => 'This list includes sample demo data, reset automatically every hour.',
        'sample_chip' => 'Sample',
        'search_label' => 'Search by domain or URL',
        'search_placeholder' => 'example.com',
        'search' => 'Search',
        'clear' => 'Clear',
        'empty_title' => 'No scans yet',
        'empty_body' => 'Run your first scan and it will appear here.',
        'empty_search_title' => 'No matching scans',
        'empty_search_body' => 'Try a different domain or clear the search.',
        'target' => 'Target',
        'score' => 'Score',
        'grade' => 'Grade',
        'date' => 'Date',
        'actions' => 'Actions',
        'view' => 'View',
        'rescan' => 'Rescan',
        'delete' => 'Delete',
        'delete_confirm' => 'Delete this scan?',
        'showing' => 'Showing :first–:last of :total',
        'previous' => 'Previous',
        'next' => 'Next',
        'summary' => ':count scan(s) in this session',
    ],

    'flash' => [
        'scan_done' => 'Scan completed.',
        'scan_deleted' => 'Scan deleted from your history.',
        'locale_changed' => 'Language updated.',
    ],

    'severity' => [
        'pass' => 'Pass',
        'warn' => 'Warning',
        'fail' => 'Failure',
        'info' => 'Info',
    ],

    'grades' => [
        'A' => [
            'label' => 'Excellent',
            'summary' => 'The core configuration is solid. Keep certificates and policies up to date.',
        ],
        'B' => [
            'label' => 'Good',
            'summary' => 'Strong overall, with a few improvements worth scheduling.',
        ],
        'C' => [
            'label' => 'Fair',
            'summary' => 'Several important protections are missing or weak.',
        ],
        'D' => [
            'label' => 'Weak',
            'summary' => 'Significant gaps in security or discoverability need attention.',
        ],
        'F' => [
            'label' => 'Critical',
            'summary' => 'Key protections are missing. Treat the failures below as priorities.',
        ],
    ],

    'tones' => [
        'excellent' => 'Excellent',
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
    ],

    'sections' => [
        'dns' => [
            'title' => 'DNS',
            'description' => 'Records that make the domain reachable and verifiable.',
        ],
        'ssl' => [
            'title' => 'SSL / TLS',
            'description' => 'Certificate trust, hostname coverage, validity and transport encryption.',
        ],
        'email' => [
            'title' => 'Email security',
            'description' => 'SPF, DMARC, DKIM and MX configuration that protects your domain from spoofing.',
        ],
        'http' => [
            'title' => 'HTTP security',
            'description' => 'Headers, cookies, redirects and response behaviour.',
        ],
        'seo' => [
            'title' => 'SEO basics',
            'description' => 'Titles, metadata, crawlability and mobile readiness.',
        ],
    ],

    'errors' => [
        'title' => 'Something went wrong',
        'back_home' => 'Back to home',
        'retry' => 'Try again',
        'search' => 'Search',
        '404_title' => 'Page not found',
        '404_body' => 'The page or report you requested does not exist, or it belongs to a different browser session.',
        '419_title' => 'Session expired',
        '419_body' => 'Your session expired or the form token was invalid. Reload the page and try again.',
        '429_title' => 'Too many scans',
        '429_body' => 'Please wait a minute before starting another scan. Scans are limited per session and per address.',
        '500_title' => 'Unexpected error',
        '500_body' => 'The request could not be completed. Please try again shortly.',
        'scan_failed' => 'The scan could not be completed. Please try again.',
        'dkim_selector' => 'DKIM selectors may contain letters, digits and hyphens only.',
        'url' => [
            'empty' => 'Enter a domain or URL.',
            'too_long' => 'The URL is too long.',
            'invalid_characters' => 'The URL contains characters that are not allowed.',
            'invalid_url' => 'That does not look like a valid URL.',
            'invalid_scheme' => 'Only http:// and https:// URLs are supported.',
            'credentials' => 'URLs with embedded usernames or passwords are not allowed.',
            'invalid_host' => 'The hostname is not valid.',
            'internal_hostname' => 'Local, internal and single-label hostnames cannot be scanned.',
            'obfuscated_host' => 'IP addresses must use the standard dotted format.',
            'blocked_port' => 'Only ports 80 and 443 can be scanned.',
            'blocked_address' => 'The host resolves to a private, loopback or reserved address, so it cannot be scanned.',
            'unresolvable' => 'The domain could not be resolved. Check the spelling and its DNS records.',
            'redirect_loop' => 'The site redirects in a loop.',
            'too_many_redirects' => 'The site redirected more than five times.',
            'invalid_redirect' => 'The site returned an invalid redirect.',
            'timeout' => 'The site took too long to respond.',
            'transport_error' => 'The connection to the site failed.',
            'no_response' => 'The server did not return an HTTP response.',
            'ip_mismatch' => 'The connection was blocked because it did not reach the validated address.',
            'headers_too_large' => 'The server response headers were too large.',
            'tls_handshake_failed' => 'The TLS handshake failed.',
            'generic' => 'This URL cannot be scanned.',
        ],
    ],

    'footer' => [
        'note' => 'Runs on your own server. No trackers, no CDNs, no third-party fonts.',
        'rights' => 'All rights reserved.',
        'credit' => 'Designed & built by Fedaa Mohamed',
        'demo_note' => 'Demo build — sample data',
        'version' => 'v2.0',
    ],

    'pages' => [
        'kicker' => 'Information',
        'cta_scan' => 'Run a scan',
        'about' => [
            'title' => 'About SiteCheck',
            'lead' => 'A website checker that runs on your own server and explains the result in plain language, with no accounts or external services.',
            'blocks' => [
                ['heading' => 'What it does', 'body' => 'SiteCheck inspects the domain from the outside: DNS records, the TLS certificate, email authentication, security headers and SEO basics, then combines them into one report graded A to F.'],
                ['heading' => 'Who it is for', 'body' => 'People running a small website, shop or clinic, and agencies checking client sites before launch or after a change.'],
                ['heading' => 'How it works', 'body' => 'Every scan starts from your server, re-validates each redirect hop and pins the connection to a validated public address. Your data is not sent to an external service.'],
                ['heading' => 'Contact', 'body' => 'Questions and feedback: hello@example.com'],
            ],
        ],
        'privacy' => [
            'title' => 'Privacy policy',
            'lead' => 'We collect as little data as possible to run a scan.',
            'updated' => 'Last updated: November 2026',
            'blocks' => [
                ['heading' => 'Scan history', 'body' => 'Scan history is stored in a local database on the server running the tool and is tied to your browser session. We do not create accounts or sync history to the cloud.'],
                ['heading' => 'What is sent', 'body' => 'Only the domain you ask to scan is sent to DNS resolvers and to the site itself. No credentials or private content are sent.'],
                ['heading' => 'Cookies', 'body' => 'We use a single session cookie to keep your language choice and the ownership token for your scan history. No analytics, ads or trackers.'],
                ['heading' => 'Demo build', 'body' => 'The demo build adds sample scan data to illustrate the interface; it is reset automatically every hour.'],
                ['heading' => 'Contact', 'body' => 'For any privacy request, email hello@example.com.'],
            ],
        ],
        'terms' => [
            'title' => 'Terms of use',
            'lead' => 'By using SiteCheck you agree to the following.',
            'updated' => 'Last updated: November 2026',
            'blocks' => [
                ['heading' => 'Acceptable use', 'body' => 'Scan public targets only. Do not use the tool against internal networks or systems you are not authorised to test.'],
                ['heading' => 'Results', 'body' => 'The report is a helper, not a guarantee. A site can change after a scan, and some checks may be incomplete for network reasons.'],
                ['heading' => 'Usage limits', 'body' => 'Rate limits protect the service, and excessive automated requests may be refused.'],
                ['heading' => 'Liability', 'body' => 'The tool is provided as is, without warranties. We are not responsible for decisions made from a report.'],
                ['heading' => 'Changes', 'body' => 'We may update these terms; continued use means you accept the updated version.'],
            ],
        ],
        'help' => [
            'title' => 'Help & FAQ',
            'lead' => 'Short answers to the questions we see most.',
            'items' => [
                ['q' => 'How do I start a scan?', 'a' => 'Enter a domain or URL on the home page and press “Start scan”. A scan usually finishes in seconds.'],
                ['q' => 'Why is a URL rejected?', 'a' => 'Only public HTTP/HTTPS targets can be scanned. Local and internal hostnames and private IP addresses are rejected to protect the network.'],
                ['q' => 'What does the grade mean?', 'a' => 'A excellent, B good, C fair, D weak, F critical. The score is weighted, with TLS and HTTP carrying more weight.'],
                ['q' => 'Why is DKIM not found?', 'a' => 'DKIM selectors are arbitrary. We check common selectors or the ones you enter, and your mail provider may use a different selector.'],
                ['q' => 'Where is history stored?', 'a' => 'In a local database on the server, scoped to your browser session. No accounts and no cloud sync.'],
                ['q' => 'How do I print or export a report?', 'a' => 'From the report page choose “Print”, or download JSON to keep the raw data.'],
            ],
        ],
        'changelog' => [
            'title' => 'Changelog',
            'lead' => 'Notable changes in SiteCheck.',
            'releases' => [
                ['version' => '2.0', 'date' => 'November 2026', 'items' => ['New clinical report identity', 'Strict CSP with no inline scripts', 'About, policy and help pages', 'Realistic sample history for the demo']],
                ['version' => '1.0', 'date' => 'November 2026', 'items' => ['DNS, TLS, email, HTTP and SEO checks', 'Bilingual report, print and JSON export', 'SSRF protection and scan rate limiting']],
            ],
        ],
    ],

    'findings' => [

        'dns_a' => [
            'title' => 'Address records (A / AAAA)',
            'detail' => [
                'pass' => 'The hostname resolves to public IP addresses.',
                'fail' => 'The hostname does not resolve to any address.',
            ],
            'fix' => 'Publish an A record (and preferably an AAAA record) pointing to your web server.',
        ],
        'dns_aaaa' => [
            'title' => 'IPv6 support',
            'detail' => 'The hostname publishes IPv6 addresses.',
        ],
        'dns_aaaa_missing' => [
            'title' => 'IPv6 support',
            'detail' => 'No IPv6 address is published. This is optional but improves future readiness.',
        ],
        'dns_cname' => [
            'title' => 'Canonical name (CNAME)',
            'detail' => 'The hostname is an alias of :target.',
        ],
        'dns_ns' => [
            'title' => 'Authoritative nameservers',
            'detail' => [
                'pass' => 'The domain uses :count authoritative nameservers.',
                'warn' => 'Only one nameserver is configured, which is a single point of failure.',
                'fail' => 'No authoritative nameserver was found for this domain.',
            ],
            'fix' => 'Use at least two nameservers on separate networks, provided by a reputable DNS host.',
        ],
        'dns_mx' => [
            'title' => 'Mail exchangers (MX)',
            'detail' => 'MX records found: :count. Full validation is part of the email section.',
        ],
        'dns_txt' => [
            'title' => 'TXT records',
            'detail' => 'TXT records found: :count.',
        ],
        'dns_caa' => [
            'title' => 'Certificate Authority Authorization (CAA)',
            'detail' => [
                'pass' => 'CAA records are published, restricting which CAs may issue certificates.',
                'warn' => 'No CAA record is published, so any public CA may issue a certificate for this domain.',
            ],
            'fix' => 'Publish a CAA record that lists the CAs you actually use (for example letsencrypt.org).',
        ],
        'dns_soa' => [
            'title' => 'Start of Authority (SOA)',
            'detail' => [
                'pass' => 'An SOA record is published.',
                'warn' => 'No SOA record was found for this name.',
            ],
            'fix' => 'Check the DNS zone configuration with your DNS provider.',
        ],
        'dns_resolver_status' => [
            'title' => 'Resolver status',
            'detail' => 'Resolver status: :status.',
        ],
        'dns_caa_unavailable' => [
            'title' => 'Certificate Authority Authorization (CAA)',
            'detail' => 'CAA lookups are unavailable on this server, so this check was skipped.',
        ],

        'ssl_https' => [
            'title' => 'HTTPS availability',
            'detail' => [
                'pass' => 'Port 443 accepts TLS connections.',
                'fail' => 'The scanned URL uses plain HTTP, so TLS was not evaluated. HTTPS is strongly recommended.',
            ],
            'fix' => 'Serve the site over HTTPS and redirect every HTTP request to it.',
        ],
        'ssl_unreachable' => [
            'title' => 'TLS connection',
            'detail' => 'No TLS connection could be established on port 443.',
            'fix' => 'Check that the web server listens on port 443 and has a certificate configured.',
        ],
        'ssl_verified' => [
            'title' => 'Certificate trust',
            'detail' => [
                'pass' => 'The certificate chain is valid and trusted by the system store.',
                'fail' => 'The certificate chain could not be verified.',
            ],
            'fix' => 'Install the full chain (leaf plus intermediates) issued by a publicly trusted CA.',
        ],
        'ssl_certificate' => [
            'title' => 'Peer certificate',
            'detail' => 'The server did not return a certificate.',
            'fix' => 'Configure a valid TLS certificate for this hostname.',
        ],
        'ssl_hostname' => [
            'title' => 'Hostname match',
            'detail' => [
                'pass' => 'The certificate covers :host.',
                'fail' => 'The certificate does not cover :host.',
            ],
            'fix' => 'Issue a certificate whose subject alternative names include the exact hostname you serve.',
        ],
        'ssl_expiry' => [
            'title' => 'Certificate validity',
            'detail' => [
                'pass' => 'The certificate expires in :days days.',
                'warn' => 'The certificate expires in :days days. Renew it soon.',
            ],
            'fix' => 'Renew the certificate and automate renewal with ACME where possible.',
        ],
        'ssl_expiry_soon' => [
            'title' => 'Certificate expiring',
            'detail' => 'The certificate expires in :days days, which is critically soon.',
            'fix' => 'Renew the certificate now and verify that automatic renewal works.',
        ],
        'ssl_expired' => [
            'title' => 'Certificate expired',
            'detail' => 'The certificate expired :days days ago.',
            'fix' => 'Install a renewed certificate immediately.',
        ],
        'ssl_issuer' => [
            'title' => 'Issuer',
            'detail' => [
                'pass' => 'The certificate was issued by :issuer.',
                'warn' => 'The issuing certificate authority could not be fully trusted (:issuer).',
            ],
            'fix' => 'Use a certificate from a publicly trusted certificate authority.',
        ],
        'ssl_chain' => [
            'title' => 'Certificate chain',
            'detail' => [
                'pass' => 'The server sent :count certificates, including the intermediate chain.',
                'warn' => 'The server sent :count certificate(s). Intermediates may be missing.',
            ],
            'fix' => 'Serve the full certificate chain, including intermediate certificates.',
        ],
        'ssl_protocol' => [
            'title' => 'TLS protocol',
            'detail' => [
                'pass' => 'The connection negotiated :protocol.',
                'warn' => 'The negotiated protocol is :protocol, which is outdated.',
                'fail' => 'The connection negotiated :protocol, which is insecure.',
            ],
            'fix' => 'Disable TLS 1.0 and 1.1 and allow only TLS 1.2 and 1.3.',
        ],
        'ssl_cipher' => [
            'title' => 'Cipher suite',
            'detail' => [
                'pass' => 'Negotiated :cipher (:bits bits).',
                'warn' => 'Negotiated a weak cipher: :cipher (:bits bits).',
            ],
            'fix' => 'Prefer AEAD ciphers such as AES-GCM or ChaCha20-Poly1305 and disable legacy ciphers.',
        ],
        'ssl_san' => [
            'title' => 'Alternative names',
            'detail' => 'The certificate lists :count DNS name(s).',
        ],

        'email_mx' => [
            'title' => 'MX records',
            'detail' => [
                'pass' => 'Mail exchangers are configured (:count).',
                'fail' => 'No MX record was found, so the domain cannot receive email.',
            ],
            'fix' => 'Publish MX records pointing to your mail provider.',
        ],
        'email_mx_null' => [
            'title' => 'Null MX',
            'detail' => 'The domain publishes a null MX record, explicitly declaring that it does not receive email.',
        ],
        'email_mail_disabled' => [
            'title' => 'Email disabled',
            'detail' => 'SPF, DMARC and DKIM are informational only for this domain because it declares no mail service.',
        ],
        'email_mx_redundancy' => [
            'title' => 'Mail redundancy',
            'detail' => [
                'pass' => ':count mail exchangers provide redundancy.',
                'warn' => 'Only one mail exchanger is configured.',
            ],
            'fix' => 'Configure a second MX host from your mail provider for redundancy.',
        ],
        'email_spf_present' => [
            'title' => 'SPF record',
            'detail' => [
                'pass' => 'An SPF record is published.',
                'fail' => 'No SPF record is published, so anyone can forge mail from this domain.',
            ],
            'fix' => 'Publish a TXT record beginning with v=spf1 that lists the hosts allowed to send mail.',
        ],
        'email_spf_multiple' => [
            'title' => 'Multiple SPF records',
            'detail' => 'Found :count SPF records. Receivers treat multiple records as a permanent error.',
            'fix' => 'Merge all mechanisms into a single SPF record.',
        ],
        'email_spf_all' => [
            'title' => 'SPF all mechanism',
            'detail' => 'The SPF record ends with -all, rejecting unauthorised senders.',
        ],
        'email_spf_all_plus' => [
            'title' => 'SPF allows everyone',
            'detail' => 'The SPF record ends with +all, which authorises every sender and defeats SPF.',
            'fix' => 'Replace +all with -all after listing every legitimate sender.',
        ],
        'email_spf_all_softfail' => [
            'title' => 'SPF soft fail',
            'detail' => 'The SPF record ends with ~all, so unauthorised mail is only marked, not rejected.',
            'fix' => 'Move to -all once you are confident the record covers all legitimate senders.',
        ],
        'email_spf_all_neutral' => [
            'title' => 'SPF neutral',
            'detail' => 'The SPF record ends with ?all, which expresses no policy at all.',
            'fix' => 'Use -all (or at least ~all) to state a clear policy.',
        ],
        'email_spf_all_missing' => [
            'title' => 'SPF all mechanism',
            'detail' => 'The SPF record has no all mechanism, leaving the default neutral policy.',
            'fix' => 'End the SPF record with -all.',
        ],
        'email_spf_lookups' => [
            'title' => 'SPF lookup budget',
            'detail' => [
                'pass' => 'The record uses :count of the 10 allowed DNS lookups.',
                'fail' => 'The record uses :count DNS lookups, above the limit of 10, and will fail with a permanent error.',
            ],
            'fix' => 'Reduce include, a, mx, ptr and exists mechanisms, or flatten includes.',
        ],
        'email_spf_syntax' => [
            'title' => 'SPF syntax',
            'detail' => [
                'pass' => 'No deprecated or unknown mechanisms were found.',
                'warn' => 'The record contains a deprecated or unknown mechanism (for example ptr).',
            ],
            'fix' => 'Remove ptr and unknown mechanisms, then validate the record with an SPF checker.',
        ],
        'email_dmarc_present' => [
            'title' => 'DMARC record',
            'detail' => [
                'pass' => 'A DMARC record is published at _dmarc.',
                'fail' => 'No DMARC record is published, so receivers cannot apply your policy.',
            ],
            'fix' => 'Publish a TXT record at _dmarc with v=DMARC1 and a starting policy.',
        ],
        'email_dmarc_policy' => [
            'title' => 'DMARC policy',
            'detail' => 'The DMARC policy is :policy.',
        ],
        'email_dmarc_policy_none' => [
            'title' => 'DMARC policy is monitoring only',
            'detail' => 'The DMARC policy is none, so forged mail is reported but still delivered.',
            'fix' => 'After reviewing reports, move to quarantine and then reject.',
        ],
        'email_dmarc_policy_missing' => [
            'title' => 'DMARC policy missing',
            'detail' => 'The DMARC record does not contain a valid p= policy.',
            'fix' => 'Add p=none, p=quarantine or p=reject to the DMARC record.',
        ],
        'email_dmarc_rua' => [
            'title' => 'DMARC reporting',
            'detail' => [
                'pass' => 'Aggregate reports are configured (:count address(es)).',
                'warn' => 'No rua= reporting address is configured, so you will not see authentication failures.',
            ],
            'fix' => 'Add rua=mailto:dmarc@yourdomain to receive aggregate reports.',
        ],
        'email_dmarc_pct' => [
            'title' => 'DMARC coverage',
            'detail' => 'The policy applies to :pct% of messages.',
            'fix' => 'Raise pct to 100 once you are confident in the policy.',
        ],
        'email_dkim' => [
            'title' => 'DKIM keys',
            'detail' => [
                'pass' => 'A usable DKIM key was found for :working of :total selector(s).',
                'warn' => 'No usable DKIM key was found for the :total selector(s) checked. Your provider may use a different selector.',
            ],
            'fix' => 'Publish the selector TXT record from your mail provider, or enter the correct selector above.',
        ],

        'http_reachable' => [
            'title' => 'Reachability',
            'detail' => 'The site could not be reached over HTTP within the configured timeouts.',
            'fix' => 'Check that the server is online, accepting connections and not blocking this server.',
        ],
        'http_status' => [
            'title' => 'HTTP status',
            'detail' => [
                'pass' => 'The server returned HTTP :status.',
                'warn' => 'The server returned an unexpected status (:status).',
                'fail' => 'The server returned HTTP :status.',
            ],
            'fix' => 'Fix the server error or authentication requirement so visitors receive a successful response.',
        ],
        'http_https' => [
            'title' => 'Final response protocol',
            'detail' => [
                'pass' => 'The final response was served over HTTPS.',
                'fail' => 'The final response was served over plain HTTP.',
            ],
            'fix' => 'Redirect all HTTP traffic to HTTPS and update internal links.',
        ],
        'http_upgrade' => [
            'title' => 'HTTP to HTTPS redirect',
            'detail' => [
                'pass' => 'Plain HTTP is not served, or every plain request redirects to HTTPS.',
                'warn' => 'HTTPS is available, but plain HTTP does not redirect to it.',
                'fail' => 'HTTPS is not available for this host.',
            ],
            'fix' => 'Redirect port 80 to the HTTPS origin with a permanent 301 or 308 redirect.',
        ],
        'http_hsts' => [
            'title' => 'HSTS',
            'detail' => [
                'pass' => 'Strict-Transport-Security is enabled for :days days.',
                'warn' => 'HSTS is configured, but its max-age is shorter than the recommended 180 days.',
                'fail' => 'No Strict-Transport-Security header is sent.',
            ],
            'fix' => 'Send Strict-Transport-Security with max-age of at least 15552000 and, when ready, includeSubDomains.',
        ],
        'http_csp' => [
            'title' => 'Content-Security-Policy',
            'detail' => [
                'pass' => 'A CSP is present without unsafe-inline or unsafe-eval.',
                'warn' => 'A CSP is present but allows unsafe-inline and/or unsafe-eval.',
                'fail' => 'No Content-Security-Policy header is sent.',
            ],
            'fix' => 'Define a restrictive CSP and remove unsafe-inline and unsafe-eval from script sources.',
        ],
        'http_xcto' => [
            'title' => 'X-Content-Type-Options',
            'detail' => [
                'pass' => 'The nosniff directive is enabled.',
                'fail' => 'The nosniff directive is missing.',
            ],
            'fix' => 'Send X-Content-Type-Options: nosniff on every response.',
        ],
        'http_frame' => [
            'title' => 'Clickjacking protection',
            'detail' => [
                'pass' => 'Framing is restricted with X-Frame-Options or CSP frame-ancestors.',
                'warn' => 'An X-Frame-Options header is present but uses a discouraged or invalid value.',
                'fail' => 'No framing protection is configured.',
            ],
            'fix' => 'Send X-Frame-Options: DENY (or SAMEORIGIN) and a CSP frame-ancestors directive.',
        ],
        'http_referrer' => [
            'title' => 'Referrer-Policy',
            'detail' => [
                'pass' => 'A privacy-preserving referrer policy is set.',
                'warn' => 'The referrer policy (:policy) leaks more information than necessary.',
                'fail' => 'No Referrer-Policy header is sent.',
            ],
            'fix' => 'Send Referrer-Policy: strict-origin-when-cross-origin (or stricter).',
        ],
        'http_permissions' => [
            'title' => 'Permissions-Policy',
            'detail' => [
                'pass' => 'A Permissions-Policy header restricts browser features.',
                'warn' => 'No Permissions-Policy header is sent.',
            ],
            'fix' => 'Disable unused features such as camera, microphone and geolocation.',
        ],
        'http_cookies' => [
            'title' => 'Cookie attributes',
            'detail' => [
                'pass' => 'All :count cookie(s) use Secure, HttpOnly and SameSite.',
                'warn' => ':count cookie(s) are set. :no_samesite lack SameSite and :no_httponly lack HttpOnly; Secure is present.',
                'fail' => ':count cookie(s) are set. :insecure lack Secure, :no_httponly lack HttpOnly and :no_samesite lack SameSite.',
                'info' => 'This response did not set any cookies.',
            ],
            'fix' => 'Mark session cookies Secure, HttpOnly and SameSite=Lax or Strict.',
        ],
        'http_server_header' => [
            'title' => 'Server information leakage',
            'detail' => [
                'pass' => 'No software version is disclosed in the server headers.',
                'warn' => 'The response discloses server software details (:server).',
            ],
            'fix' => 'Remove version banners and the X-Powered-By header at the web server or proxy.',
        ],
        'http_response_time' => [
            'title' => 'Response time',
            'detail' => [
                'pass' => 'The final response took :ms ms.',
                'warn' => 'The final response took :ms ms, which is slower than ideal.',
                'fail' => 'The final response took :ms ms, which is slow.',
            ],
            'fix' => 'Add caching, compress responses and investigate slow database or upstream calls.',
        ],
        'http_compression' => [
            'title' => 'Compression',
            'detail' => 'Content-Encoding: :encoding.',
        ],
        'http_redirects' => [
            'title' => 'Redirects',
            'detail' => 'The request followed :count redirect(s).',
        ],

        'seo_unreachable' => [
            'title' => 'On-page analysis',
            'detail' => 'The page could not be fetched, so SEO could not be analysed.',
            'fix' => 'Make the page reachable, then run the scan again.',
        ],
        'seo_content_type' => [
            'title' => 'Response type',
            'detail' => 'The response is not HTML (:type), so on-page SEO signals could not be analysed.',
            'fix' => 'Scan a URL that returns an HTML document.',
        ],
        'seo_title' => [
            'title' => 'Title tag',
            'detail' => [
                'pass' => 'The title has :length characters.',
                'warn' => 'The title has :length characters. Aim for 30–60 characters.',
                'fail' => 'The title is missing or has an unsuitable length (:length characters).',
            ],
            'fix' => 'Write a unique, descriptive title between 30 and 60 characters.',
        ],
        'seo_description' => [
            'title' => 'Meta description',
            'detail' => [
                'pass' => 'The description has :length characters.',
                'warn' => 'The description has :length characters. Aim for 50–160 characters.',
                'fail' => 'The meta description is missing or has an unsuitable length (:length characters).',
            ],
            'fix' => 'Add a meta description of 50–160 characters that summarises the page.',
        ],
        'seo_canonical' => [
            'title' => 'Canonical URL',
            'detail' => [
                'pass' => 'A canonical URL is declared.',
                'warn' => 'No canonical URL is declared.',
            ],
            'fix' => 'Add a link rel="canonical" element with the preferred absolute URL.',
        ],
        'seo_robots_meta' => [
            'title' => 'Indexing policy',
            'detail' => [
                'pass' => 'The page does not ask search engines to skip indexing.',
                'warn' => 'The page asks search engines not to index it (noindex).',
            ],
            'fix' => 'Remove noindex from the robots meta tag or X-Robots-Tag header for public pages.',
        ],
        'seo_h1' => [
            'title' => 'H1 heading',
            'detail' => [
                'pass' => 'The page has exactly one H1 heading.',
                'warn' => 'The page has :count H1 headings. Use a single H1.',
                'fail' => 'The page has no H1 heading.',
            ],
            'fix' => 'Use one descriptive H1 that matches the page intent.',
        ],
        'seo_lang' => [
            'title' => 'Document language',
            'detail' => [
                'pass' => 'The html element declares lang=":lang".',
                'warn' => 'The html element does not declare a lang attribute.',
            ],
            'fix' => 'Set the correct lang attribute on the html element.',
        ],
        'seo_viewport' => [
            'title' => 'Viewport',
            'detail' => [
                'pass' => 'A responsive viewport meta tag is present.',
                'fail' => 'No viewport meta tag is present.',
            ],
            'fix' => 'Add meta name="viewport" content="width=device-width, initial-scale=1".',
        ],
        'seo_og' => [
            'title' => 'Open Graph',
            'detail' => [
                'pass' => 'Open Graph tags are complete (:count tags).',
                'warn' => 'Open Graph tags are incomplete (:count tags found).',
            ],
            'fix' => 'Add og:title, og:description, og:image and og:url.',
        ],
        'seo_images_alt' => [
            'title' => 'Image alternatives',
            'detail' => [
                'pass' => 'All :total images have an alt attribute.',
                'warn' => ':missing of :total images are missing the alt attribute.',
            ],
            'fix' => 'Describe meaningful images and use alt="" for decorative ones.',
        ],
        'seo_robots_txt' => [
            'title' => 'robots.txt',
            'detail' => [
                'pass' => 'robots.txt is available.',
                'warn' => 'robots.txt is missing or did not return a successful response.',
            ],
            'fix' => 'Publish a robots.txt that points to your sitemap and blocks private paths.',
        ],
        'seo_sitemap' => [
            'title' => 'Sitemap',
            'detail' => [
                'pass' => 'A valid XML sitemap was found.',
                'warn' => 'No valid sitemap.xml was found.',
            ],
            'fix' => 'Publish sitemap.xml and reference it from robots.txt.',
        ],

        'check_failed' => [
            'title' => 'Check could not run',
            'detail' => 'This check stopped unexpectedly before it could produce a result.',
            'fix' => 'Try again. If it keeps failing, review the application log for the cause.',
        ],
    ],
];
