{{-- mPDF letterhead for Dark Pro: black band with an accent edge. Expects optional $doctor and $heading. --}}
@php use App\Support\Branding; @endphp
<table width="100%" style="border-collapse: collapse; background: #0a0a0a; color: #fff">
    <tr>
        <td width="6" style="background: {{ Branding::primaryColor() ?? '#10b981' }}"></td>
        <td style="padding: 8px 10px">
            @if (settings('print_show_logo') && Branding::logoDataUri())
                <img src="{{ Branding::logoDataUri() }}" height="34" style="background: #fff">
            @endif
            <span style="font-size: 13pt; font-weight: bold">{{ Branding::name() }}</span><br>
            <span style="font-size: 7.5pt; color: #a3a3a3">{{ Branding::tagline() }}</span>
        </td>
        <td style="padding: 8px 10px; text-align: {{ is_rtl() ? 'left' : 'right' }}">
            @isset($doctor)
                <b>{{ $doctor->displayName() }}</b><br>
                <span style="font-size: 7.5pt; color: #d4d4d4">{{ $doctor->doctorProfile?->title() }}</span>
            @endisset
            @isset($heading)
                <span style="font-size: 7pt; color: #a3a3a3; letter-spacing: {{ is_rtl() ? 0 : 1 }}px">{{ $heading['label'] }}</span><br>
                <span style="font-family: dejavusansmono; font-size: 13pt; font-weight: bold">{{ $heading['value'] }}</span>
            @endisset
        </td>
    </tr>
</table>
