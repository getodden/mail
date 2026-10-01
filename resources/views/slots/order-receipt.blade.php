@php
    $orderNumber = (string) $slot->get('order_number', '#10492');
    $orderDate = (string) $slot->get('order_date', date('M j, Y'));
    $currency = (string) $slot->get('currency', '$');
    /** @var list<array{item?: string, qty?: int|string, unit_price?: string, total?: string}> $items */
    $items = $slot->get('items', [
        ['item' => 'Acme Enterprise Workspace (Annual)', 'qty' => 1, 'unit_price' => '2,400.00', 'total' => '2,400.00'],
        ['item' => 'Dedicated IP & Deliverability Warmup', 'qty' => 1, 'unit_price' => '450.00', 'total' => '450.00'],
    ]);
    $subtotal = (string) $slot->get('subtotal', '2,850.00');
    $tax = (string) $slot->get('tax', '0.00');
    $shipping = (string) $slot->get('shipping', '0.00');
    $grandTotal = (string) $slot->get('grand_total', '2,850.00');
    $notes = (string) $slot->get('notes', 'Payment processed via Corporate Visa ending in •••• 4242.');
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0;">
    <tr>
        <td style="padding: 24px; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px;">
            {{-- Receipt Header --}}
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-bottom: 2px solid #E2E8F0; padding-bottom: 16px; margin-bottom: 16px;">
                <tr>
                    <td>
                        <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;">Order Summary</span>
                        <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 18px; font-weight: 800; color: #0F172A; margin-top: 2px;">{{ $orderNumber }}</span>
                    </td>
                    <td align="right" valign="bottom">
                        <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #64748B;">Date: <strong style="color: #0F172A;">{{ $orderDate }}</strong></span>
                    </td>
                </tr>
            </table>

            {{-- Line Items Table --}}
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 16px;">
                <thead>
                    <tr>
                        <th align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; padding-bottom: 8px;">Description</th>
                        <th align="center" width="50" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; padding-bottom: 8px;">Qty</th>
                        <th align="right" width="100" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748B; padding-bottom: 8px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $row)
                        @php
                            $desc = (string) ($row['item'] ?? 'Product');
                            $qty = (string) ($row['qty'] ?? '1');
                            $tot = (string) ($row['total'] ?? '0.00');
                        @endphp
                        <tr>
                            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #1E293B; padding: 8px 0; border-top: 1px solid #E2E8F0;">
                                {{ $desc }}
                            </td>
                            <td align="center" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #64748B; padding: 8px 0; border-top: 1px solid #E2E8F0;">
                                {{ $qty }}
                            </td>
                            <td align="right" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; font-weight: 600; color: #0F172A; padding: 8px 0; border-top: 1px solid #E2E8F0;">
                                {{ $currency }}{{ $tot }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Subtotals & Grand Total --}}
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top: 2px solid #CBD5E1; padding-top: 12px;">
                <tr>
                    <td align="right" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #64748B; padding: 3px 0;">Subtotal:</td>
                    <td align="right" width="110" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #0F172A; padding: 3px 0;">{{ $currency }}{{ $subtotal }}</td>
                </tr>
                @if($tax !== '0.00')
                    <tr>
                        <td align="right" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #64748B; padding: 3px 0;">Tax:</td>
                        <td align="right" width="110" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #0F172A; padding: 3px 0;">{{ $currency }}{{ $tax }}</td>
                    </tr>
                @endif
                <tr>
                    <td align="right" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; font-weight: 800; color: #0F172A; padding: 8px 0;">Total Paid:</td>
                    <td align="right" width="110" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 18px; font-weight: 800; color: #16A34A; padding: 8px 0;">{{ $currency }}{{ $grandTotal }}</td>
                </tr>
            </table>

            @if($notes !== '')
                <p style="margin: 16px 0 0 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; color: #64748B; line-height: 1.4; border-top: 1px solid #E2E8F0; padding-top: 12px;">
                    💳 {{ $notes }}
                </p>
            @endif
        </td>
    </tr>
</table>
