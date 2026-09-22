<?php
if (!defined('ABSPATH')) exit;
// The supplied V5 is deliberately kept byte-for-byte intact in templates/.
$html=file_get_contents(get_template_directory().'/templates/approved-v5.html');
ob_start(); wp_head(); $head=ob_get_clean();
ob_start(); wp_body_open(); $body=ob_get_clean();
ob_start(); wp_footer(); $footer=ob_get_clean();
echo str_replace(['</head>','<body>','</body>'],[$head.'</head>','<body>'.$body,$footer.'</body>'],$html);
