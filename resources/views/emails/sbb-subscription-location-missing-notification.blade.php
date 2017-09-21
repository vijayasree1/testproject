<style>
body, div {
    font-family: 'Arial';
    font-size: 10pt;
}

.header
{
    font-family: Arial;
    font-size: 12pt;
    height: 12pt;
    color: darkgray;
}

table.border-table {
    border-collapse: collapse;
}

table.border-table, .border-table th, .border-table td {
    border: 1px solid black;
}

.border-table th, .border-table td {
    padding: 5px;
}
</style>

<div>
<div>
<span style="font-weight: bold;" class="header">DBMan</span>
<span class="header"> | Subscription Missing - Notification</span></div>
<div class="header" style="font-size: 10pt;">Subscription Missing - Notification Report</div>
</div>
<br />
<br />

@if (!empty($expiryDetails))
    <div>Subscription Missing - Notification Details.</div>
     <br />
     <div> Below locations are not mapped to any of the subscriptions.</div>
    <br />
    <table class="border-table">
    <thead>
    <tr>
    <th>Location Idx</th>
    <th>Customer Idx </th>
    <th>Company</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($expiryDetails as $row)
        <tr>
        <td>
        {{$row['Location_Idx']}}
        </td>
        <td>
        {{$row['Customer_Idx']}}
        </td>
        <td>
        {{$row['Company']}}
        </td>
        </tr>
        @endforeach
        </tbody>
        </table>
        @endif
