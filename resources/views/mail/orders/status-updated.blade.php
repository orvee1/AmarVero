<x-mail::message>
# Order status updated

Hi {{ $order->customer_name }},

Your order {{ $order->order_number }} moved from {{ $previousStatusLabel }} to {{ $statusLabel }}.

<x-mail::panel>
Order number: {{ $order->order_number }}

Current status: {{ $statusLabel }}

Payment: {{ $paymentStatusLabel }}

Shipping: {{ $order->shippingMethod?->name ?? __('To be confirmed') }}

Total: {{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}
</x-mail::panel>

<x-mail::table>
| Item | Qty | Total |
| :--- | :-: | ---: |
@foreach ($order->items as $item)
| {{ $item->product_name }}@if ($item->variant_name) <br><small>{{ $item->variant_name }}</small>@endif | {{ $item->quantity }} | {{ $order->currency_code }} {{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
