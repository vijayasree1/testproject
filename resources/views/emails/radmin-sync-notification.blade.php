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
        <span class="header"> | Radmin Sync</span></div>
    <div class="header" style="font-size: 10pt;">Sync Report</div>
</div>
<br />
<br />

@if (!empty($imsiList))
    <div>Sync is performed for the following IMSI numbers:</div>
    <br />
    <table class="border-table">
        <thead>
        <tr>
            <th>IMSI</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($imsiList as $imsi)
            <tr>
                <td>
                    {{$imsi}}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
<table>
    <tbody>
        <tr>
            <td>Status of Sync:</td>
            <td>Successful</td>
        </tr>
        <tr>
            <td>
                Sync Start Time:
            </td>
            <td>
                {{$syncStartTime}}
            </td>
        </tr>
        <tr>
            <td>
                Sync End Time:
            </td>
            <td>
                {{$syncEndTime}}
            </td>
        </tr>
        <tr>
            <td>
                Total Number of SIMs in DBMan (only active):
            </td>
            <td>
                {{$dbmanSimCount}}
            </td>
        </tr>
        <tr>
            <td>
                Total Number of SIMs in Radmin (only active):
            </td>
            <td>
                {{$radminSimCount}}
            </td>
        </tr>
        <tr>
            <td>
                Total Number of SIM updates:
            </td>
            <td>
                {{count($errors)}}
            </td>
        </tr>
         <tr>
            <td>
                Total Number of successful SIM updates:
            </td>
            <td>
                {{$taskStatusCount['passed']}}
            </td>
        </tr>
        <tr>
            <td>
                Total Number of failed SIM updates:
            </td>
            <td>
                {{$taskStatusCount['failed']}}
            </td>
        </tr>
        <tr>
            <td>
                Total Number of pending SIM updates:
            </td>
            <td>
                {{$taskStatusCount['waiting']}}
            </td>
        </tr>
    </tbody>
</table>
@endif

<br />
@if (count($errors) > 0)
    <div>Following are the list of errors for each IMSI and sync status:</div>
    <table class="border-table">
        <thead>
            <tr>
                <th>Terminal ID</th>
                <th>IMSI</th>
                <th>Errors</th>
                <th>Action</th>
                <th>Status</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($errors as $terminalId => $errorDetails)
                <tr>
                    <td align="center">{{$terminalId}}</td>
                    <td>{{$errorDetails['imsi']}}</td>
                    <td valign="middle">
                        <ul>
                            @if(is_array( $errorDetails['errors'] ))
                                @foreach( $errorDetails['errors'] as $message )
                                    <li>{{$message}}</li>
                                @endforeach
                            @else
                                <li>{{$errorDetails['errors']}}</li>
                            @endif
                        </ul>
                    </td>
                    <td>
                        {{$errorDetails['action']}}
                    </td>
                    @if( $errorDetails['taskStatus'] == 'STATUS_DONE_OK' )
                        <td style="background-color: greenyellow;">
                            {{$errorDetails['taskStatus']}}
                        </td>
                    @elseif( $errorDetails['taskStatus'] == 'STATUS_DONE_FAIL' )
                        <td style="background-color: red;">
                            {{$errorDetails['taskStatus']}}
                        </td>
                    @else
                        <td style="background-color: yellow">
                            {{$errorDetails['taskStatus']}}
                        </td>
                    @endif
                    <td>{{$errorDetails['taskStatusMessage']}}</td>
                </tr>
            @endforeach
            @foreach ($jxErrors as $terminalId => $errorDetails)
                <tr>
                    <td align="center">{{$terminalId}}</td>
                    <td>{{$errorDetails['imsi']}}</td>
                    <td valign="middle">
                        <ul>
                            @if(is_array( $errorDetails['errors'] ))
                                @foreach( $errorDetails['errors'] as $message )
                                    <li>{{$message}}</li>
                                @endforeach
                            @else
                                <li>{{$errorDetails['errors']}}</li>
                            @endif
                        </ul>
                    </td>
                    <td>
                        {{$errorDetails['action']}}
                    </td>
                    @if( $errorDetails['taskStatus'] == 'STATUS_DONE_OK' )
                        <td style="background-color: greenyellow;">
                            {{$errorDetails['taskStatus']}}
                        </td>
                    @elseif( $errorDetails['taskStatus'] == 'STATUS_DONE_FAIL' )
                        <td style="background-color: red;">
                            {{$errorDetails['taskStatus']}}
                        </td>
                    @else
                        <td style="background-color: yellow">
                            {{$errorDetails['taskStatus']}}
                        </td>
                    @endif
                    <td>{{$errorDetails['taskStatusMessage']}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div style="color: green">There are no errors!</div>
@endif

<br />

@if( is_array($radminIgnoredSims) && count($radminIgnoredSims) > 0 )
    <br />
    <div>Following SIMs are not considered for Sync:</div>
    <br />
    <table class="border-table">
        <thead>
        <tr>
            <th>IMSI</th>
            <th>Comments</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($radminIgnoredSims as $ignoredImsi)
            <tr>
                <td>
                    {{$ignoredImsi->IMSI}}
                </td>
                <td>
                    {{$ignoredImsi->Comments}}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <br /><br />
@endif

@if (empty($imsiList))
    @if (is_array($radminSims) &&  count($radminSims) > 0)
        <div>Following are the SIMs which are present only in Radmin and active:</div>
        <br />
        <div>
            <div style="font-size: 11px;">Possible reasons include:</div>
            <ul style="font-size: 11px;">
                <li>User manually added SIM into Radmin and has not been provisioned in DBMan</li>
                <li>Sim Is deactivated in dbman and active in Radmin</li>
            </ul>
        </div>

        <br />
        <table class="border-table">
            <thead>
            <tr>
                <th>IMSI</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($radminSims as $sim)
                <tr>
                    <td>
                        {{$sim}}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if( is_array($inactiveRadminOnlySims) && count($inactiveRadminOnlySims) > 0 )
        <br />
        <div>Following are the SIMs which are present only in Radmin and inactive:</div>
        <br />
        <table class="border-table">
            <thead>
            <tr>
                <th>IMSI</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($inactiveRadminOnlySims as $sim)
                <tr>
                    <td>
                        {{$sim}}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endif