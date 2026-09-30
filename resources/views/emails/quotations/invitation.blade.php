<p>Hello {{ $recipient->contact->first_name }},</p>

<p>
    You have received quotation
    <strong>{{ $quotation->quotation_number }}</strong>
    from Oneyard Outfitters.
</p>

<p>
    Please review the quotation using the secure link below.
</p>

<p>
    <a href="{{ $url }}">
        Review Quotation
    </a>
</p>

<p>
    Quotation total:
    <strong>₦{{ number_format((float) $quotation->total, 2) }}</strong>
</p>

<p>
    Regards,<br>
    Oneyard Outfitters
</p>
