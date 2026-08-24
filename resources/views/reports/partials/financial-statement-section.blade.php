{{-- One captioned block of a statement: the maroon band, its statement lines with
     their underlying accounts indented beneath, and the block's total. Expects
     $heading, $groups, $total and the $money formatter from the calling view. --}}
<tr class="section-row">
    <td colspan="2"><span class="section-heading">{{ $heading }}</span></td>
</tr>

@forelse ($groups as $group)
    <tr class="line-row">
        <td>{{ $group['statement_line'] }}</td>
        <td class="text-right amount">{{ $money($group['amount']) }}</td>
    </tr>
    @foreach ($group['accounts'] as $account)
        <tr class="detail-row">
            <td>{{ $account['name'] }}</td>
            <td class="text-right amount">{{ $money($account['amount']) }}</td>
        </tr>
    @endforeach
@empty
    <tr class="empty-row">
        <td colspan="2">{{ $emptyLabel ?? 'None recorded.' }}</td>
    </tr>
@endforelse

<tr class="total-row">
    <td>Total {{ $heading }}</td>
    <td class="text-right amount">{{ $money($total) }}</td>
</tr>
