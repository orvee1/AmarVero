<x-mail::message>
# Order received

Hi {{ $order->customer_name }},

We received your Amarvero order and saved it for our operations team.

<x-mail::panel>
Order number: {{ $order->order_number }}

Status: {{ $statusLabel }}

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

We will email you again when the order status changes.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
