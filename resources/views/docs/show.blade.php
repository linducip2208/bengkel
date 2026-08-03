@extends('docs.layout')

@php
    $slugs = array_column($sections, 'slug');
    $idx = array_search($section['slug'], $slugs);
    $prev = $idx > 0 ? $sections[$idx - 1] : null;
    $next = $idx < count($sections) - 1 ? $sections[$idx + 1] : null;
@endphp

@section('title', $section['title'])
@section('description', $section['lead'])
@section('crumb', $section['title'])

@section('content')
    <h1>{{ $section['title'] }}</h1>
    <p class="lead">{{ $section['lead'] }}</p>

    <section>
        @foreach($section['body'] as $block)
            @switch($block['type'])

                @case('p')
                    <p>{!! $block['text'] !!}</p>
                    @break

                @case('h')
                    <h3>{{ $block['text'] }}</h3>
                    @break

                @case('ol')
                    <ol>
                        @foreach($block['items'] as $item)
                            <li>{!! $item !!}</li>
                        @endforeach
                    </ol>
                    @break

                @case('ul')
                    <ul>
                        @foreach($block['items'] as $item)
                            <li>{!! $item !!}</li>
                        @endforeach
                    </ul>
                    @break

                @case('table')
                    <table class="docs-table">
                        @foreach($block['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{!! $cell !!}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                    @break

                @case('tip')
                    <div class="docs-callout tip">{!! $block['text'] !!}</div>
                    @break

                @case('note')
                    <div class="docs-callout note">{!! $block['text'] !!}</div>
                    @break

                @case('warn')
                    <div class="docs-callout warn">{!! $block['text'] !!}</div>
                    @break

                @case('screenshot')
                    <div class="docs-screenshot">
                        <div class="browser-chrome">
                            <span class="dot r"></span>
                            <span class="dot y"></span>
                            <span class="dot g"></span>
                            <span class="url">{{ $block['url'] ?? 'bengkelpaten.id' . ($block['path'] ?? '') }}</span>
                        </div>
                        <img src="{{ asset('marketing/screens/' . $block['file']) }}"
                             alt="{{ $block['alt'] ?? $block['label'] }}"
                             loading="lazy"
                             width="1440" height="900">
                    </div>
                    @if(!empty($block['caption']))
                        <p class="docs-screenshot-caption">{{ $block['caption'] }}</p>
                    @endif
                    @break

                @case('img')
                    <div class="docs-screenshot plain">
                        <img src="{{ asset('marketing/screens/' . $block['file']) }}"
                             alt="{{ $block['alt'] ?? '' }}"
                             loading="lazy"
                             width="1440" height="900">
                    </div>
                    @if(!empty($block['caption']))
                        <p class="docs-screenshot-caption">{{ $block['caption'] }}</p>
                    @endif
                    @break

                @case('faq')
                    @foreach($block['items'] as $faq)
                        <div class="docs-faq-item">
                            <strong>{{ $faq['q'] }}</strong>
                            <p>{!! $faq['a'] !!}</p>
                        </div>
                    @endforeach
                    @break

            @endswitch
        @endforeach
    </section>

    <nav class="docs-pager">
        @if($prev)
            <a href="{{ route('docs.show', $prev['slug']) }}">
                <div class="label"><i class="fas fa-arrow-left me-1"></i>Sebelumnya</div>
                <div class="title">{{ $prev['title'] }}</div>
            </a>
        @else
            <a href="{{ route('docs.index') }}">
                <div class="label"><i class="fas fa-arrow-left me-1"></i>Sebelumnya</div>
                <div class="title">Beranda Docs</div>
            </a>
        @endif

        @if($next)
            <a href="{{ route('docs.show', $next['slug']) }}" class="next">
                <div class="label">Selanjutnya <i class="fas fa-arrow-right ms-1"></i></div>
                <div class="title">{{ $next['title'] }}</div>
            </a>
        @endif
    </nav>

    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endsection
