<x-mail::message>
@if ($intro)
{!! nl2br(e($intro)) !!}
@endif

@if ($post)
## {{ $post->title }}

@if ($post->excerpt)
{{ $post->excerpt }}
@endif

<x-mail::button :url="$postUrl">
Eintrag lesen
</x-mail::button>
@endif

Liebe Grüße
die Reisemäuse

<x-slot:subcopy>
Du bekommst diese E-Mail, weil du dich für unseren Reise-Newsletter angemeldet hast.
[Hier kannst du dich abmelden]({{ $unsubscribeUrl }}).
</x-slot:subcopy>
</x-mail::message>
