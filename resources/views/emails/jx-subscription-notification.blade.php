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
        <span style="font-weight: bold;" class="header">Jx Subscription</span>
        <span class="header"> | Subscriptions Sync</span></div>
    <div class="header" style="font-size: 10pt;">Sync Report</div>
</div>
<br />
<br />
<table class="border-table">
    <thead>
    <tr>
        <th>Message</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                {{$exception}}
            </td>
        </tr>
    </tbody>
</table>