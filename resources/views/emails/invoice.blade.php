<x-mail::message>
{{-- Greeting --}}
# {{ $greeting }}

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Invoice Details --}}
<x-mail::panel>
**Donation ID:** {{ $userDonation->human_readable_id }}<br>
**Date:** {{ $donationDate }}<br>
**Payment Method:** {{ $userDonation->payment_method === 'qris' ? 'QRIS' : 'Debit/Credit Card' }}<br>
**Status:** {{ strtoupper($userDonation->payment_status) }}
</x-mail::panel>

<x-mail::table>
| Description          | Amount         |
|----------------------|----------------:|
| Donation Amount      | Rp{{ $totalAmount }} |
| **Total**            | **Rp{{ $totalAmount }}** |
</x-mail::table>


{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}<br>
@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ config('app.name') }}
@endif
</x-mail::message>
