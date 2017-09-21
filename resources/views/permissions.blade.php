<!DOCTYPE html>
<html>
<head>
    <title>DBMan API Permissions Management</title>

    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="public/assets/css/custom.css">

    <style>
        * {
            font-family: 'Roboto', sans-serif;
        }

        table > thead > tr > th {
            /* transform: rotate(270deg); */
            text-align: center;
        }

        table > tbody > tr > td:not(:nth-child(1)) {
            text-align: center;
        }
    </style>

</head>

<body>
    <div style="padding: 10px;">

        <h1>API ACL Permissions</h1>
        <br><br>
        Token: <input type="text" id="auth_token" size="60"
                    value="Bearer NWGFnoyh8Gdpb3voK9HegOkkyeTWDm1LKeT7Nnh5Aum8fXky95bhyqKHb9nx"/>
        <input type="button" onclick="refreshRoles()" value="Get Role/Permissions" />
        <input type="button" onclick="updateRolePermissions()" value="Save" />

        <br><br>
        <div class="starter-template">
            <table id="role-permissions-table" class="table table-striped" width="80%">
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.16.4/lodash.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js"></script>
    <script type="text/javascript" src="resources/assets/scripts/permissions.js"></script>
</body>
</html>
