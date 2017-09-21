<!DOCTYPE html>
<html>
    <head>
        <title>DBMan API</title>

        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato';
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
                margin-top: 50px;

            }

            .title {
                font-size: 96px;
            }
            .mini-title {
                font-size: 28px;
            }
            .mini-title a { color:red !important; }

        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">DBMan API</div>
                <a href="{{ url('/public/vendor/l5-swagger/') }}" target="_blank"><div class="mini-title"> Documentation</div></a> <br>
                <a href="{{ url('/v1/permissions') }}" target="_blank"><div class="mini-title">View ACL</div></a> <br>
            </div>
            <iframe src="{{ url('/public/vendor/l5-swagger/') }}" width="85%" height="80%" frameBorder="0"></iframe>
        </div>
    </body>
</html>
