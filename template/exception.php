<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8"/>
    <title>Hexagon Framework Debug Trace</title>

    <style>
        body, ul, h1, h2 {
            margin: 0;
            padding: 0;
        }

        body {
            background: #fff;
            color: #333;
            font-size: 12px;
        }

        #wrapper {
            margin: 0 auto;
        }

        #inner {
            margin: 5px 0;
            padding: 5px 20px;
        }

        ul {
            list-style: none;
        }

        li {
            padding: 5px 7px;
        }

        li:hover .func {
            background: #333;
            color: #fff;
        }

        .func {
            display: block;
            margin: 8px 0;
            color: #666;
            padding: 3px 20px;
        }

        #trace {
            padding: 4px 10px;
            margin: 20px 0;
        }

        h1 {
            font-size: 22px;
            color: #fff;
            background: darkred;
            padding: 10px 20px;
        }

        h1 span {
            font-size: 12px;
            color: pink;
        }

        h2 {
            font-size: 14px;
            margin: 6px 0;
        }

        h2 span {
            font-size: 12px;
            color: #666;
        }

        .gray {
            color: #888;
        }

        #version {
            text-align: center;
            margin: 10px auto;
            width: 400px;
        }

        #version span.ver {
            color: #69c;
            margin-left: 3px;
        }

        #version #site {
            display: block;
            margin-top: 6px;
            color: #DDD;
            padding: 2px 5px;
        }

        #version #site a {
            color: #DDD;
            text-decoration: none;
            margin-left: 2px;
        }

        /* 文件详细提示 */
        #where {
            border: 1px #ccc solid;
        }

        #where ul {
            padding: 3px 10px;
        }

        #where b {
            color: #999;
            padding: 5px 0;
            margin-right: 10px;
        }

        #where li {
            border-bottom: 1px #eee solid;
            display: block;
        }

        #where li.current {
            background: lightyellow;
        }

        #where li:last-child {
            border-bottom: none;
        }

        #where li:hover {
            color: #fff;
            background: #888;
        }

        #where li:hover b {
            color: #fff;
        }

        .w-block {
            width: 12px;
            display: inline-block;
        }
    </style>

</head>

<body>
<div id="wrapper">

    <h1>Hexagon Framework Debug Trace</h1>

    <div id="inner">
        <p style='font-size: 14px'><?php echo $message ?> <span class="gray">on <?php echo $file ?> (<?php echo $line ?>
                )</span></p>

        <p class="gray">If you do not understand this message, the message can be sent to the site administrator.</p>
    </div>

    <div id="where">
        <ul><?php echo $fileLineLog ?></ul>
    </div>

    <?php if (!empty($trace)): ?>
        <div id="trace">
            <h2>Stack</h2>
            <ul id="trace_log">
                <?php foreach ($trace as $value): ?>
                    <li><?php echo $value ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div id="version">
        Hexagon Framework
    </div>
</div>

</body>
</html>