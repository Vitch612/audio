<!doctype html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <script type="text/javascript" src="script.js"></script>
        <style>
            input {
                margin-right:10px;
                margin-bottom:10px;
            }
            a.namelink:link,a.namelink:visited,a.namelink:hover,a.namelink:active {
              text-decoration:none;
              color: black;
              margin-right:10px;
              margin-bottom:10px;
              font-size: 20px;
              font-weight: bold;
              display:block;
            }
        </style>
    </head>
    <body style="text-align:center;padding:10px;">
        <input type="text" name="name"><BR>
        <input type="button" name="start" value="Start">
        <input type="button" name="stop" value="Stop">
        <input type="button" name="restart" value="Restart">
        <input type="button" name="reset" value="Reset">
        <div class="players" style="margin-top:20px;text-align:left;"></div>
        <div class="received" style="display:block;"></div>
        <div style="position:relative;width:100%;padding:0;">
        <div class="events" style="max-height:500px;overflow:auto;position:relative;float:left;text-align:left;width:27%;"></div>
        <div class="message" style="max-height:500px;overflow:auto;position:relative;float:right;text-align:left;width:71%;"></div>
        <div class="network" style="margin-top:10px;max-height:600px;overflow:auto;position:relative;float:left;text-align:left;width:100%;"></div>
        </div>
    </body>
</html>