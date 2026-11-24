@php
    $details = $section['details'] ?? [];
    $formatDate = static function ($timestamp): string {
        $timestamp = (int) $timestamp;

        return $timestamp > 0
            ? \Illuminate\Support\Carbon::createFromTimestamp($timestamp)->translatedFormat('j F Y')
            : '—';
    };
@endphp

@switch($section['key'])
    @case('dns')
        @php
            $addresses = array_merge($details['a'] ?? [], $details['aaaa'] ?? []);
            $nameservers = array_merge($details['cname'] ?? [], $details['ns'] ?? []);
        @endphp

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dns_a') }}</h4>
                @if ($addresses === [])
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.type') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details['a'] ?? [] as $record)
                                    <tr><td>A</td><td dir="ltr" class="font-mono text-xs">{{ $record['ip'] ?? '' }}</td></tr>
                                @endforeach
                                @foreach ($details['aaaa'] ?? [] as $record)
                                    <tr><td>AAAA</td><td dir="ltr" class="font-mono text-xs">{{ $record['ip'] ?? '' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dns_ns') }} / {{ __('sitecheck.report.dns_cname') }}</h4>
                @if ($nameservers === [])
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.type') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details['ns'] ?? [] as $record)
                                    <tr><td>NS</td><td dir="ltr" class="font-mono text-xs">{{ $record['target'] ?? '' }}</td></tr>
                                @endforeach
                                @foreach ($details['cname'] ?? [] as $record)
                                    <tr><td>CNAME</td><td dir="ltr" class="font-mono text-xs">{{ $record['target'] ?? '' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dns_mx') }}</h4>
                @if (empty($details['mx']))
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.priority') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details['mx'] as $record)
                                    <tr>
                                        <td>{{ $record['priority'] ?? 0 }}</td>
                                        <td dir="ltr" class="font-mono text-xs">{{ $record['target'] !== '' ? $record['target'] : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dns_caa') }} / {{ __('sitecheck.report.dns_soa') }}</h4>
                @if (empty($details['caa']) && empty($details['soa']))
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="space-y-2">
                        @foreach ($details['caa'] ?? [] as $record)
                            <div class="rounded-lg border border-line bg-white px-3 py-2 text-xs" dir="ltr">
                                <span class="font-bold uppercase">{{ $record['tag'] ?? '' }}</span>
                                <span class="text-soft">flags={{ $record['flags'] ?? 0 }}</span>
                                <span class="break-all font-mono">{{ $record['value'] ?? '' }}</span>
                            </div>
                        @endforeach
                        @foreach ($details['soa'] ?? [] as $record)
                            <div class="rounded-lg border border-line bg-white px-3 py-2 text-xs" dir="ltr">
                                <span class="font-bold">SOA</span>
                                <span class="break-all font-mono">{{ $record['mname'] ?? '' }}</span>
                                <span class="text-soft">{{ $record['rname'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2">
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dns_txt') }}</h4>
                @if (empty($details['txt']))
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($details['txt'] as $record)
                            <li class="break-all rounded-lg border border-line bg-white px-3 py-2 font-mono text-xs text-ink" dir="ltr">{{ $record['text'] ?? '' }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        @break

    @case('ssl')
        @php
            $tls = $details['tls'] ?? null;
            $certificate = is_array($tls) ? ($tls['certificate'] ?? null) : null;
        @endphp

        @if (! is_array($tls))
            <p class="text-sm text-soft">{{ __('sitecheck.report.not_available') }}</p>
        @else
            <div class="grid gap-5 lg:grid-cols-2">
                <dl class="kv">
                    <div>
                        <dt>{{ __('sitecheck.report.tls_verified') }}</dt>
                        <dd>{{ ($tls['verified'] ?? false) ? __('sitecheck.report.yes') : __('sitecheck.report.no') }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('sitecheck.report.protocol') }}</dt>
                        <dd dir="ltr">{{ $tls['protocol'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('sitecheck.report.cipher') }}</dt>
                        <dd dir="ltr">{{ $tls['cipher'] ?? '—' }} @if (! empty($tls['cipher_bits'])) ({{ $tls['cipher_bits'] }} {{ __('sitecheck.report.bits') }}) @endif</dd>
                    </div>
                    <div>
                        <dt>{{ __('sitecheck.report.chain_length') }}</dt>
                        <dd>{{ $tls['chain_count'] ?? 0 }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('sitecheck.report.tls_handshake') }}</dt>
                        <dd>{{ $tls['handshake_ms'] ?? 0 }} {{ __('sitecheck.report.ms') }}</dd>
                    </div>
                    @if (! empty($tls['error']))
                        <div class="sm:col-span-2">
                            <dt>{{ __('sitecheck.report.tls_error') }}</dt>
                            <dd class="text-fail" dir="ltr">{{ $tls['error'] }}</dd>
                        </div>
                    @endif
                </dl>

                <div>
                    <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.certificate_title') }}</h4>
                    @if (! is_array($certificate))
                        <p class="text-sm text-soft">{{ __('sitecheck.report.not_available') }}</p>
                    @else
                        <dl class="kv">
                            <div>
                                <dt>{{ __('sitecheck.report.subject') }}</dt>
                                <dd dir="ltr">{{ $certificate['subject'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('sitecheck.report.issuer') }}</dt>
                                <dd dir="ltr">{{ $certificate['issuer'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('sitecheck.report.valid_from') }}</dt>
                                <dd>{{ $formatDate($certificate['valid_from'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('sitecheck.report.valid_to') }}</dt>
                                <dd>{{ $formatDate($certificate['valid_to'] ?? 0) }}</dd>
                            </div>
                        </dl>

                        @if (! empty($certificate['dns_names']))
                            <h4 class="mb-2 mt-3 text-xs font-bold text-soft">{{ __('sitecheck.report.san_list') }}</h4>
                            <ul class="flex flex-wrap gap-1.5">
                                @foreach ($certificate['dns_names'] as $name)
                                    <li class="rounded-md bg-report-strong px-2 py-1 font-mono text-[0.7rem] text-ink" dir="ltr">{{ $name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>
            </div>
        @endif
        @break

    @case('email')
        @php
            $spf = $details['spf'] ?? [];
            $dmarc = $details['dmarc'] ?? [];
            $dkim = $details['dkim'] ?? [];
        @endphp

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.spf_title') }}</h4>
                @if (empty($spf['records']))
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($spf['records'] as $record)
                            <li class="break-all rounded-lg border border-line bg-white px-3 py-2 font-mono text-xs" dir="ltr">{{ $record }}</li>
                        @endforeach
                    </ul>
                    <dl class="kv mt-3">
                        <div>
                            <dt>{{ __('sitecheck.report.lookups') }}</dt>
                            <dd>{{ $spf['lookups'] ?? 0 }} / 10</dd>
                        </div>
                        <div>
                            <dt>all</dt>
                            <dd dir="ltr">{{ $spf['all'] ?? '—' }}</dd>
                        </div>
                    </dl>
                    @if (! empty($spf['issues']))
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($spf['issues'] as $issue)
                                <span class="chip-neutral">{{ $issue }}</span>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dmarc_title') }}</h4>
                @if (empty($dmarc['present']))
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <dl class="kv">
                        <div>
                            <dt>{{ __('sitecheck.report.policy') }}</dt>
                            <dd dir="ltr">{{ $dmarc['policy'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>pct</dt>
                            <dd>{{ $dmarc['pct'] ?? 100 }}%</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt>rua</dt>
                            <dd>
                                @if (! empty($dmarc['rua']))
                                    <ul class="space-y-1">
                                        @foreach ($dmarc['rua'] as $uri)
                                            <li class="break-all font-mono text-xs" dir="ltr">{{ $uri }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{ __('sitecheck.report.none') }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                    @if (! empty($dmarc['tags']))
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($dmarc['tags'] as $tag => $value)
                                <span class="chip-neutral" dir="ltr">{{ $tag }}={{ $value }}</span>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dkim_title') }}</h4>
                @if (empty($dkim['selectors']))
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.selector') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.status') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.key_size') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dkim['selectors'] as $selector)
                                    <tr>
                                        <td dir="ltr" class="font-mono text-xs">{{ $selector['selector'] }}</td>
                                        <td>
                                            @if ($selector['revoked'])
                                                <span class="chip-warn">{{ __('sitecheck.report.revoked') }}</span>
                                            @elseif ($selector['valid'])
                                                <span class="chip-pass">{{ __('sitecheck.report.found') }}</span>
                                            @else
                                                <span class="chip-neutral">{{ __('sitecheck.report.not_found') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $selector['key_bits'] ? $selector['key_bits'].' '.__('sitecheck.report.bits') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.dns_mx') }}</h4>
                @if (empty($details['mx']))
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.priority') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details['mx'] as $record)
                                    <tr>
                                        <td>{{ $record['priority'] ?? 0 }}</td>
                                        <td dir="ltr" class="font-mono text-xs">{{ $record['target'] !== '' ? $record['target'] : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @break

    @case('http')
        @php
            $headers = $details['headers'] ?? [];
            $cookies = $details['cookies'] ?? [];
            $hops = $details['redirects'] ?? [];
        @endphp

        <div class="grid gap-5 lg:grid-cols-2">
            <dl class="kv">
                <div class="sm:col-span-2">
                    <dt>{{ __('sitecheck.report.final_url') }}</dt>
                    <dd class="break-all font-mono text-xs" dir="ltr">{{ $details['final_url'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.status') }}</dt>
                    <dd>{{ $details['status'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.response_time') }}</dt>
                    <dd>{{ $details['total_time_ms'] ?? 0 }} {{ __('sitecheck.report.ms') }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.server') }}</dt>
                    <dd dir="ltr">{{ $details['server'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.powered_by') }}</dt>
                    <dd dir="ltr">{{ $details['powered_by'] ?? '—' }}</dd>
                </div>
            </dl>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.redirect_chain') }}</h4>
                @if ($hops === [])
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.hop') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.status') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.url') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hops as $hop)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $hop['status'] ?? '' }}</td>
                                        <td dir="ltr" class="break-all font-mono text-[0.7rem]">{{ $hop['url'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2">
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.headers_seen') }}</h4>
                @if ($headers === [])
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.type') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($headers as $name => $values)
                                    @foreach ($values as $value)
                                        <tr>
                                            <td dir="ltr" class="font-mono text-xs font-semibold">{{ $name }}</td>
                                            <td dir="ltr" class="break-all font-mono text-[0.7rem]">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2">
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.cookie_name') }}</h4>
                @if ($cookies === [])
                    <p class="text-sm text-soft">{{ __('sitecheck.report.no_cookies') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.cookie_name') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.secure') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.http_only') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.same_site') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cookies as $cookie)
                                    <tr>
                                        <td dir="ltr" class="font-mono text-xs">{{ $cookie['name'] ?? '' }}</td>
                                        <td>{{ ($cookie['secure'] ?? false) ? __('sitecheck.report.yes') : __('sitecheck.report.no') }}</td>
                                        <td>{{ ($cookie['http_only'] ?? false) ? __('sitecheck.report.yes') : __('sitecheck.report.no') }}</td>
                                        <td dir="ltr">{{ $cookie['same_site'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @break

    @case('seo')
        @php
            $page = $details['page'] ?? [];
            $openGraph = $page['open_graph'] ?? [];
            $robotsTxt = $details['robots_txt'] ?? [];
            $sitemap = $details['sitemap'] ?? [];
        @endphp

        <div class="grid gap-5 lg:grid-cols-2">
            <dl class="kv">
                <div class="sm:col-span-2">
                    <dt>{{ __('sitecheck.report.page_title') }}</dt>
                    <dd>{{ $page['title'] ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt>{{ __('sitecheck.report.meta_description') }}</dt>
                    <dd>{{ $page['description'] ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt>{{ __('sitecheck.report.canonical') }}</dt>
                    <dd class="break-all font-mono text-xs" dir="ltr">{{ $page['canonical'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.robots_meta') }}</dt>
                    <dd dir="ltr">{{ $page['robots_meta'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.html_lang') }}</dt>
                    <dd dir="ltr">{{ $page['lang'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.viewport') }}</dt>
                    <dd dir="ltr" class="break-all text-xs">{{ $page['viewport'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.h1') }}</dt>
                    <dd>{{ $page['h1_count'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt>{{ __('sitecheck.report.images_without_alt') }}</dt>
                    <dd>{{ $page['images_missing_alt'] ?? 0 }} / {{ $page['images_total'] ?? 0 }}</dd>
                </div>
            </dl>

            <div>
                <h4 class="mb-2 text-xs font-bold text-soft">{{ __('sitecheck.report.open_graph') }}</h4>
                @if ($openGraph === [])
                    <p class="text-sm text-soft">{{ __('sitecheck.report.none') }}</p>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('sitecheck.report.tag') }}</th>
                                    <th scope="col">{{ __('sitecheck.report.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($openGraph as $property => $content)
                                    <tr>
                                        <td dir="ltr" class="font-mono text-xs">{{ $property }}</td>
                                        <td dir="ltr" class="break-all text-xs">{{ $content }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <dl class="kv mt-4">
                    <div>
                        <dt>{{ __('sitecheck.report.robots_txt') }}</dt>
                        <dd>
                            @if (! empty($robotsTxt['available']))
                                <span class="chip-pass">{{ __('sitecheck.report.yes') }}</span>
                            @else
                                <span class="chip-warn">{{ __('sitecheck.report.no') }}</span>
                            @endif
                            @if (! empty($robotsTxt['status']))
                                <span class="ms-1 text-xs text-soft">HTTP {{ $robotsTxt['status'] }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>{{ __('sitecheck.report.sitemap') }}</dt>
                        <dd>
                            @if (! empty($sitemap['available']))
                                <span class="chip-pass">{{ __('sitecheck.report.yes') }}</span>
                            @else
                                <span class="chip-warn">{{ __('sitecheck.report.no') }}</span>
                            @endif
                            @if (! empty($sitemap['status']))
                                <span class="ms-1 text-xs text-soft">HTTP {{ $sitemap['status'] }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
        @break

    @default
        <p class="text-sm text-soft">{{ __('sitecheck.report.no_details') }}</p>
@endswitch
