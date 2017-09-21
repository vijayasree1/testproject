<style>
body, div {
	font-family: 'Arial';
	font-size: 10pt;
}

.header {
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
		<span style="font-weight: bold;" class="header">DBMan</span> <span
			class="header"> | Traffic Shaping Notification</span>
	</div>
	<div class="header" style="font-size: 10pt;">Traffic Shaping Notification
		Report</div>
</div>
<br />
<br />

@if (!empty($expiryDetails))


<div>Traffic Shaping limit reached to 80% for your JX system.Below are the details.</div>

<br />


<table class="border-table">
	<thead>
		<tr>
			<th>TPK_DID</th>
			<th>Video Streaming (kbps)</th>
			<th>Hours opted</th>
			<th>Hours remaining</th>
		</tr>
	</thead>
	<tbody>
		@foreach ($expiryDetails as $row)
		<tr>
			<td>{{$row['Uid']}}</td>
			<td>{{$row['Streaming_Rate']}}</td>
			<td>{{$row['Threshold_Value']}}</td>
			<td>{{(($row['Threshold_Value']*20)/100)}}</td>
		</tr>
		@endforeach
	</tbody>
</table>
@endif
