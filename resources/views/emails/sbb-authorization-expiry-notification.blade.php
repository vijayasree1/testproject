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
        <span class="header"> | SBB Authorization Expiry</span></div>
    <div class="header" style="font-size: 10pt;">SBB Authorization Expiry Report</div>
</div>
<br />
<br />

@if (!empty($expiryDetails))
    <div>SBB Authorization Expiry Details:</div>
    <br />
    <table class="border-table">
        <thead>
        <tr>
            <th>Location Idx</th>
			<th>Location </th>
			<th>Country</th>
			<th>Start Date</th>
			<th>End Date</th>
			<th>Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($expiryDetails as $row)
            <tr>
                <td>
                    {{$row['Location_Idx']}}
                </td>
				<td>
                    {{$row['Location']}}
                </td>
				<td>
                    {{$row['Country']}}
                </td>
				<td>
                    {{$row['Start_Date']}}
                </td>
				<td>
                    {{$row['End_Date']}}
                </td>
				<td>
                    {{$row['Location_Status']}}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
